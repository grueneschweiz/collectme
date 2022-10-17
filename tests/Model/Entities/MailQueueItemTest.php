<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Misc\Settings;
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
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $mailQueueItem->save();

        $dbItem = MailQueueItem::get($mailQueueItem->uuid);

        $this->assertSame($group->uuid, $dbItem->groupUuid);
        $this->assertSame(EnumMessageKey::OBJECTIVE_ACHIEVED, $dbItem->messageKey);
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
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsent1->save();

        $unsent2 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsent2->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            date_create(),
        );
        $sent->save();

        $deleted = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
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
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsent1->save();

        $unsent2 = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsent2->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            date_create(),
        );
        $sent->save();

        $unsentOtherGroup = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsentOtherGroup->save();

        $deleted = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
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
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsent->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            date_create(),
        );
        $sent->save();

        $unsentOtherGroup = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
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
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsent->save();

        $unsentOtherMsgKey = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::NO_COLLECT,
            wp_generate_password(64, false),
            null,
        );
        $unsentOtherMsgKey->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            date_create(),
        );
        $sent->save();

        $unsentOtherGroup = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsentOtherGroup->save();

        $deleted = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $deleted->save();
        $deleted->delete();

        $found = MailQueueItem::findUnsentByGroupAndMsgKey(
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED
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
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsent->save();

        $unsentOtherMsgKey = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::NO_COLLECT,
            wp_generate_password(64, false),
            null,
        );
        $unsentOtherMsgKey->save();

        $sent = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            date_create(),
        );
        $sent->save();

        $unsentOtherGroup = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED,
            wp_generate_password(64, false),
            null,
        );
        $unsentOtherGroup->save();

        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED
        );

        $this->assertNotNull(MailQueueItem::get($unsent->uuid, true)->deleted);
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
            EnumMessageKey::NO_COLLECT->value => new \DateInterval('P1D'),
            EnumMessageKey::REMINDER_1->value => null,
            EnumMessageKey::OBJECTIVE_ADDED->value => null,
            EnumMessageKey::OBJECTIVE_ACHIEVED->value => null,
            EnumMessageKey::OBJECTIVE_ACHIEVED_FINAL->value => null,
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
            EnumMessageKey::NO_COLLECT,
            wp_generate_password(64, false),
            null,
            date_create('-2 days'),
        );

        $notYetDue = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::NO_COLLECT,
            wp_generate_password(64, false),
            null,
            date_create('+1 hour'),
        );

        $disabled = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ADDED,
            wp_generate_password(64, false),
            null,
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
            EnumMessageKey::NO_COLLECT->value => new \DateInterval('P1D'),
            EnumMessageKey::REMINDER_1->value => null,
            EnumMessageKey::OBJECTIVE_ADDED->value => null,
            EnumMessageKey::OBJECTIVE_ACHIEVED->value => null,
            EnumMessageKey::OBJECTIVE_ACHIEVED_FINAL->value => null,
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
            EnumMessageKey::NO_COLLECT,
            wp_generate_password(64, false),
            null,
            date_create('-2 days'),
        );

        $notYetDue = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::NO_COLLECT,
            wp_generate_password(64, false),
            null,
            date_create('+1 hour'),
        );

        $disabled = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_ADDED,
            wp_generate_password(64, false),
            null,
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
            EnumMessageKey::NO_COLLECT,
            wp_generate_password(64, false),
            null,
        );

        $this->assertSame($group->uuid, $item->group()->uuid);
    }
}
