<?php

declare(strict_types=1);

namespace Unit\Controller;

use Collectme\Controller\StatController;
use Collectme\Misc\Settings;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class StatControllerTest extends TestCase
{

    public function test_index(): void
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

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            10,
            $cause->uuid,
            $group->uuid
        );
        $log->save();

        $entry1 = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            10,
            $log->uuid
        );
        $entry1->save();

        $objective1 = new Objective(
            null,
            20,
            $group->uuid,
            'Newsletter 220401'
        );
        $objective1->save();

        Settings::getInstance()->setSignatureSettings([
            'objective' => 1000,
            'offset' => 90,
        ], $cause->uuid);

        Settings::getInstance()->setPledgeSettings([
            'objective' => 1000,
            'offset' => 80,
        ], $cause->uuid);

        $request = new \WP_REST_Request();
        $request->set_param('uuid', $cause->uuid);

        $controller = new StatController();

        $result = $controller->index($request);
        $data = json_decode(json_encode($result->get_data()), true);

        $this->assertEquals(200, $result->get_status());

        $this->assertEquals('overview-'.$cause->uuid, $data['data']['id']);
        $this->assertEquals('stat', $data['data']['type']);
        $this->assertEquals(0.1, $data['data']['attributes']['pledged']);
        $this->assertEquals(0.1, $data['data']['attributes']['registered']);
        $this->assertNotNull($data['data']['attributes']['updated']);
        $this->assertEquals('cause', $data['data']['relationships']['cause']['data']['type']);
        $this->assertEquals($cause->uuid, $data['data']['relationships']['cause']['data']['id']);
    }
}
