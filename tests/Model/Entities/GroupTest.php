<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\Group;
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
        $this->assertSame(0, $apiModel->attributes['_signatures']);
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
}
