<?php

declare(strict_types=1);

namespace Unit\Controller;

use Collectme\Controller\ObjectiveController;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class ObjectiveControllerTest extends TestCase
{

    public function test_add__success(): void
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
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ_WRITE,
        );
        $role->save();

        $apiData = [
            'data' => [
                'type' => 'objective',
                'attributes' => [
                    'objective' => 200,
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'type' => 'group',
                            'id' => $group->uuid,
                        ],
                    ],
                ],
            ],
        ];

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getUserUuid')
            ->willReturn($user->uuid);

        $request = new \WP_REST_Request();
        $request->set_body(wp_json_encode($apiData));
        $request->set_header('Content-Type', 'application/json');

        $controller = new ObjectiveController($authMock);

        $response = $controller->add($request);
        $data = json_decode(json_encode($response->get_data()), true);

        $this->assertEquals(201, $response->get_status());
        $this->assertSame('objective', $data['data']['type']);
        $this->assertSame(200, $data['data']['attributes']['objective']);
        $this->assertNotEmpty($data['data']['id']);
        $this->assertNotEmpty($data['data']['relationships']['group']['data']['id']);

        $log = ActivityLog::findByCause($cause->uuid);
        $this->assertCount(1, $log);
    }

    public function test_add__success__idempotency(): void
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
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ_WRITE,
        );
        $role->save();

        $apiData = [
            'data' => [
                'type' => 'objective',
                'attributes' => [
                    'objective' => 200,
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'type' => 'group',
                            'id' => $group->uuid,
                        ],
                    ],
                ],
            ],
        ];

        $authMock = $this->createMock(Auth::class);
        $authMock->method('getUserUuid')
            ->willReturn($user->uuid);

        $request = new \WP_REST_Request();
        $request->set_body(wp_json_encode($apiData));
        $request->set_header('Content-Type', 'application/json');

        $controller = new ObjectiveController($authMock);

        $response1 = $controller->add($request);
        $response2 = $controller->add($request);

        $this->assertEquals(201, $response1->get_status());
        $this->assertEquals(200, $response2->get_status());

        $log = ActivityLog::findByCause($cause->uuid);
        $this->assertCount(1, $log);
    }

    public function test_add__org__noLog(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::ORGANIZATION,
            $cause->uuid,
            false,
        );
        $group->save();

        $user = new User(
            null,
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ_WRITE,
        );
        $role->save();

        $apiData = [
            'data' => [
                'type' => 'objective',
                'attributes' => [
                    'objective' => 200,
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'type' => 'group',
                            'id' => $group->uuid,
                        ],
                    ],
                ],
            ],
        ];

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getUserUuid')
            ->willReturn($user->uuid);

        $request = new \WP_REST_Request();
        $request->set_body(wp_json_encode($apiData));
        $request->set_header('Content-Type', 'application/json');

        $controller = new ObjectiveController($authMock);

        $response = $controller->add($request);
        $data = json_decode(json_encode($response->get_data()), true);

        $this->assertEquals(201, $response->get_status());
        $this->assertSame('objective', $data['data']['type']);
        $this->assertSame(200, $data['data']['attributes']['objective']);
        $this->assertNotEmpty($data['data']['id']);
        $this->assertNotEmpty($data['data']['relationships']['group']['data']['id']);

        $log = ActivityLog::findByCause($cause->uuid);
        $this->assertEmpty( $log);
    }


    public function test_add__noDowngrade(): void
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
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ_WRITE,
        );
        $role->save();

        $apiData = [
            'data' => [
                'type' => 'objective',
                'attributes' => [
                    'objective' => 200,
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'type' => 'group',
                            'id' => $group->uuid,
                        ],
                    ],
                ],
            ],
        ];

        $authMock = $this->createMock(Auth::class);
        $authMock->method('getUserUuid')
            ->willReturn($user->uuid);

        $request = new \WP_REST_Request();
        $request->set_body(wp_json_encode($apiData));
        $request->set_header('Content-Type', 'application/json');

        $controller = new ObjectiveController($authMock);

        $create = $controller->add($request);

        $apiData['data']['attributes']['objective'] = 100;
        $request->set_body(wp_json_encode($apiData));
        $downgrade = $controller->add($request);

        $this->assertEquals(201, $create->get_status());
        $this->assertEquals(422, $downgrade->get_status());

        $log = ActivityLog::findByCause($cause->uuid);
        $this->assertCount(1, $log);
        $this->assertEquals(200, $log[0]->count);
    }

    public function test_add__noWritePermission(): void
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
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ,
        );
        $role->save();

        $apiData = [
            'data' => [
                'type' => 'objective',
                'attributes' => [
                    'objective' => 200,
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'type' => 'group',
                            'id' => $group->uuid,
                        ],
                    ],
                ],
            ],
        ];

        $authMock = $this->createMock(Auth::class);
        $authMock->method('getUserUuid')
            ->willReturn($user->uuid);

        $request = new \WP_REST_Request();
        $request->set_body(wp_json_encode($apiData));
        $request->set_header('Content-Type', 'application/json');

        $controller = new ObjectiveController($authMock);

        $response = $controller->add($request);

        $this->assertEquals(401, $response->get_status());
    }
}
