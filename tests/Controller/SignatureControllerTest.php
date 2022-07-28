<?php

declare(strict_types=1);

namespace Controller;

use Collectme\Controller\SignatureController;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class SignatureControllerTest extends TestCase
{

    public function test_add__invalidApiModel(): void
    {
        $apiData = [
            'data' => [
                'type' => 'signature',
                'attributes' => [
                    'INVALID' => 13,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => wp_generate_uuid4(),
                        ],
                    ],
                    'group' => [
                        'data' => [
                            'type' => 'group',
                            'id' => wp_generate_uuid4(),
                        ],
                    ],
                ],
            ],
        ];

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getUserUuid')
            ->willReturn(wp_generate_uuid4());

        $request = new \WP_REST_Request();
        $request->set_body(wp_json_encode($apiData));
        $request->set_header('Content-Type', 'application/json');

        $controller = new SignatureController($authMock);

        $response = $controller->add($request);

        $this->assertEquals(422, $response->get_status());
    }

    public function test_add__invalidAttrValues(): void
    {
        $apiData = [
            'data' => [
                'type' => 'signature',
                'attributes' => [
                    'count' => 0,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => 'asdf',
                        ],
                    ],
                    'group' => [
                        'data' => [
                            'type' => 'group',
                            'id' => 'asdf',
                        ],
                    ],
                ],
            ],
        ];

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getUserUuid')
            ->willReturn(wp_generate_uuid4());

        $request = new \WP_REST_Request();
        $request->set_body(wp_json_encode($apiData));
        $request->set_header('Content-Type', 'application/json');

        $controller = new SignatureController($authMock);

        $response = $controller->add($request);

        $this->assertEquals(422, $response->get_status());

        $errors = json_decode(json_encode($response->get_data()), true)['errors'];
        $pointers = array_map(static fn($error) => $error['source']['pointer'], $errors);
        $this->assertEqualsCanonicalizing(
            [
                '/data/attributes/count',
                '/data/relationships/user/data/id',
                '/data/relationships/group/data/id',
            ],
            $pointers
        );
    }

    public function test_add__noGroupWritePermission(): void
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
            true,
        );
        $group->save();

        $user = new User(
            null,
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
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
                'type' => 'signature',
                'attributes' => [
                    'count' => 13,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => $user->uuid,
                        ],
                    ],
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

        $controller = new SignatureController($authMock);

        $response = $controller->add($request);

        $this->assertEquals(401, $response->get_status());
    }

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
                'type' => 'signature',
                'attributes' => [
                    'count' => 13,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => $user->uuid,
                        ],
                    ],
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

        $controller = new SignatureController($authMock);

        $response = $controller->add($request);
        $data = json_decode(json_encode($response->get_data()), true);

        $this->assertEquals(201, $response->get_status());
        $this->assertSame('signature', $data['data']['type']);
        $this->assertNotEmpty($data['data']['id']);
        $this->assertNotEmpty($data['data']['relationships']['activity']['data']['id']);
    }

    public function test_add__achieveObjective(): void
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
            'user cause test'
        );
        $user->save();

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            90,
            $cause->uuid,
            $group->uuid
        );
        $log->save();

        $signature = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            99,
            $log->uuid
        );
        $signature->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ_WRITE,
        );
        $role->save();

        $objective = new Objective(
            null,
            100,
            $group->uuid,
            'test'
        );
        $objective->save();

        $apiData = [
            'data' => [
                'type' => 'signature',
                'attributes' => [
                    'count' => 1,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => $user->uuid,
                        ],
                    ],
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

        $controller = new SignatureController($authMock);

        $response = $controller->add($request);
        $data = json_decode(json_encode($response->get_data()), true);

        $activity = ActivityLog::get($data['data']['relationships']['activity']['data']['id']);

        $this->assertEquals(EnumActivityType::PERSONAL_GOAL_ACHIEVED, $activity->type);
    }

    public function test_add__notAchieveObjective(): void
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
            'user cause test'
        );
        $user->save();

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            90,
            $cause->uuid,
            $group->uuid
        );
        $log->save();

        $signature = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            100,
            $log->uuid
        );
        $signature->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ_WRITE,
        );
        $role->save();

        $objective = new Objective(
            null,
            100,
            $group->uuid,
            'test'
        );
        $objective->save();

        $apiData = [
            'data' => [
                'type' => 'signature',
                'attributes' => [
                    'count' => 1,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => $user->uuid,
                        ],
                    ],
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

        $controller = new SignatureController($authMock);

        $response = $controller->add($request);
        $data = json_decode(json_encode($response->get_data()), true);

        $activity = ActivityLog::get($data['data']['relationships']['activity']['data']['id']);

        $this->assertEquals(EnumActivityType::PERSONAL_SIGNATURE, $activity->type);
    }

    public function test_delete__success(): void
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

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            13,
            $cause->uuid,
            $group->uuid,
        );
        $log->save();

        $signature = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            13,
            $log->uuid,
        );
        $signature->save();

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getUserUuid')
            ->willReturn($user->uuid);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $signature->uuid);

        $controller = new SignatureController($authMock);
        $response = $controller->delete($request);

        $this->assertSame(204, $response->get_status());
    }

    public function test_delete__alreadyDeleted(): void
    {
        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getUserUuid')
            ->willReturn(wp_generate_uuid4());

        $request = new \WP_REST_Request();
        $request->set_param('uuid', wp_generate_uuid4());

        $controller = new SignatureController($authMock);
        $response = $controller->delete($request);

        $this->assertSame(204, $response->get_status());
    }

    public function test_delete__notAuthorized(): void
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

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            13,
            $cause->uuid,
            $group->uuid,
        );
        $log->save();

        $signature = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            13,
            $log->uuid,
        );
        $signature->save();

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getUserUuid')
            ->willReturn(wp_generate_uuid4());

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $signature->uuid);

        $controller = new SignatureController($authMock);
        $response = $controller->delete($request);

        $this->assertSame(401, $response->get_status());
    }
}
