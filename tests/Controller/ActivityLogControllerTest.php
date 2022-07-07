<?php

declare(strict_types=1);

namespace Controller;

use Collectme\Controller\ActivityLogController;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\Group;
use PHPUnit\Framework\TestCase;

class ActivityLogControllerTest extends TestCase
{

    public function test_index__success(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $group1 = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group1->save();
        $group2 = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group2->save();

        for ($i = 10; $i > -5; $i--) {
            $log = new ActivityLog(
                null,
                EnumActivityType::PERSONAL_SIGNATURE,
                $i,
                $cause->uuid,
                $i % 2 === 0 ? $group1->uuid : $group2->uuid,
            );
            $log->save();
        }

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $cause->uuid);
        $request->set_param('page', ['cursor' => $log->uuid, 'points' => 'last']);
        $request->set_param('filter', ['count' => 'gt(0)']);

        $controller = new ActivityLogController($authMock);
        $response = $controller->index($request);

        $resp = json_decode(wp_json_encode($response->get_data()), true);

        $this->assertEquals(200, $response->get_status());
        $this->assertCount(10, $resp['data']);
        $this->assertSame(1, $resp['data'][0]['attributes']['count']);
        $this->assertSame(10, $resp['data'][9]['attributes']['count']);

        $this->assertCount(2, $resp['included']);
        $this->assertContains($group1->uuid, [$resp['included'][0]['id'],$resp['included'][1]['id']]);
        $this->assertContains($group2->uuid, [$resp['included'][0]['id'],$resp['included'][1]['id']]);

        $this->assertCount(4, $resp['links']);

        $baseUrl = "http://example.org/index.php?rest_route=" . urlencode("/collectme/v1/causes/$cause->uuid/activities");

        $this->assertSame(
            $baseUrl . "&filter[count]=gt(0)",
            $resp['links']['first']
        );

        $this->assertNull($resp['links']['last']);

        $this->assertSame(
            $baseUrl . "&page[cursor]={$resp['data'][0]['id']}&page[points]=first&filter[count]=gt(0)",
            $resp['links']['prev']
        );

        $this->assertSame(
            $baseUrl . "&page[cursor]={$resp['data'][9]['id']}&page[points]=last&filter[count]=gt(0)",
            $resp['links']['next']
        );
    }

    public function test_index__notAuthenticated(): void
    {
        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $request = new \WP_REST_Request();
        $controller = new ActivityLogController($authMock);
        $response = $controller->index($request);
        $this->assertEquals(401, $response->get_status());
    }

    public function test_index__noCursor(): void
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

        for ($i = 12; $i > -5; $i--) {
            $log = new ActivityLog(
                null,
                EnumActivityType::PERSONAL_SIGNATURE,
                $i,
                $cause->uuid,
                $group->uuid,
            );
            $log->save();
        }

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $cause->uuid);
        $request->set_param('filter[count]', 'gt(0)');

        $controller = new ActivityLogController($authMock);
        $response = $controller->index($request);

        $resp = json_decode(wp_json_encode($response->get_data()), true);

        $this->assertEquals(200, $response->get_status());
        $this->assertCount(10, $resp['data']);
    }

    public function test_index__nonExistentCursor(): void
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

        for ($i = 12; $i > -5; $i--) {
            $log = new ActivityLog(
                null,
                EnumActivityType::PERSONAL_SIGNATURE,
                $i,
                $cause->uuid,
                $group->uuid,
            );
            $log->save();
        }

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $cause->uuid);
        $request->set_param('page', ['cursor' => wp_generate_uuid4()]);
        $request->set_param('filter', ['count' => 'gt(0)']);

        $controller = new ActivityLogController($authMock);
        $response = $controller->index($request);

        $resp = json_decode(wp_json_encode($response->get_data()), true);

        $this->assertEquals(200, $response->get_status());
        $this->assertCount(0, $resp['data']);
        $this->assertNull($resp['links']['prev']);
        $this->assertNull($resp['links']['next']);
    }

    public function test_index__nonExistentCause(): void
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

        for ($i = 12; $i > -5; $i--) {
            $log = new ActivityLog(
                null,
                EnumActivityType::PERSONAL_SIGNATURE,
                $i,
                $cause->uuid,
                $group->uuid,
            );
            $log->save();
        }

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', wp_generate_uuid4());
        $request->set_param('page', ['cursor' => wp_generate_uuid4()]);
        $request->set_param('filter', ['count' => 'gt(0)']);

        $controller = new ActivityLogController($authMock);
        $response = $controller->index($request);

        $resp = json_decode(wp_json_encode($response->get_data()), true);

        $this->assertEquals(200, $response->get_status());
        $this->assertCount(0, $resp['data']);
        $this->assertNull($resp['links']['prev']);
        $this->assertNull($resp['links']['next']);
    }

    public function test_index__filterGt10(): void
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

        for ($i = 12; $i > -5; $i--) {
            $log = new ActivityLog(
                null,
                EnumActivityType::PERSONAL_SIGNATURE,
                $i,
                $cause->uuid,
                $group->uuid,
            );
            $log->save();
        }

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $cause->uuid);
        $request->set_param('filter', ['count' => 'gt(10)']);

        $controller = new ActivityLogController($authMock);
        $response = $controller->index($request);

        $resp = json_decode(wp_json_encode($response->get_data()), true);

        $this->assertEquals(200, $response->get_status());
        $this->assertCount(2, $resp['data']);
    }

    public function test_index__noFilter(): void
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

        for ($i = 10; $i > -5; $i--) {
            $log = new ActivityLog(
                null,
                EnumActivityType::PERSONAL_SIGNATURE,
                $i,
                $cause->uuid,
                $group->uuid,
            );
            $log->save();
        }

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $cause->uuid);

        $controller = new ActivityLogController($authMock);
        $response = $controller->index($request);

        $resp = json_decode(wp_json_encode($response->get_data()), true);

        $this->assertEquals(200, $response->get_status());
        $this->assertCount(10, $resp['data']);
        $this->assertEquals(-4, $resp['data'][0]['attributes']['count']);
    }
}
