<?php

declare(strict_types=1);

namespace Unit\Model\Entities;

use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function test_get(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $dbGroup = Group::get($group->uuid);

        $this->assertSame($group->name, $dbGroup->name);
        $this->assertSame(EnumGroupType::PERSON, $dbGroup->type);
        $this->assertSame($group->causeUuid, $dbGroup->causeUuid);
        $this->assertFalse($dbGroup->worldReadable);
        $this->assertSame(0, $dbGroup->signatures());
    }

    public function test_get__withSignatures(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $log1 = new ActivityLog(
            null,
            EnumActivityType::PLEDGE,
            10,
            $cause->uuid,
            $group->uuid,
        );
        $log1->save();
        $log2 = new ActivityLog(
            null,
            EnumActivityType::PLEDGE,
            10,
            $cause->uuid,
            $group->uuid,
        );
        $log2->save();

        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::DE,
            true,
            'user cause test'
        );
        $user->save();

        $entry1 = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            10,
            $log1->uuid,
        );
        $entry1->save();
        $entry2 = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            20,
            $log2->uuid,
        );
        $entry2->save();

        $dbGroup = Group::get($group->uuid);

        $this->assertSame(30, $dbGroup->signatures());
    }

    public function test_get__withWriteable(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::DE,
            true,
            'user cause test'
        );
        $user->save();

        $dbGroup = Group::get($group->uuid);

        $this->assertFalse($dbGroup->toApiModel()->attributes['writeable']);
    }

    public function test_save(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $this->assertNotEmpty($group->uuid);
    }

    public function test_toApiModel(): void
    {
        $group = new Group(
            wp_generate_uuid4(),
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            wp_generate_uuid4(),
            false,
            date_create('2022-06-28T17:10:15+00:00'),
            date_create('2022-06-28T17:10:16+00:00'),
            date_create('2022-06-28T17:10:17+00:00'),
        );

        $group->objectiveUuids = [
            wp_generate_uuid4(),
            wp_generate_uuid4()
        ];

        $group->roleUuids = [
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            wp_generate_uuid4()
        ];

        $apiModel = $group->toApiModel();

        $this->assertSame($group->uuid, $apiModel->id);
        $this->assertSame('group', $apiModel->type);

        $this->assertSame($group->name, $apiModel->attributes['name']);
        $this->assertSame(0, $apiModel->attributes['signatures']);
        $this->assertSame('person', $apiModel->attributes['type']);
        $this->assertArrayHasKey('created', $apiModel->attributes);
        $this->assertArrayHasKey('updated', $apiModel->attributes);
        $this->assertArrayNotHasKey('deleted', $apiModel->attributes);

        $this->assertCount(3, $apiModel->relationships);

        $this->assertCount(2, $apiModel->relationships['objective']);
        $this->assertSame($group->objectiveUuids[0], $apiModel->relationships['objective'][0]['data']['id']);
        $this->assertSame('objective', $apiModel->relationships['objective'][0]['data']['type']);

        $this->assertCount(3, $apiModel->relationships['role']);
        $this->assertSame($group->roleUuids[0], $apiModel->relationships['role'][0]['data']['id']);
        $this->assertSame('role', $apiModel->relationships['role'][0]['data']['type']);

        $this->assertSame($group->causeUuid, $apiModel->relationships['cause']['data']['id']);
        $this->assertSame('cause', $apiModel->relationships['cause']['data']['type']);
    }

    public function test_fromApiModelToPropsArray(): void
    {
        $apiData = [
            'id' => wp_generate_uuid4(),
            'type' => 'group',
            'attributes' => [
                'name' => 'test_' . wp_generate_password(),
                'type' => 'person',
            ],
            'relationships' => [
                'objective' => [
                    [
                        'data' => [
                            'id' => wp_generate_uuid4(),
                            'type' => 'objective',
                        ],
                    ],
                    [
                        'data' => [
                            'id' => wp_generate_uuid4(),
                            'type' => 'objective',
                        ],
                    ],
                ],
                'cause' => [
                    'data' => [
                        'id' => wp_generate_uuid4(),
                        'type' => 'cause',
                    ],
                ],
            ],
        ];

        $props = Group::fromApiModelToPropsArray($apiData);
        unset($props['objectiveUuids']);

        /** @noinspection PhpParamsInspection */
        $group = new Group(...$props, worldReadable: false);

        $this->assertSame($apiData['id'], $group->uuid);
        $this->assertSame($apiData['attributes']['name'], $group->name);
        $this->assertSame(EnumGroupType::PERSON, $group->type);
        $this->assertSame($apiData['relationships']['cause']['data']['id'], $group->causeUuid);
    }

    public function test_findByCauseAndReadableByUser(): void
    {
        $cause1 = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause1->save();

        $cause2 = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause2->save();

        $groupWorldReadTrue = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause1->uuid,
            true,
        );
        $groupWorldReadTrue->save();

        $groupRoleRead = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause1->uuid,
            false,
        );
        $groupRoleRead->save();

        $groupRoleReadWrite = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause1->uuid,
            false,
        );
        $groupRoleReadWrite->save();

        // not cause
        $groupOtherCause = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause2->uuid,
            true,
        );
        $groupOtherCause->save();

        // not readable
        $groupWorldReadFalse = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause1->uuid,
            false,
        );
        $groupWorldReadFalse->save();

        $user1 = new User(
            null,
            wp_generate_uuid4().'@example.com',
            'John',
            'Doe',
            EnumLang::DE,
            true,
            'test group'
        );
        $user1->save();

        $user2= new User(
            null,
            wp_generate_uuid4().'@example.com',
            'Jane',
            'Doe',
            EnumLang::DE,
            true,
            'test group'
        );
        $user2->save();

        $roleRead = new Role(
            null,
            $user1->uuid,
            $groupRoleRead->uuid,
            EnumPermission::READ
        );
        $roleRead->save();

        $roleReadWrite = new Role(
            null,
            $user1->uuid,
            $groupRoleReadWrite->uuid,
            EnumPermission::READ_WRITE
        );
        $roleReadWrite->save();

        $roleOtherUser = new Role(
            null,
            $user2->uuid,
            $groupWorldReadFalse->uuid,
            EnumPermission::READ_WRITE
        );
        $roleOtherUser->save();

        $roleDeleted = new Role(
            null,
            $user1->uuid,
            $groupWorldReadFalse->uuid,
            EnumPermission::READ_WRITE
        );
        $roleDeleted->save();
        $roleDeleted->delete();

        $groups = Group::findByCauseAndReadableByUser($cause1->uuid, $user1->uuid);

        $this->assertCount(3, $groups);

        $groupsUuids = array_map(static fn(Group $group) => $group->uuid, $groups);
        $this->assertContains($groupWorldReadTrue->uuid, $groupsUuids);
        $this->assertContains($groupRoleRead->uuid, $groupsUuids);
        $this->assertContains($groupRoleReadWrite->uuid, $groupsUuids);
        $this->assertNotContains($groupOtherCause->uuid, $groupsUuids);
        $this->assertNotContains($groupWorldReadFalse->uuid, $groupsUuids);
    }

    public function test_userCanWrite(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $groupWorldRead = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            true,
        );
        $groupWorldRead->save();

        $groupRoleRead = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $groupRoleRead->save();

        $groupRoleReadWrite = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $groupRoleReadWrite->save();

        $user1 = new User(
            null,
            wp_generate_uuid4().'@example.com',
            'John',
            'Doe',
            EnumLang::DE,
            true,
            'test group'
        );
        $user1->save();

        $user2= new User(
            null,
            wp_generate_uuid4().'@example.com',
            'Jane',
            'Doe',
            EnumLang::DE,
            true,
            'test group'
        );
        $user2->save();

        $roleRead = new Role(
            null,
            $user1->uuid,
            $groupRoleRead->uuid,
            EnumPermission::READ
        );
        $roleRead->save();

        $roleReadWrite = new Role(
            null,
            $user1->uuid,
            $groupRoleReadWrite->uuid,
            EnumPermission::READ_WRITE
        );
        $roleReadWrite->save();

        $roleOtherUser = new Role(
            null,
            $user2->uuid,
            $groupWorldRead->uuid,
            EnumPermission::READ_WRITE
        );
        $roleOtherUser->save();

        $roleDeleted = new Role(
            null,
            $user1->uuid,
            $groupWorldRead->uuid,
            EnumPermission::READ_WRITE
        );
        $roleDeleted->save();
        $roleDeleted->delete();

        $this->assertTrue($groupRoleReadWrite->userCanWrite($user1->uuid));
        $this->assertFalse($groupRoleRead->userCanWrite($user1->uuid));
        $this->assertFalse($groupWorldRead->userCanWrite($user1->uuid));
    }

    public function test_getMany(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $uuidsEven = [];
        for ($i = 0; $i < 10; $i++) {
            $group = (new Group(
                null,
                'test_' . wp_generate_password(),
                EnumGroupType::PERSON,
                $cause->uuid,
                true,
            ))->save();

            if ($i % 2 === 0) {
                $uuidsEven[] = $group->uuid;
            }
        }

        $dbGroups = Group::getMany($uuidsEven);
        $uuids = array_map(static fn(Group $group) => $group->uuid, $dbGroups);

        $this->assertEqualsCanonicalizing($uuidsEven, $uuids);
    }
}
