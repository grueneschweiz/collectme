<?php /** @noinspection JsonEncodingApiUsageInspection */

declare(strict_types=1);

namespace Unit\Controller;

use Collectme\Controller\UserController;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class UserControllerTest extends TestCase
{
    public function test_getCurrent__success(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::EN,
            true,
            'test: some string',
        );
        $user = $user->save();

        $session = new PersistentSession(
            wp_generate_uuid4(),
            $user->uuid,
            0,
            null,
            'asdf',
            'asdf',
            date_create(),
            null,
            date_create(),
            date_create(),
        );

        $expectedJson = json_encode([
            'data' => [
                'id' => $user->uuid,
                'type' => 'user',
                'attributes' => [
                    'email' => $user->email,
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'lang' => 'e',
                    'created' => $user->created->format(DATE_RFC3339_EXTENDED),
                    'updated' => $user->updated->format(DATE_RFC3339_EXTENDED),
                ]
            ]
        ]);


        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn($session);

        $controller = new UserController($authMock);
        $resp = $controller->getCurrent(new \WP_REST_Request());

        $this->assertEquals(200, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_getCurrent__unauthorized(): void
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

        $controller = new UserController($authMock);
        $resp = $controller->getCurrent(new \WP_REST_Request());

        $this->assertEquals(401, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }

    public function test_getCurrent__notFound(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            'asdf',
            'asdf',
            date_create(),
            null,
            date_create(),
            date_create(),
        );

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
            ->willReturn($session);

        $controller = new UserController($authMock);
        $resp = $controller->getCurrent(new \WP_REST_Request());

        $this->assertEquals(404, $resp->get_status());
        $this->assertJsonStringEqualsJsonString($expectedJson, wp_json_encode($resp->jsonSerialize()));
    }
}
