<?php

declare(strict_types=1);

namespace Unit\Misc;

use Collectme\Misc\MailQueueItemProcessor;
use Collectme\Misc\MailQueueProcessor;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumMailQueueItemTrigger;
use Collectme\Model\Entities\EnumMessageKey;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\MailQueueItem;
use PHPUnit\Framework\TestCase;

class MailQueueProcessorTest extends TestCase
{

    public function test_removeCron(): void
    {
        // precondition
        MailQueueProcessor::scheduleCron();

        // test
        MailQueueProcessor::removeCron();
        self::assertFalse(wp_next_scheduled('collectme_send_mails'));
    }

    public function test_scheduleCron(): void
    {
        // precondition
        MailQueueProcessor::removeCron();

        // test
        MailQueueProcessor::scheduleCron();
        self::assertNotFalse(wp_next_scheduled('collectme_send_mails'));

        // cleanup
        MailQueueProcessor::removeCron();
    }

    public function test_processQueue(): void
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

        // make sure we start clean
        foreach (MailQueueItem::findUnsent() as $item) {
            $item->delete();
        }

        $item1 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $item1->save();

        $item2 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $item2->save();

        $itemSent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            date_create('-1 second'),
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $itemSent->save();

        $itemProcessorMock = $this->createMock(MailQueueItemProcessor::class);
        $itemProcessorMock->expects($this->exactly(2))
            ->method('process')
            ->with($this->isInstanceOf(MailQueueItem::class));

        (new MailQueueProcessor($itemProcessorMock))->process();
    }
}
