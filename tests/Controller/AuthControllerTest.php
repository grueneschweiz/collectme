<?php
/** @noinspection JsonEncodingApiUsageInspection */

declare(strict_types=1);

namespace Controller;

use Collectme\Controller\AuthController;
use Collectme\Email\LoginEmail;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{

    public function test_loginWithFormData__success(): void
    {
        $cause = new Cause(
            null,
            'auth_controller_' . wp_generate_password(),
        );
        $cause->save();

        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash(wp_generate_password(), PASSWORD_DEFAULT);
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            $sessionSecret,
            $sessionHash,
            date_create(),
            null,
            date_create(),
            date_create(),
        );

        $request = new \WP_REST_Request();
        $request->set_param('data', [
            'attributes' => [
                'email' => wp_generate_uuid4() . '@mail.com         ', // space added by purpose
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'appUrl' => 'https://example.com',
                'urlAuth' => wp_hash('https://example.com', 'nonce'),
            ],
            'relationships' => [
                'cause' => [
                    'data' => [
                        'id' => $cause->uuid,
                    ]
                ]
            ],
        ]);

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('createPersistentSession')
            ->with($this->isInstanceOf(User::class), false);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn($session);

        $emailMock = $this->createMock(LoginEmail::class);
        $emailMock->expects($this->once())
            ->method('send');

        $controller = new AuthController($authMock, $emailMock);
        $resp = $controller->loginWithFormData($request);

        $respData = json_decode(json_encode($resp->get_data()), true);

        self::assertSame(201, $resp->get_status());
        self::assertEqualsCanonicalizing($session->toApiModel()->toArray(), $respData['data']);
    }

    public function test_loginWithFormData__invalidUrl(): void
    {
        $cause = new Cause(
            null,
            'auth_controller_' . wp_generate_password(),
        );
        $cause->save();

        $request = new \WP_REST_Request();
        $request->set_param('data', [
            'attributes' => [
                'email' => wp_generate_uuid4() . '@mail.com',
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'appUrl' => 'https://evil.com',
                'urlAuth' => wp_hash('https://example.com', 'nonce'),
            ],
            'relationships' => [
                'cause' => [
                    'data' => [
                        'id' => $cause->uuid,
                    ]
                ]
            ],
        ]);

        $authMock = $this->createMock(Auth::class);
        $emailMock = $this->createMock(LoginEmail::class);

        $controller = new AuthController($authMock, $emailMock);
        $resp = $controller->loginWithFormData($request);

        $respData = json_decode(json_encode($resp->get_data()), true);

        self::assertSame(422, $resp->get_status());
        self::assertSame('/data/attributes/urlAuth', $respData['errors'][0]['source']['pointer']);
        self::assertSame('/data/attributes/appUrl', $respData['errors'][1]['source']['pointer']);
    }

    public function test_loginWithFormData__invalidOther(): void
    {
        $request = new \WP_REST_Request();
        $request->set_param('data', [
            'attributes' => [
                'email' => 'invalid',
                'firstName' => '<script>alert("xss")</script>',
                'lastName' => str_repeat('a', 50),
                'appUrl' => 'ftp://example.com',
//                'urlAuth' => null, // missing by purpose
            ],
            'relationships' => [
                'cause' => [
                    'data' => [
                        'id' => wp_generate_uuid4(),
                    ]
                ]
            ],
        ]);

        $authMock = $this->createMock(Auth::class);
        $emailMock = $this->createMock(LoginEmail::class);

        $controller = new AuthController($authMock, $emailMock);
        $resp = $controller->loginWithFormData($request);

        $respData = json_decode(json_encode($resp->get_data()), true);

        self::assertSame(422, $resp->get_status());
        self::assertSame('/data/attributes/email', $respData['errors'][0]['source']['pointer']);
        self::assertSame('/data/attributes/firstName', $respData['errors'][1]['source']['pointer']);
        self::assertSame('/data/attributes/lastName', $respData['errors'][2]['source']['pointer']);
        self::assertSame('/data/attributes/urlAuth', $respData['errors'][3]['source']['pointer']);
        self::assertSame('/data/attributes/appUrl', $respData['errors'][4]['source']['pointer']);
        self::assertSame('/data/relationships/cause/data/id', $respData['errors'][5]['source']['pointer']);
    }
}
