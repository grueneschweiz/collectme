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


#[ApiModelType('objective')]
#[DBTable('objectives')]
class Objective extends Entity
{
    public function __construct(
        ?string $uuid,

        #[DBField]
        #[ApiModelAttribute]
        public int $objective,

        #[DBField('groups_uuid')]
        #[ApiModelRelationship(Group::class)]
        public string $groupUuid,

        #[DBField]
        #[ApiModelAttribute]
        public string $source,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @return Objective[]
     * @throws CollectmeDBException
     */
    public static function findByGroups(array $groupUuids): array
    {
        global $wpdb;

        $objectivesTbl = self::getTableName();
        $placeholders = implode(',', array_fill(0, count($groupUuids), '%s'));

        $query = $wpdb->prepare(<<<SQL
SELECT * FROM {$objectivesTbl}
WHERE groups_uuid IN ({$placeholders})
AND deleted_at IS NULL
SQL,
            ...$groupUuids
        );

        return self::findByQuery($query);
    }

    protected static function _convertFromObjective(string|int $objective): int
    {
        return (int)$objective;
    }
}