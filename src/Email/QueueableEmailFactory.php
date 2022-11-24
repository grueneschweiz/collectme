<?php

declare(strict_types=1);

namespace Collectme\Email;

use Collectme\Model\Entities\EnumMessageKey;
use DI\DependencyException;
use DI\NotFoundException;

class QueueableEmailFactory
{
    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function get(EnumMessageKey $key): QueuableEmail & Mailable
    {
        $emailClass = match ($key) {
            EnumMessageKey::COLLECTION_REMINDER => EmailCollectionReminder::class,
            EnumMessageKey::OBJECTIVE_CHANGE => EmailObjectiveChange::class,
        };

        return collectme_get_container()->get($emailClass);
    }
}