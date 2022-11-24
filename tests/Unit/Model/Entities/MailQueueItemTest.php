<?php

declare(strict_types=1);

namespace Unit\Model\Entities;

use Collectme\Misc\Settings;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumMailQueueItemTrigger;
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
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $mailQueueItem->save();

        $dbItem = MailQueueItem::get($mailQueueItem->uuid);

        $this->assertSame($group->uuid, $dbItem->groupUuid);
        $this->assertSame(EnumMessageKey::COLLECTION_REMINDER, $dbItem->messageKey);
        $this->assertNotEmpty($dbItem->unsubscribeSecret);
        $this->assertNull($dbItem->sent);
        $this->assertSame($group->uuid, $dbItem->triggerObjUuid);
        $this->assertSame(EnumMailQueueItemTrigger::GROUP, $dbItem->triggerObjType);
        $this->assertNotEmpty($dbItem->created);
        $this->assertNotEmpty($dbItem->updated);
        $this->assertNull($dbItem->deleted);

        $mailQueueItem->sent = date_create('2022-10-13T20:10:15+00:00');
        $mailQueueItem->save();

        $dbItem = MailQueueItem::get($mailQueueItem->uuid);
        $this->assertNotEmpty($dbItem);
    }

    public function test_findUnsent(): void
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

        $unsent1 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsent1->save();

        $unsent2 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsent2->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            date_create(),
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $sent->save();

        $deleted = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $deleted->save()->delete();

        $found = MailQueueItem::findUnsent();
        $foundUuids = array_map(
            static fn(MailQueueItem $item) => $item->uuid,
            $found
        );

        $this->assertContains($unsent1->uuid, $foundUuids);
        $this->assertContains($unsent2->uuid, $foundUuids);
        $this->assertNotContains($sent->uuid, $foundUuids);
        $this->assertNotContains($deleted->uuid, $foundUuids);
    }

    public function test_findUnsentByGroup(): void
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

        $otherGroup = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $otherGroup->save();

        $unsent1 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsent1->save();

        $unsent2 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsent2->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            date_create(),
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $sent->save();

        $unsentOtherGroup = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $otherGroup->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsentOtherGroup->save();

        $deleted = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $deleted->save();
        $deleted->delete();

        $found = MailQueueItem::findUnsentByGroup($group->uuid);
        $foundUuids = array_map(
            static fn(MailQueueItem $item) => $item->uuid,
            $found
        );

        $this->assertContains($unsent1->uuid, $foundUuids);
        $this->assertContains($unsent2->uuid, $foundUuids);
        $this->assertNotContains($sent->uuid, $foundUuids);
        $this->assertNotContains($unsentOtherGroup->uuid, $foundUuids);
        $this->assertNotContains($deleted->uuid, $foundUuids);
    }

    public function test_deleteUnsentByGroup(): void
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

        $otherGroup = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $otherGroup->save();

        $unsent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsent->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            date_create(),
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $sent->save();

        $unsentOtherGroup = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $otherGroup->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsentOtherGroup->save();

        MailQueueItem::deleteUnsentByGroup($group->uuid);

        $this->assertNotNull(MailQueueItem::get($unsent->uuid, true)->deleted);
        $this->assertNull(MailQueueItem::get($sent->uuid, true)->deleted);
        $this->assertNull(MailQueueItem::get($unsentOtherGroup->uuid, true)->deleted);
    }

    public function test_findUnsentByGroupAndMsgKey(): void
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

        $otherGroup = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $otherGroup->save();

        $unsent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsent->save();

        $unsentOtherMsgKey = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsentOtherMsgKey->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            date_create(),
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $sent->save();

        $unsentOtherGroup = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $otherGroup->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsentOtherGroup->save();

        $deleted = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $deleted->save();
        $deleted->delete();

        $found = MailQueueItem::findUnsentByGroupAndMsgKey(
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER
        );
        $foundUuids = array_map(
            static fn(MailQueueItem $item) => $item->uuid,
            $found
        );

        $this->assertContains($unsent->uuid, $foundUuids);
        $this->assertNotContains($unsentOtherMsgKey->uuid, $foundUuids);
        $this->assertNotContains($sent->uuid, $foundUuids);
        $this->assertNotContains($unsentOtherGroup->uuid, $foundUuids);
        $this->assertNotContains($deleted->uuid, $foundUuids);
    }

    public function test_deleteUnsentByGroupAndMsgKey(): void
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

        $otherGroup = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $otherGroup->save();

        $unsent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsent->save();

        $unsentOtherMsgKey = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsentOtherMsgKey->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            date_create(),
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $sent->save();

        $unsentOtherGroup = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $otherGroup->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsentOtherGroup->save();

        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER
        );

        $this->assertNotNull(MailQueueItem::get($unsent->uuid, true)->deleted);
        $this->assertNull(MailQueueItem::get($unsentOtherMsgKey->uuid, true)->deleted);
        $this->assertNull(MailQueueItem::get($sent->uuid, true)->deleted);
        $this->assertNull(MailQueueItem::get($unsentOtherGroup->uuid, true)->deleted);
    }

    public function test_deleteUnsentByGroupAndMsgKeys(): void
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

        $otherGroup = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $otherGroup->save();

        $unsent1 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsent1->save();

        $unsent2 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsent2->save();

        $unsentOtherMsgKey = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsentOtherMsgKey->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            date_create(),
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $sent->save();

        $unsentOtherGroup = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $otherGroup->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $unsentOtherGroup->save();

        MailQueueItem::deleteUnsentByGroupAndMsgKeys(
            $group->uuid,
            [
                EnumMessageKey::COLLECTION_REMINDER,
            ]
        );

        $this->assertNotNull(MailQueueItem::get($unsent1->uuid, true)->deleted);
        $this->assertNotNull(MailQueueItem::get($unsent2->uuid, true)->deleted);
        $this->assertNull(MailQueueItem::get($unsentOtherMsgKey->uuid, true)->deleted);
        $this->assertNull(MailQueueItem::get($sent->uuid, true)->deleted);
        $this->assertNull(MailQueueItem::get($unsentOtherGroup->uuid, true)->deleted);
    }

    public function test_isEnabled(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        Settings::getInstance()->setMailDelays([
            EnumMessageKey::OBJECTIVE_CHANGE->value => new \DateInterval('P1D'),
            EnumMessageKey::COLLECTION_REMINDER->value => null,
        ], $cause->uuid);

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $due = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
            date_create('-2 days'),
        );

        $notYetDue = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
            date_create('+1 hour'),
        );

        $disabled = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
            date_create('-2 days'),
        );

        $this->assertTrue($due->isEnabled());
        $this->assertTrue($notYetDue->isEnabled());
        $this->assertFalse($disabled->isEnabled());
    }

    public function test_isDueForSending(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        Settings::getInstance()->setMailDelays([
            EnumMessageKey::OBJECTIVE_CHANGE->value => new \DateInterval('P1D'),
            EnumMessageKey::COLLECTION_REMINDER->value => null,
        ], $cause->uuid);

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $due = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
            date_create('-2 days'),
        );

        $notYetDue = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
            date_create('+1 hour'),
        );

        $disabled = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
            date_create('-2 days'),
        );

        $this->assertTrue($due->isDueForSending());
        $this->assertFalse($notYetDue->isDueForSending());
        $this->assertFalse($disabled->isDueForSending());
    }

    public function test_group(): void
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

        $item = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );

        $this->assertSame($group->uuid, $item->group()->uuid);
    }
}
