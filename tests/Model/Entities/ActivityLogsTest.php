<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\Group;
use PHPUnit\Framework\TestCase;

class ActivityLogsTest extends TestCase
{
    public function test_get(): void
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

        $log = new ActivityLog(
            null,
            EnumActivityType::PLEDGE,
            500,
            $cause->uuid,
            $group->uuid,
        );
        $log->save();

        $log = ActivityLog::get($log->uuid);

        $this->assertSame(EnumActivityType::PLEDGE, $log->type);
        $this->assertSame(500, $log->count);
        $this->assertSame($cause->uuid, $log->causeUuid);
        $this->assertSame($group->uuid, $log->groupUuid);
    }

    public function test_save(): void
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

        $log = new ActivityLog(
            null,
            EnumActivityType::PLEDGE,
            500,
            $cause->uuid,
            $group->uuid,
        );
        $log->save();

        $this->assertNotEmpty($log->uuid);
    }

    public function test_toApiModel(): void
    {
        $log = new ActivityLog(
            wp_generate_uuid4(),
            EnumActivityType::PLEDGE,
            500,
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            date_create('2022-06-28T17:10:15+00:00'),
            date_create('2022-06-28T17:10:16+00:00'),
            date_create('2022-06-28T17:10:17+00:00'),
        );

        $apiModel = $log->toApiModel();

        $this->assertEqualsCanonicalizing(
            [
                'data' => [
                    'id' => $log->groupUuid,
                    'type' => 'group'
                ]
            ],
            $apiModel->toArray()['relationships']['group']
        );
        $this->assertEqualsCanonicalizing(
            [
                'data' => [
                    'id' => $log->causeUuid,
                    'type' => 'cause'
                ]
            ],
            $apiModel->toArray()['relationships']['cause']
        );

        $data = $apiModel->toArray();
        unset($data['relationships']);

        $this->assertEqualsCanonicalizing(
            [
                'id' => $log->uuid,
                'type' => 'activity',
                'attributes' => [
                    'type' => 'pledge',
                    'count' => 500,
                    'created' => date_create('2022-06-28T17:10:15+00:00')->format(DATE_RFC3339_EXTENDED),
                    'updated' => date_create('2022-06-28T17:10:16+00:00')->format(DATE_RFC3339_EXTENDED),
                ],
            ],
            $data
        );
    }
}
