<?php /** @noinspection JsonEncodingApiUsageInspection */

declare(strict_types=1);

namespace Controller;

use Collectme\Controller\SessionController;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class SessionControllerTest extends TestCase
{

    public function test_getCurrent__success(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            1,
            date_create(date_create()->format(DATE_ATOM)),
            wp_generate_password(64, false),
            wp_hash_password(wp_generate_password(64, false)),
            date_create(date_create()->format(DATE_ATOM)),
            null,
            date_create(date_create()->format(DATE_ATOM)),
            date_create(date_create()->format(DATE_ATOM)),
        );

        $expectedJson = json_encode([
            'data' => [
                'id' => $session->uuid,
                'type' => 'session',
                'attributes' => [
                    'loginCounter' => $session->loginCounter,
                    'lastLogin' => $session->lastLogin->format(DATE_RFC3339_EXTENDED),
                    'activated' => $session->activated->format(DATE_RFC3339_EXTENDED),
                    'created' => $session->created->format(DATE_RFC3339_EXTENDED),
                    'updated' => $session->updated->format(DATE_RFC3339_EXTENDED),
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'id' => $session->userUuid,
                            'type' => 'user'
                        ],
                    ],
                ],
            ],
        ]);


        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn($session);

        $controller = new SessionController($authMock);
        $resp = $controller->getCurrent(new \WP_REST_Request());

        $this->assertEquals(200, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_getCurrent__noSessionUuid(): void
    {
        $expectedJson = json_encode([
            'errors' => [
                [
                    'status' => 401,
                    'title' => 'Unauthorized',
                ],
            ]
        ]);

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn(null);
        $authMock->expects($this->once())
            ->method('getClaimedSessionUuid')
            ->willReturn(null);

        $controller = new SessionController($authMock);
        $resp = $controller->getCurrent(new \WP_REST_Request());

        $this->assertEquals(401, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_getCurrent__closedSession(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'John',
            'Doe',
            EnumLang::FR,
            'test: some string',
            date_create(),
            date_create(),
        );
        $user->save();

        $session = new PersistentSession(
            null,
            $user->uuid,
            1,
            date_create(date_create()->format(DATE_ATOM)),
            wp_generate_password(64, false),
            wp_hash_password(wp_generate_password(64, false)),
            date_create(date_create()->format(DATE_ATOM)),
            date_create(date_create('-1 second')->format(DATE_ATOM)),
            date_create(date_create()->format(DATE_ATOM)),
            date_create(date_create()->format(DATE_ATOM)),
        );
        $session->save();

        $expectedJson = json_encode([
            'errors' => [
                [
                    'status' => 404,
                    'title' => 'Not Found',
                ],
            ]
        ]);

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn(null);
        $authMock->expects($this->once())
            ->method('getClaimedSessionUuid')
            ->willReturn($session->uuid);

        $controller = new SessionController($authMock);
        $resp = $controller->getCurrent(new \WP_REST_Request());

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_getCurrent__nonExistentSessionUuid(): void
    {
        $expectedJson = json_encode([
            'errors' => [
                [
                    'status' => 404,
                    'title' => 'Not Found',
                ],
            ]
        ]);

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn(null);
        $authMock->expects($this->once())
            ->method('getClaimedSessionUuid')
            ->willReturn(wp_generate_uuid4());

        $controller = new SessionController($authMock);
        $resp = $controller->getCurrent(new \WP_REST_Request());

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_activate__success(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'John',
            'Doe',
            EnumLang::FR,
            'test: some string',
        );
        $user->save();

        $session = new PersistentSession(
            null,
            $user->uuid,
            0,
            null,
            wp_generate_password(64, false),
            wp_hash_password(wp_generate_password(64, false)),
            null,
            null,
        );
        $session->save();

        $authMock = $this->createMock(Auth::class);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $session->uuid);
        $request->set_param('token', $session->activationSecret);

        $controller = new SessionController($authMock);
        $resp = $controller->activate($request);

        $this->assertEquals(204, $resp->get_status());
        $this->assertTrue(
            PersistentSession::get($session->uuid)->isActivated()
        );
    }

    public function test_activate__invalidToken(): void
    {
        $expectedJson = json_encode([
            'errors' => [
                [
                    'status' => 404,
                    'title' => 'Invalid Token',
                    'source' => ['parameter' => 'token']
                ],
            ]
        ]);

        $authMock = $this->createMock(Auth::class);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', wp_generate_uuid4());
        $request->set_param('token', 'invalid');

        $controller = new SessionController($authMock);
        $resp = $controller->activate($request);

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString(
            $expectedJson,
            wp_json_encode($resp->jsonSerialize())
        );
    }

    public function test_activate__closed(): void
    {
        $expectedJson = json_encode([
            'errors' => [
                [
                    'status' => 404,
                    'title' => 'Not Found',
                ],
            ]
        ]);

        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'John',
            'Doe',
            EnumLang::FR,
            'test: some string',
        );
        $user->save();

        $session = new PersistentSession(
            null,
            $user->uuid,
            0,
            null,
            wp_generate_password(64, false),
            wp_hash_password(wp_generate_password(64, false)),
            null,
            date_create('-1 second'),
        );
        $session->save();

        $authMock = $this->createMock(Auth::class);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $session->uuid);
        $request->set_param('token', $session->activationSecret);

        $controller = new SessionController($authMock);
        $resp = $controller->activate($request);

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString(
            $expectedJson,
            wp_json_encode($resp->jsonSerialize())
        );
    }

    public function test_activate__invalidSessionUuid(): void
    {
        $expectedJson = json_encode([
            'errors' => [
                [
                    'status' => 404,
                    'title' => 'Not Found',
                ],
            ]
        ]);

        $authMock = $this->createMock(Auth::class);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', wp_generate_uuid4());
        $request->set_param('token', wp_generate_password(64, false));

        $controller = new SessionController($authMock);
        $resp = $controller->activate($request);

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString(
            $expectedJson,
            wp_json_encode($resp->jsonSerialize())
        );
    }

    public function test_logout__success(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            wp_generate_password(64, false),
            wp_hash_password(wp_generate_password(64, false)),
            null,
            null,
        );

        $authMock = $this->createMock(Auth::class);

        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn($session);

        $authMock->expects($this->once())
            ->method('logout');

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $session->uuid);

        $controller = new SessionController($authMock);
        $resp = $controller->logout($request);

        $this->assertEquals(204, $resp->get_status());
        $this->assertEmpty($resp->get_data());
    }

    public function test_logout__unauthorized(): void
    {
        $expectedJson = json_encode([
            'errors' => [
                [
                    'status' => 401,
                    'title' => 'Unauthorized',
                ],
            ]
        ]);

        $session = new PersistentSession(
            null,
            wp_generate_uuid4(),
            0,
            null,
            wp_generate_password(64, false),
            wp_hash_password(wp_generate_password(64, false)),
            null,
            null,
        );

        $authMock = $this->createMock(Auth::class);

        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn($session);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', wp_generate_uuid4());

        $controller = new SessionController($authMock);
        $resp = $controller->logout($request);

        $this->assertEquals(401, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->get_data()));
    }
}
