<?php

declare(strict_types=1);

namespace Unit\Model\Entities;

use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_get(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user->save();

        $cause = new Cause(
            null,
            'test_'.wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ
        );
        $role->save();

        $role = Role::get($role->uuid);

        $this->assertSame($user->uuid, $role->userUuid);
        $this->assertSame($group->uuid, $role->groupUuid);
        $this->assertSame(EnumPermission::READ, $role->permission);
    }

    public function test_save(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user->save();

        $cause = new Cause(
            null,
            'test_'.wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ
        );
        $role->save();

        $this->assertNotEmpty($role->uuid);
    }

    public function test_toApiModel(): void
    {
        $role = new Role(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            EnumPermission::READ,
        );

        $apiModel = $role->toApiModel();

        $this->assertSame($role->uuid, $apiModel->id);
        $this->assertSame('role', $apiModel->type);
        $this->assertSame('r', $apiModel->attributes['permission']);
        $this->assertSame($role->userUuid, $apiModel->relationships['user']['data']['id']);
        $this->assertSame($role->groupUuid, $apiModel->relationships['group']['data']['id']);
    }

    public function test_fromApiModelToPropsArray(): void
    {
        $apiData = [
            'id' => wp_generate_uuid4(),
            'type' => 'role',
            'attributes' => [
                'permission' => 'r',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'id' => wp_generate_uuid4(),
                        'type' => 'user',
                    ],
                ],
                'group' => [
                    'data' => [
                        'id' => wp_generate_uuid4(),
                        'type' => 'group',
                    ],
                ],
            ],
        ];

        $role = new Role(...Role::fromApiModelToPropsArray($apiData));

        $this->assertSame($apiData['id'], $role->uuid);
        $this->assertSame(EnumPermission::READ, $role->permission);
        $this->assertSame($apiData['relationships']['user']['data']['id'], $role->userUuid);
        $this->assertSame($apiData['relationships']['group']['data']['id'], $role->groupUuid);
    }

    public function test_findByGroup(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user->save();

        $cause = new Cause(
            null,
            'test_'.wp_generate_password(),
        );
        $cause->save();

        $group1 = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group1->save();

        $group2 = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group2->save();

        $roleR = new Role(
            null,
            $user->uuid,
            $group1->uuid,
            EnumPermission::READ
        );
        $roleR->save();

        $roleRW = new Role(
            null,
            $user->uuid,
            $group1->uuid,
            EnumPermission::READ_WRITE
        );
        $roleRW->save();

        $roleDeleted = new Role(
            null,
            $user->uuid,
            $group1->uuid,
            EnumPermission::READ_WRITE,
        );
        $roleDeleted->save();
        $roleDeleted->delete();

        $roleOtherGroup = new Role(
            null,
            $user->uuid,
            $group2->uuid,
            EnumPermission::READ_WRITE,
        );
        $roleOtherGroup->save();

        $roles = Role::findByGroups([$group1->uuid]);

        $this->assertCount(2, $roles);

        $rolesUuids = array_map(static fn(Role $role) => $role->uuid, $roles);
        $this->assertContains($roleR->uuid, $rolesUuids);
        $this->assertContains($roleRW->uuid, $rolesUuids);
        $this->assertNotContains($roleDeleted->uuid, $rolesUuids);
        $this->assertNotContains($roleOtherGroup->uuid, $rolesUuids);
    }
}
