<?php

declare(strict_types=1);

namespace Collectme\Email;

use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\User;

interface QueuableEmail
{
    public function prepare(MailQueueItem $item): void;
    public function send(MailQueueItem $item, User $user): void;
}