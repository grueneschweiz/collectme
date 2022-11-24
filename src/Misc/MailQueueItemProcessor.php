<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Collectme;
use Collectme\Email\QueueableEmailFactory;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\User;
use DI\DependencyException;
use DI\NotFoundException;

class MailQueueItemProcessor
{
    /**
     * Causes cache. Lazily populated.
     *
     * @var Array<string, Cause> [causeUuid => Cause]
     */
    private array $causes;

    public function __construct(
        private readonly Mailer $mailer,
        private readonly QueueableEmailFactory $emailFactory,
    ) {
    }

    /**
     * @throws CollectmeDBException
     * @throws CollectmeException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function process(MailQueueItem $item): void
    {
        try {
            $causeUuid = $item->group()->causeUuid;
            $cause = $this->getCause($causeUuid);
            Collectme::setCauseUuid($causeUuid);
        } catch (CollectmeDBException) {
            $item->delete();
            return;
        }

        if ($cause->isDataRetentionExpired()) {
            $item->delete();
            return;
        }

        if (!$cause->isActive()) {
            return;
        }

        if (!$item->isEnabled()) {
            return;
        }

        if (!$item->isDueForSending()) {
            return;
        }

        $email = $this->emailFactory->get($item->messageKey);
        $email->prepareFor($item);

        if (!$email->shouldBeSent()) {
            return;
        }

        $initialLocale = get_locale();
        $group = $item->group();

        $users = User::findWithWritePermissionForGroup($item->groupUuid);
        foreach ($users as $user) {
            if (!$user->mailPermission) {
                continue;
            }

            $userLocale = Util::determineLocale($user->lang);
            switch_to_locale($userLocale);

            $email->setUser($user);
            $this->mailer->send($email, $group->causeUuid);
        }

        switch_to_locale($initialLocale);

        $item->sent = date_create('now', Util::getTimeZone());
        $item->save();

        $email->afterSent();
    }

    /**
     * @throws CollectmeDBException
     */
    private function getCause(string $causeUuid): Cause
    {
        if (!isset($this->causes[$causeUuid])) {
            $this->causes[$causeUuid] = Cause::get($causeUuid);
        }

        return $this->causes[$causeUuid];
    }
}