<?php

declare(strict_types=1);

namespace Collectme\Email;

use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\User;

class Reminder1Email implements QueuableEmail
{

    public function prepare(MailQueueItem $item): void
    {
        // TODO: Implement prepare() method.
    }

    public function send(MailQueueItem $item, User $user): void
    {
        // TODO: Implement send() method.
    }
}