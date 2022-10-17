<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Settings;
use Collectme\Misc\Util;
use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\DateProperty;
use Collectme\Model\Entity;

#[DBTable('mails')]
class MailQueueItem extends Entity
{
    private null|\DateInterval $delay;
    private Group $group;

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
     * @return MailQueueItem[]
     * @throws CollectmeDBException
     */
    public static function findUnsent(): array
    {
        $tbl = self::getTableName();

        return self::findByQuery(
            <<<SQL
SELECT * FROM $tbl
WHERE sent_at IS NULL
AND deleted_at IS NULL
SQL
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
     * Is sending of this message type (messageKey) enabled in the settings?
     */
    public function isEnabled(): bool
    {
        return null !== $this->getDelay();
    }

    /**
     * The delay after which the mail should be sent, configured in settings.
     */
    private function getDelay(): null|\DateInterval
    {
        if (!isset($this->delay)) {
            try {
                $causeUuid = $this->group()->causeUuid;
                $this->delay = Settings::getInstance()
                    ->getMailDelays($causeUuid)[$this->messageKey->value];
            } catch (CollectmeDBException) {
                $this->delay = null;
            }
        }

        return $this->delay;
    }

    /**
     * @throws CollectmeDBException
     */
    public function group(): Group
    {
        if (!isset($this->group)) {
            $this->group = Group::get($this->groupUuid);
        }

        return $this->group;
    }

    /**
     * Are we already past the delay after which the mail should be sent?
     */
    public function isDueForSending(): bool
    {
        $delay = $this->getDelay();

        if (null === $delay) {
            return false;
        }

        $now = date_create('now', Util::getTimeZone());
        return $this->created->add($delay) <= $now;
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