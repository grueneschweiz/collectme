<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Email\NoCollectEmail;
use Collectme\Email\ObjectiveAchievedEmail;
use Collectme\Email\ObjectiveAchievedFinalEmail;
use Collectme\Email\ObjectiveAddedEmail;
use Collectme\Email\Reminder1Email;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\EnumMessageKey;
use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\User;

class MailQueueProcessor
{
    public function __construct(
        private readonly NoCollectEmail $noCollectEmail,
        private readonly Reminder1Email $reminder1Email,
        private readonly ObjectiveAddedEmail $objectiveAddedEmail,
        private readonly ObjectiveAchievedEmail $objectiveAchievedEmail,
        private readonly ObjectiveAchievedFinalEmail $objectiveAchievedFinalEmail,
    ) {
    }

    public static function scheduleCron(): void
    {
        if (!wp_next_scheduled('collectme_send_mails')) {
            wp_schedule_event(time(), 'hourly', 'collectme_send_mails');
        }
    }

    public static function removeCron(): void
    {
        $timestamp = wp_next_scheduled('collectme_send_mails');
        wp_unschedule_event($timestamp, 'collectme_send_mails');
    }

    /**
     * @throws CollectmeDBException
     */
    public function processQueue(): void
    {
        foreach (MailQueueItem::findUnsent() as $item) {
            $this->processItem($item);
        }
    }

    /**
     * @throws CollectmeDBException
     */
    private function processItem(MailQueueItem $item): void
    {
        if ($item->isEnabled()) {
            $item->delete();
            return;
        }

        if (!$item->isDueForSending()) {
            return;
        }

        $email = match ($item->messageKey) {
            EnumMessageKey::NO_COLLECT => $this->noCollectEmail,
            EnumMessageKey::REMINDER_1 => $this->reminder1Email,

            EnumMessageKey::OBJECTIVE_ADDED => $this->objectiveAddedEmail,
            EnumMessageKey::OBJECTIVE_ACHIEVED => $this->objectiveAchievedEmail,
            EnumMessageKey::OBJECTIVE_ACHIEVED_FINAL => $this->objectiveAchievedFinalEmail,
        };

        $email->prepare($item);

        $users = User::findWithWritePermissionForGroup($item->groupUuid);
        foreach ($users as $user) {
            if (!$user->mailPermission) {
                continue;
            }

            $email->send($item, $user);
        }

        $item->sent = date_create('now', Util::getTimeZone());
        $item->save();
    }
}