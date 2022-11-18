<?php

declare(strict_types=1);

namespace Email;

use Collectme\Email\EmailCollectionReminder;
use Collectme\Email\EmailObjectiveChange;
use Collectme\Email\QueueableEmailFactory;
use Collectme\Model\Entities\EnumMessageKey;
use PHPUnit\Framework\TestCase;

class QueueableEmailFactoryTest extends TestCase
{

    public function test_get(): void
    {
        $factory = new QueueableEmailFactory();

        $reminder = $factory->get(EnumMessageKey::COLLECTION_REMINDER);
        $change = $factory->get(EnumMessageKey::OBJECTIVE_CHANGE);

        self::assertInstanceOf(EmailCollectionReminder::class, $reminder);
        self::assertInstanceOf(EmailObjectiveChange::class, $change);
    }
}
