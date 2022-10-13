<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\DateProperty;
use Collectme\Model\Entity;

#[DBTable('mails')]
class MailQueueItem extends Entity
{
    public function __construct(
        ?string $uuid,

        #[DBField('groups_uuid')]
        public string $groupUuid,

        #[DBField('msg_key')]
        public EnumMessageKey $messageKey,

        #[DBField('unsubscribe_secret')]
        public string $unsubscribeSecret,

        #[DateProperty]
        #[DBField]
        public ?\DateTime $sent,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * Convert message key string to enum
     *
     * @param string $key
     * @return EnumMessageKey
     * @noinspection PhpUnused
     */
    protected static function _convertFromMessageKey(string $key): EnumMessageKey
    {
        return EnumMessageKey::from($key);
    }

    /**
     * Return message key enum as string
     *
     * @return string
     * @noinspection PhpUnused
     */
    protected function _convertToMessageKey(): string
    {
        return $this->messageKey->value;
    }
}