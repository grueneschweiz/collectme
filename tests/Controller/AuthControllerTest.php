<?php
/** @noinspection JsonEncodingApiUsageInspection */

declare(strict_types=1);

namespace Controller;

use Collectme\Controller\AuthController;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{

    public function test_loginWithToken__noToken(): void
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

        $request = new \WP_REST_Request();
        $authMock = $this->createMock(Auth::class);

        $controller = new AuthController($authMock);

        $resp = $controller->loginWithToken($request);

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_loginWithToken__invalidToken(): void
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

        $request = new \WP_REST_Request();
        $request->set_param('token', 'invalid');

        $authMock = $this->createMock(Auth::class);

        $controller = new AuthController($authMock);

        $resp = $controller->loginWithToken($request);

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_loginWithToken__nonExistentToken(): void
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

        $request = new \WP_REST_Request();
        $request->set_param('token', wp_generate_password(64, false));

        $authMock = $this->createMock(Auth::class);

        $controller = new AuthController($authMock);

        $resp = $controller->loginWithToken($request);

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_loginWithToken__tokenExpired(): void
    {
        $cause = new Cause(
            null,
            'auth_controller_'.wp_generate_password(),
        );
        $cause->save();

        $token = wp_generate_password(64, false, false);
        $accountToken = new AccountToken(
            null,
            $token,
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::EN,
            date_create('-1 second')
        );
        $accountToken = $accountToken->save();

        $expectedJson = json_encode([
            'errors' => [
                [
                    'status' => 404,
                    'title' => 'Invalid Token',
                    'source' => ['parameter' => 'token']
                ],
            ]
        ]);

        $request = new \WP_REST_Request();
        $request->set_param('token', $token);
        $request->set_param('email', $accountToken->email);
        $request->set_param('cause', $cause->uuid);

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn(null);

        $controller = new AuthController($authMock);

        $resp = $controller->loginWithToken($request);

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_loginWithToken__invalidCause(): void
    {
        $expectedJson = json_encode([
            'errors' => [
                [
                    'status' => 404,
                    'title' => 'Invalid Cause',
                    'source' => ['parameter' => 'cause']
                ],
            ]
        ]);

        $request = new \WP_REST_Request();
        $request->set_param('token', wp_generate_password(64, false));
        $request->set_param('email', 'mail@example.com');
        $request->set_param('cause', wp_generate_uuid4());

        $authMock = $this->createMock(Auth::class);

        $controller = new AuthController($authMock);

        $resp = $controller->loginWithToken($request);

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_loginWithToken__success(): void
    {
        $token = wp_generate_password(64, false, false);
        $accountToken = new AccountToken(
            null,
            $token,
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::EN,
            date_create('+2 years')
        );
        $accountToken = $accountToken->save();

        $user = new User(
            null,
            $accountToken->email,
            $accountToken->firstName,
            $accountToken->lastName,
            $accountToken->lang,
            'test: some string',
            date_create(),
            date_create(),
        );
        $user->save();

        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            wp_generate_uuid4(),
            $user->uuid,
            0,
            null,
            $sessionSecret,
            $sessionHash,
            date_create(),
            null,
            date_create(),
            date_create(),
        );

        $cause = new Cause(
            null,
            'auth_controller_'.wp_generate_password(),
        );
        $cause->save();

        $expectedJson = json_encode([
            'data' => [
                'id' => $session->uuid,
                'type' => 'session',
                'attributes' => [
                    'loginCounter' => 0,
                    'lastLogin' => null,
                    'activated' => $session->activated->format(DATE_RFC3339_EXTENDED),
                    'created' => $session->created->format(DATE_RFC3339_EXTENDED),
                    'updated' => $session->updated->format(DATE_RFC3339_EXTENDED),
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'id' => $user->uuid,
                            'type' => 'user'
                        ]
                    ]
                ]
            ]
        ]);

        $request = new \WP_REST_Request();
        $request->set_param('token', $token);
        $request->set_param('email', $accountToken->email);
        $request->set_param('cause', $cause->uuid);

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getOrCreateUserFromAccountToken')
            ->with($accountToken)
            ->willReturn($user);
        $authMock->expects($this->once())
            ->method('createPersistentSession')
            ->with($user, true);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn($session);

        $controller = new AuthController($authMock);

        $resp = $controller->loginWithToken($request);

        $this->assertEquals(200, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
        $this->assertCount(1, $user->causes());
    }
}
