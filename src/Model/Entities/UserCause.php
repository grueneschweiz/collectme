<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\Entity;


#[DBTable('users_causes')]
class UserCause extends Entity
{
    public function __construct(
        ?string $uuid,

        #[DBField('users_uuid')]
        public string $userUuid,

        #[DBField('causes_uuid')]
        public string $causeUuid,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @throws CollectmeDBException
     */
    public static function getByUserAndCause(string $userUuid, string $causeUuid): self
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM " . self::getTableName() .
            " WHERE users_uuid = '%s'" .
            " AND causes_uuid = '%s'" .
            " AND deleted_at IS NULL",
            $userUuid,
            $causeUuid
        );

        return static::getByQuery($query);
    }
}