<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
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
        #[DBField('sent_at')]
        public ?\DateTime $sent,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @throws CollectmeDBException
     */
    public static function deleteUnsentByGroup(string $groupUuid): void
    {
        foreach (self::findUnsentByGroup($groupUuid) as $item) {
            $item->delete();
        }
    }

    /**
     * @return MailQueueItem[]
     * @throws CollectmeDBException
     */
    public static function findUnsentByGroup(string $groupUuid): array
    {
        global $wpdb;

        $tbl = self::getTableName();

        return self::findByQuery(
            $wpdb->prepare(
                <<<SQL
SELECT * FROM $tbl
WHERE groups_uuid = '%s'
AND sent_at IS NULL
AND deleted_at IS NULL
SQL,
                $groupUuid
            )
        );
    }

    /**
     * @throws CollectmeDBException
     */
    public static function deleteUnsentByGroupAndMsgKey(string $groupUuid, EnumMessageKey $msgKey): void
    {
        foreach (self::findUnsentByGroupAndMsgKey($groupUuid, $msgKey) as $queuedItem) {
            $queuedItem->delete();
        }
    }

    /**
     * @return MailQueueItem[]
     * @throws CollectmeDBException
     */
    public static function findUnsentByGroupAndMsgKey(string $groupUuid, EnumMessageKey $msgKey): array
    {
        global $wpdb;

        $tbl = self::getTableName();

        return self::findByQuery(
            $wpdb->prepare(
                <<<SQL
SELECT * FROM $tbl
WHERE groups_uuid = '%s'
AND sent_at IS NULL
AND msg_key = '%s'
AND deleted_at IS NULL
SQL,
                $groupUuid,
                $msgKey->value,
            )
        );
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