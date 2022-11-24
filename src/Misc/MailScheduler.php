<?php

declare(strict_types=1);

namespace Collectme\Misc;


use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\EnumMailQueueItemTrigger;
use Collectme\Model\Entities\EnumMessageKey;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\SignatureEntry;

class MailScheduler
{
    /**
     * @throws CollectmeDBException
     */
    public function groupDeleted(Group $group): void
    {
        MailQueueItem::deleteUnsentByGroup($group->uuid);
    }

    /**
     * @throws CollectmeDBException
     */
    public function groupUpdated(Group $group): void
    {
        if (!$group->isPersonal()) {
            MailQueueItem::deleteUnsentByGroup($group->uuid);
        }
    }

    /**
     * @throws CollectmeDBException
     */
    public function groupCreated(Group $group): void
    {
        if (!$group->isPersonal()) {
            return;
        }

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
    }

    public function objectiveDeleted(Objective $objective): void
    {
        // nothing to do
    }

    /**
     * @throws CollectmeDBException
     */
    public function objectiveUpdated(Objective $newObjective, Objective $oldObjective): void
    {
        if ($newObjective->groupUuid !== $oldObjective->groupUuid
            || $newObjective->objective !== $oldObjective->objective
        ) {
            MailQueueItem::deleteUnsentByGroupAndMsgKey(
                $oldObjective->groupUuid,
                EnumMessageKey::OBJECTIVE_CHANGE,
            );
            $this->objectiveCreated($newObjective);
        }
    }

    /**
     * @throws CollectmeDBException
     */
    public function objectiveCreated(Objective $objective): void
    {
        if (!Group::get($objective->groupUuid)->isPersonal()) {
            return;
        }

        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $objective->groupUuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        $queueItem = new MailQueueItem(
            null,
            $objective->groupUuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $objective->uuid,
            EnumMailQueueItemTrigger::OBJECTIVE,
        );
        $queueItem->save();
    }

    /**
     * @throws CollectmeDBException
     */
    public function signatureEntryChange(SignatureEntry $entry): void
    {
        $group = Group::get($entry->groupUuid);

        if (!$group->isPersonal()) {
            return;
        }

        $this->scheduleReminder($entry);
        $this->scheduleObjectiveMsg($entry, $group);
    }

    /**
     * @throws CollectmeDBException
     */
    private function scheduleReminder(SignatureEntry $entry): void
    {
        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $entry->groupUuid,
            EnumMessageKey::COLLECTION_REMINDER,
        );

        $queueItem = new MailQueueItem(
            null,
            $entry->groupUuid,
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $entry->uuid,
            EnumMailQueueItemTrigger::SIGNATURE,
        );
        $queueItem->save();
    }

    /**
     * @throws CollectmeDBException
     */
    private function scheduleObjectiveMsg(SignatureEntry $entry, Group $group): void
    {
        $objective = Objective::findHighestOfGroup($entry->groupUuid);
        if (empty($objective)) {
            return;
        }

        $objectiveCount = $objective[0]->objective;
        $signatureCount = $group->signatures();

        if ($signatureCount < $objectiveCount // below objective
            || ($signatureCount - $entry->count) >= $objectiveCount // objective already achieved
            || 0 >= $objectiveCount
        ) {
            return;
        }

        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $entry->groupUuid,
            EnumMessageKey::OBJECTIVE_CHANGE
        );

        $queueItem = new MailQueueItem(
            null,
            $entry->groupUuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $entry->uuid,
            EnumMailQueueItemTrigger::SIGNATURE,
        );
        $queueItem->save();
    }
}