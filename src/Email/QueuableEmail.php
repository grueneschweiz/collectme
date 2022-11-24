<?php

declare(strict_types=1);

namespace Collectme\Email;

use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\User;

interface QueuableEmail
{
    public function prepareFor(MailQueueItem $item): void;

    public function shouldBeSent(): bool;

    public function setUser(User $user): void;

    public function afterSent(): void;
}