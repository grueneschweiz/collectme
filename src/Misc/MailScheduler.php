<?php

declare(strict_types=1);

namespace Collectme\Misc;


use Collectme\Exceptions\CollectmeDBException;
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
            EnumMessageKey::NO_COLLECT,
            wp_generate_password(64, false),
            null,
        );
        $queueItem->save();
    }

    /**
     * @throws CollectmeDBException
     */
    public function objectiveDeleted(Objective $objective): void
    {
        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $objective->groupUuid,
            EnumMessageKey::OBJECTIVE_ADDED
        );
    }

    /**
     * @throws CollectmeDBException
     */
    public function objectiveUpdated(Objective $newObjective, Objective $oldObjective): void
    {
        if ($newObjective->groupUuid !== $oldObjective->groupUuid) {
            MailQueueItem::deleteUnsentByGroupAndMsgKey(
                $oldObjective->groupUuid,
                EnumMessageKey::OBJECTIVE_ADDED
            );
            $this->objectiveCreated($newObjective);
        }

        if ($newObjective->objective !== $oldObjective->objective) {
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
            EnumMessageKey::OBJECTIVE_ADDED
        );
//        TODO: Consider the following lines in the Mailer logic.
//        They can not be treated here as it would lead to buggy
//        behavior if settings were changed during an ongoing
//        campaign.
//
//        TODO: Delete those lines
//        MailQueueItem::deleteUnsentByGroupAndMsgKey(
//            $objective->groupUuid,
//            EnumMessageKey::OBJECTIVE_ACHIEVED
//        );
//        MailQueueItem::deleteUnsentByGroupAndMsgKey(
//            $objective->groupUuid,
//            EnumMessageKey::OBJECTIVE_ACHIEVED_FINAL
//        );

        $queueItem = new MailQueueItem(
            null,
            $objective->groupUuid,
            EnumMessageKey::OBJECTIVE_ADDED,
            wp_generate_password(64, false),
            null,
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

        $this->scheduleReminder($entry, $group);
        $this->scheduleObjectiveMsg($entry, $group);
    }

    /**
     * @throws CollectmeDBException
     */
    private function scheduleReminder(SignatureEntry $entry, Group $group): void
    {
        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $entry->groupUuid,
            EnumMessageKey::NO_COLLECT
        );
        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $entry->groupUuid,
            EnumMessageKey::REMINDER_1
        );

        $msg = $group->signatures() > 0
            ? EnumMessageKey::REMINDER_1
            : EnumMessageKey::NO_COLLECT;

        $queueItem = new MailQueueItem(
            null,
            $entry->groupUuid,
            $msg,
            wp_generate_password(64, false),
            null,
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
            EnumMessageKey::OBJECTIVE_ACHIEVED
        );
        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $entry->groupUuid,
            EnumMessageKey::OBJECTIVE_ACHIEVED_FINAL
        );

        $objectiveSettings = Settings::getInstance()->getObjectives($group->causeUuid);
        $enabledObjectives = array_filter(
            $objectiveSettings,
            static fn($setting) => $setting['enabled']
        );
        $highestObjective = array_reduce(
            $enabledObjectives,
            static fn($carry, $setting) => $carry['objective'] > $setting['objective'] ? $carry : $setting,
            ['objective' => 0]
        );

        $msg = $objectiveCount < $highestObjective['objective']
            ? EnumMessageKey::OBJECTIVE_ACHIEVED
            : EnumMessageKey::OBJECTIVE_ACHIEVED_FINAL;

        $queueItem = new MailQueueItem(
            null,
            $entry->groupUuid,
            $msg,
            wp_generate_password(64, false),
            null,
        );
        $queueItem->save();
    }
}