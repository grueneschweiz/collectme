<?php

declare(strict_types=1);

namespace Unit\Controller;

use Collectme\Controller\GroupController;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class GroupControllerTest extends TestCase
{

    public function testFindByCause(): void
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
            wp_generate_uuid4() . '@example.com',
            'John',
            'Doe',
            EnumLang::DE,
            true,
            'test group'
        );
        $user1->save();

        $user2 = new User(
            null,
            wp_generate_uuid4() . '@example.com',
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

        $objective = new Objective(
            null,
            100,
            $groupRoleRead->uuid,
            'Newsletter 220401'
        );
        $objective->save();

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getUserUuid')
            ->willReturn($user1->uuid);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $cause1->uuid);

        $controller = new GroupController($authMock);

        $result = $controller->findByCause($request);
        $data = json_decode(json_encode($result->get_data()), true);

        $this->assertEquals(200, $result->get_status());
        $this->assertCount(2, $data);

        $groupUuids = array_map(static fn(array $group) => $group['id'], $data['data']);
        $this->assertEqualsCanonicalizing([$groupWorldReadTrue->uuid, $groupRoleRead->uuid], $groupUuids);

        $this->assertFalse($data['data'][0]['attributes']['writeable']);

        $objectives = array_values(array_filter(
            $data['included'],
            static fn($item) => $item['type'] === 'objective'
        ));

        $this->assertEquals($objective->uuid, $objectives[0]['id']);
        $this->assertEquals($objective->objective, $objectives[0]['attributes']['objective']);

        $roles = array_values(array_filter(
            $data['included'],
            static fn($item) => $item['type'] === 'role'
        ));

        $this->assertEquals($roleRead->uuid, $roles[0]['id']);
        $this->assertEquals($roleRead->permission->value, $roles[0]['attributes']['permission']);
    }
}
