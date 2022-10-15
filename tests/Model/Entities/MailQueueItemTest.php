<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumMessageKey;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\MailQueueItem;
use PHPUnit\Framework\TestCase;

class MailQueueItemTest extends TestCase
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

        $mailQueueItem = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::GOAL_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $mailQueueItem->save();

        $dbItem = MailQueueItem::get($mailQueueItem->uuid);

        $this->assertSame($group->uuid, $dbItem->groupUuid);
        $this->assertSame(EnumMessageKey::GOAL_ACHIEVED, $dbItem->messageKey);
        $this->assertNotEmpty($dbItem->unsubscribeSecret);
        $this->assertNull($dbItem->sent);
        $this->assertNotEmpty($dbItem->created);
        $this->assertNotEmpty($dbItem->updated);
        $this->assertNull($dbItem->deleted);

        $mailQueueItem->sent = date_create('2022-10-13T20:10:15+00:00');
        $mailQueueItem->save();

        $dbItem = MailQueueItem::get($mailQueueItem->uuid);
        $this->assertNotEmpty($dbItem);
    }
}
