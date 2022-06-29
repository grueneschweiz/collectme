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

#[DBTable('roles')]
#[ApiModelType('role')]
class Role extends Entity
{
    public function __construct(
        ?string $uuid,

        #[ApiModelRelationship(User::class)]
        #[DBField('users_uuid')]
        public string $userUuid,

        #[ApiModelRelationship(Group::class)]
        #[DBField('groups_uuid')]
        public string $groupUuid,

        #[ApiModelAttribute]
        #[DBField]
        public EnumPermission $permission,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @return Role[]
     * @throws CollectmeDBException
     */
    public static function findByGroups(array $groupUuids): array
    {
        global $wpdb;

        $rolesTbl = self::getTableName();
        $placeholders = implode(',', array_fill(0, count($groupUuids), '%s'));

        $query = $wpdb->prepare(
            <<<SQL
SELECT * FROM {$rolesTbl}
WHERE groups_uuid IN ({$placeholders})
AND deleted_at IS NULL
SQL,
            ...$groupUuids
        );

        return self::findByQuery($query);
    }

    protected static function _convertFromPermission(string $permission): EnumPermission
    {
        return EnumPermission::from($permission);
    }

    protected function _convertToPermission(): string
    {
        return $this->permission->value;
    }
}