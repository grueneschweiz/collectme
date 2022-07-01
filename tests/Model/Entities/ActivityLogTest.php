<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\Group;
use Collectme\Model\EnumPaginationCursorPointsTo;
use Collectme\Model\EnumPaginationOrder;
use Collectme\Model\Filter;
use Collectme\Model\Paginator;
use PHPUnit\Framework\TestCase;

class ActivityLogTest extends TestCase
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

    public function test_findByCause(): void
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
                EnumActivityType::PLEDGE,
                $i,
                $cause->uuid,
                $group->uuid,
            );
            $log->save();
        }

        $filter = new Filter('count', 0, '>');

        // DESC

        $paginatorStart = new Paginator(2, null, EnumPaginationCursorPointsTo::LAST, EnumPaginationOrder::DESC);
        $page1 = ActivityLog::findByCause($cause->uuid, $paginatorStart, $filter);
        $this->assertCount(2, $page1);
        $this->assertSame(1, $page1[0]->count);
        $this->assertSame(2, $page1[1]->count);

        $paginatorStart = new Paginator(2, $page1[1]->uuid, EnumPaginationCursorPointsTo::LAST, EnumPaginationOrder::DESC);
        $page2 = ActivityLog::findByCause($cause->uuid, $paginatorStart, $filter);
        $this->assertCount(2, $page2);
        $this->assertSame(3, $page2[0]->count);
        $this->assertSame(4, $page2[1]->count);

        $paginatorStart = new Paginator(2, $page2[0]->uuid, EnumPaginationCursorPointsTo::FIRST, EnumPaginationOrder::DESC);
        $page1again = ActivityLog::findByCause($cause->uuid, $paginatorStart, $filter);
        $this->assertCount(2, $page1again);
        $this->assertSame(1, $page1again[0]->count);
        $this->assertSame(2, $page1again[1]->count);

        $paginatorStart = new Paginator(2, $page1again[0]->uuid, EnumPaginationCursorPointsTo::FIRST, EnumPaginationOrder::DESC);
        $page0 = ActivityLog::findByCause($cause->uuid, $paginatorStart, $filter);
        $this->assertCount(0, $page0);

        // ASC

        $paginatorStart = new Paginator(2, null, EnumPaginationCursorPointsTo::LAST, EnumPaginationOrder::ASC);
        $page1 = ActivityLog::findByCause($cause->uuid, $paginatorStart, $filter);
        $this->assertCount(2, $page1);
        $this->assertSame(10, $page1[0]->count);
        $this->assertSame(9, $page1[1]->count);

        $paginatorStart = new Paginator(2, $page1[1]->uuid, EnumPaginationCursorPointsTo::LAST, EnumPaginationOrder::ASC);
        $page2 = ActivityLog::findByCause($cause->uuid, $paginatorStart, $filter);
        $this->assertCount(2, $page2);
        $this->assertSame(8, $page2[0]->count);
        $this->assertSame(7, $page2[1]->count);

        $paginatorStart = new Paginator(2, $page2[0]->uuid, EnumPaginationCursorPointsTo::FIRST, EnumPaginationOrder::ASC);
        $page1again = ActivityLog::findByCause($cause->uuid, $paginatorStart, $filter);
        $this->assertCount(2, $page1again);
        $this->assertSame(10, $page1again[0]->count);
        $this->assertSame(9, $page1again[1]->count);

        $paginatorStart = new Paginator(2, $page1again[0]->uuid, EnumPaginationCursorPointsTo::FIRST, EnumPaginationOrder::ASC);
        $page0 = ActivityLog::findByCause($cause->uuid, $paginatorStart, $filter);
        $this->assertCount(0, $page0);

    }
}
