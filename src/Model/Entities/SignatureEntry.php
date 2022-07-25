<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\Entity;
use Collectme\Model\JsonApi\ApiModelAttribute;
use Collectme\Model\JsonApi\ApiModelRelationship;
use Collectme\Model\JsonApi\ApiModelType;

#[DBTable('signatures')]
#[ApiModelType('signature')]
class SignatureEntry extends Entity
{
    public function __construct(
        ?string $uuid,

        #[ApiModelRelationship(Group::class)]
        #[DBField('collected_by_groups_uuid')]
        public string $groupUuid,

        #[ApiModelRelationship(User::class)]
        #[DBField('entered_by_users_uuid')]
        public string $userUuid,

        #[ApiModelAttribute]
        #[DBField]
        public int $count,

        #[DBField('activity_logs_uuid')]
        #[ApiModelRelationship(ActivityLog::class)]
        public string $activityLogUuid,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @throws CollectmeDBException
     */
    public static function totalByCauseAndType(string $causeUuid, EnumGroupType $type): int
    {
        global $wpdb;

        $signaturesTbl = self::getTableName();
        $groupsTbl = Group::getTableName();

        $query = $wpdb->prepare(<<<EOL
SELECT SUM({$signaturesTbl}.count) as total 
FROM {$signaturesTbl}
INNER JOIN {$groupsTbl} ON {$signaturesTbl}.collected_by_groups_uuid = {$groupsTbl}.uuid
WHERE 
    {$groupsTbl}.causes_uuid = '%s'
    AND {$groupsTbl}.type = '%s'
    AND {$signaturesTbl}.deleted_at IS NULL
    AND {$groupsTbl}.deleted_at IS NULL
EOL,
            $causeUuid,
            $type->value
        );

        $result = $wpdb->get_var($query);

        if ($result === null && $wpdb->error) {
            throw new CollectmeDBException('Could not get total signatures for cause:' . $wpdb->last_error);
        }

        return (int) $result;
    }

    protected static function _convertFromCount(string|int $count): int
    {
        return (int) $count;
    }

    public function save(): static
    {
        $entry = parent::save();

        $group = Group::get($this->groupUuid);
        Stat::clearCache($group->causeUuid);

        return $entry;
    }

    public function delete(): void
    {
        parent::delete();

        $group = Group::get($this->groupUuid);
        Stat::clearCache($group->causeUuid);
    }
}