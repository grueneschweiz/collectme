<?php

declare(strict_types=1);

namespace Misc;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumMailQueueItemTrigger;
use Collectme\Model\Entities\EnumMessageKey;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class MailSchedulerTest extends TestCase
{
    public function test_groupDeleted(): void
    {
        $group = $this->getGroup(EnumGroupType::PERSON);
        $queueItem = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $queueItem->save();

        $otherGroup = $this->getGroup(EnumGroupType::PERSON);
        $otherQueueItem = new MailQueueItem(
            null,
            $otherGroup->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $otherGroup->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $otherQueueItem->save();

        $group->delete();

        $this->assertNotNull(MailQueueItem::get($otherQueueItem->uuid));

        $this->expectException(CollectmeDBException::class);
        MailQueueItem::get($queueItem->uuid);
    }

    private function getGroup(EnumGroupType $type): Group
    {
        $group = new Group(
            null,
            'test_mail_scheduler_' . wp_generate_password(),
            $type,
            $this->getCause()->uuid,
            false,
        );
        return $group->save();
    }

    private function getCause(): Cause
    {
        $cause = new Cause(
            null,
            'test_mail_scheduler_' . wp_generate_password(),
        );
        return $cause->save();
    }

    public function test_groupUpdated(): void
    {
        $group1 = $this->getGroup(EnumGroupType::PERSON);
        $group2 = $this->getGroup(EnumGroupType::PERSON);
        $queueItem1 = new MailQueueItem(
            null,
            $group1->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group1->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $queueItem1->save();
        $queueItem2 = new MailQueueItem(
            null,
            $group2->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group2->uuid,
            EnumMailQueueItemTrigger::GROUP,
        );
        $queueItem2->save();

        $group1->type = EnumGroupType::ORGANIZATION;
        $group1->save();

        $group2->name = 'test_mail_scheduler_changed_' . wp_generate_password();
        $group2->save();

        $this->assertNotNull(MailQueueItem::get($queueItem2->uuid));

        $this->expectException(CollectmeDBException::class);
        MailQueueItem::get($queueItem1->uuid);
    }

    public function test_groupCreated(): void
    {
        $groupPerson = $this->getGroup(EnumGroupType::PERSON);
        $groupOrganization = $this->getGroup(EnumGroupType::ORGANIZATION);

        $queueItemsPerson = MailQueueItem::findUnsentByGroup($groupPerson->uuid);
        $queueItemsOrganization = MailQueueItem::findUnsentByGroup($groupOrganization->uuid);

        $this->assertCount(1, $queueItemsPerson);
        $this->assertCount(0, $queueItemsOrganization);
        $this->assertSame(EnumMessageKey::COLLECTION_REMINDER, $queueItemsPerson[0]->messageKey);
    }

    public function test_objectiveDeleted(): void
    {
        $objective = $this->getObjective();
        MailQueueItem::deleteUnsentByGroupAndMsgKey($objective->groupUuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $objective->delete();
        $this->assertCount(0, MailQueueItem::findUnsentByGroupAndMsgKey($objective->groupUuid, EnumMessageKey::OBJECTIVE_CHANGE));
    }

    private function getObjective(): Objective
    {
        $objective = new Objective(
            null,
            100,
            $this->getGroup(EnumGroupType::PERSON)->uuid,
            'test mail scheduler'
        );
        return $objective->save();
    }

    public function test_objectiveCreated(): void
    {
        $this->getObjective();
        $objective = $this->getObjective();
        $queueItems = MailQueueItem::findUnsentByGroupAndMsgKey($objective->groupUuid, EnumMessageKey::OBJECTIVE_CHANGE);
        $this->assertCount(1, $queueItems);
    }

    public function test_objectiveUpdated__groupChanged(): void
    {
        $objective = $this->getObjective();
        $oldGroupUuid = $objective->groupUuid;
        $newGroupUuid = $this->getGroup(EnumGroupType::PERSON)->uuid;
        $objective->groupUuid = $newGroupUuid;
        $objective->save();

        $this->assertCount(
            0,
            MailQueueItem::findUnsentByGroupAndMsgKey($oldGroupUuid, EnumMessageKey::OBJECTIVE_CHANGE)
        );
        $this->assertCount(
            1,
            MailQueueItem::findUnsentByGroupAndMsgKey($newGroupUuid, EnumMessageKey::OBJECTIVE_CHANGE)
        );
    }

    public function test_objectiveUpdated__objectiveChanged(): void
    {
        $objective = $this->getObjective();
        MailQueueItem::deleteUnsentByGroupAndMsgKey($objective->groupUuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $objective->objective++;
        $objective->save();

        $this->assertCount(
            1,
            MailQueueItem::findUnsentByGroupAndMsgKey($objective->groupUuid, EnumMessageKey::OBJECTIVE_CHANGE)
        );
    }

    public function test_signatureEntryChange_created(): void
    {
        $group = $this->getGroup(EnumGroupType::PERSON);
        MailQueueItem::deleteUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::COLLECTION_REMINDER);

        $this->getSignatureEntry($group);
        $this->getSignatureEntry($group);

        $reminders = MailQueueItem::findUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::COLLECTION_REMINDER);

        $this->assertCount(1, $reminders);
        $this->assertSame(EnumMessageKey::COLLECTION_REMINDER, $reminders[0]->messageKey);
    }

    public function test_signatureEntryChange_deleted(): void
    {
        $group = $this->getGroup(EnumGroupType::PERSON);
        MailQueueItem::deleteUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::COLLECTION_REMINDER);

        $entry = $this->getSignatureEntry($group);
        $entry->delete();

        $reminders = MailQueueItem::findUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::COLLECTION_REMINDER);

        $this->assertCount(1, $reminders);
        $this->assertSame(EnumMessageKey::COLLECTION_REMINDER, $reminders[0]->messageKey);
    }

    public function test_signatureEntryChange_updateCount(): void
    {
        $group = $this->getGroup(EnumGroupType::PERSON);
        MailQueueItem::deleteUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::COLLECTION_REMINDER);

        $this->getSignatureEntry($group);
        $entry = $this->getSignatureEntry($group);
        $entry->count = -$entry->count;
        $entry->save();

        $reminders = MailQueueItem::findUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::COLLECTION_REMINDER);

        $this->assertCount(1, $reminders);
        $this->assertSame(EnumMessageKey::COLLECTION_REMINDER, $reminders[0]->messageKey);
    }

    public function test_signatureEntryChange_objectiveAchieved(): void
    {
        $group = $this->getGroup(EnumGroupType::PERSON);

        $objective = new Objective(
            null,
            100,
            $group->uuid,
            'test mail scheduler'
        );
        $objective->save();
        MailQueueItem::deleteUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $entry1 = $this->getSignatureEntry($group);
        $entry2 = new SignatureEntry(
            null,
            $group->uuid,
            $entry1->userUuid,
            100 - $entry1->count,
            $entry1->activityLogUuid,
        );
        $entry2->save();

        $mails = MailQueueItem::findUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $this->assertCount(1, $mails);
        $this->assertSame(EnumMessageKey::OBJECTIVE_CHANGE, $mails[0]->messageKey);
    }

    public function test_signatureEntryChange_objectiveAchievedFinal(): void
    {
        $group = $this->getGroup(EnumGroupType::PERSON);

        $objective = new Objective(
            null,
            1000,
            $group->uuid,
            'test mail scheduler'
        );
        $objective->save();
        MailQueueItem::deleteUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $entry1 = $this->getSignatureEntry($group);
        $entry2 = new SignatureEntry(
            null,
            $group->uuid,
            $entry1->userUuid,
            1000 - $entry1->count,
            $entry1->activityLogUuid,
        );
        $entry2->save();

        $final = MailQueueItem::findUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $this->assertCount(1, $final);
        $this->assertSame(EnumMessageKey::OBJECTIVE_CHANGE, $final[0]->messageKey);
    }

    public function test_signatureEntryChange_objectiveNotAchieved(): void
    {
        $group = $this->getGroup(EnumGroupType::PERSON);

        $objective = new Objective(
            null,
            100,
            $group->uuid,
            'test mail scheduler'
        );
        $objective->save();
        MailQueueItem::deleteUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $entry1 = $this->getSignatureEntry($group);
        $entry2 = new SignatureEntry(
            null,
            $group->uuid,
            $entry1->userUuid,
            1,
            $entry1->activityLogUuid,
        );
        $entry2->save();

        $mails = MailQueueItem::findUnsentByGroupAndMsgKey($group->uuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $this->assertCount(0, $mails);
    }

    private function getSignatureEntry(Group $group): SignatureEntry
    {
        $user = new User(
            null,
            wp_generate_password() . '@example.test',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'MailSchedulerTest',
        );
        $user->save();

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            11,
            $group->causeUuid,
            $group->uuid,
        );
        $log->save();

        $entry = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            11,
            $log->uuid,
        );
        return $entry->save();
    }
}
