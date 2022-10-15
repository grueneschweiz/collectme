<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Settings;
use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\Entity;
use Collectme\Model\JsonApi\ApiModelType;

#[ApiModelType('cause')]
#[DBTable('causes')]
class Cause extends Entity
{
    public function __construct(
        ?string $uuid,

        #[DBField]
        public string $name,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @return User[]
     * @throws CollectmeDBException
     */
    public static function findByUser(string $userUuid): array
    {
        global $wpdb;

        $causeTbl = self::getTableName();
        $userCausesTbl = UserCause::getTableName();

        $query = $wpdb->prepare(
            "SELECT {$causeTbl}.* FROM {$causeTbl}" .
            " INNER JOIN {$userCausesTbl} ON {$causeTbl}.uuid = {$userCausesTbl}.causes_uuid" .
            " WHERE {$userCausesTbl}.users_uuid = '%s'" .
            " AND {$causeTbl}.deleted_at IS NULL" .
            " AND {$userCausesTbl}.deleted_at IS NULL",
            $userUuid
        );

        return self::findByQuery($query);
    }

    /**
     * @return Cause[]
     * @throws CollectmeDBException
     */
    public function users(): array
    {
        return User::findByCause($this->uuid);
    }

    /**
     * @return Cause[]
     * @throws CollectmeDBException
     */
    public static function findActive(): array
    {
        $now = date_create();
        $settings = Settings::getInstance();

        return array_filter(
            self::findAll(),
            static function (Cause $cause) use ($now, $settings) {
                ['start' => $start, 'stop' => $stop] = $settings->getTimings($cause->uuid);

                // missing start / stop dates are considered active
                return !($start && $start > $now)
                    && !($stop && $stop < $now);
            }
        );
    }

    /**
     * @return Cause[]
     * @throws CollectmeDBException
     */
    public static function findAll(): array
    {
        $causeTbl = self::getTableName();
        return self::findByQuery("SELECT * FROM {$causeTbl} WHERE deleted_at IS NULL");
    }
}