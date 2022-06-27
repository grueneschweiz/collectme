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
}
