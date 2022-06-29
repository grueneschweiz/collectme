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

#[ApiModelType('group')]
#[DBTable('groups')]
class Group extends Entity
{
    /**
     * @var Objective[]
     */
    #[ApiModelRelationship(Objective::class)]
    public array $objectiveUuids;

    /**
     * @var Role[]
     */
    #[ApiModelRelationship(Role::class)]
    public array $roleUuids;

    #[ApiModelAttribute('_signatures')]
    private int $signatures;

    public function __construct(
        ?string $uuid,

        #[ApiModelAttribute]
        #[DBField]
        public string $name,

        #[ApiModelAttribute]
        #[DBField]
        public EnumGroupType $type,

        #[ApiModelRelationship(Cause::class)]
        #[DBField('causes_uuid')]
        public string $causeUuid,

        #[DBField('world_readable')]
        public bool $worldReadable,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @return Group[]
     * @throws CollectmeDBException
     */
    public static function findByCauseAndReadableByUser(string $causeUuid, string $userUuid): array
    {
        global $wpdb;

        $groupsTbl = self::getTableName();
        $rolesTbl = Role::getTableName();

        return self::findByQuery(
            $wpdb->prepare(
                <<<SQL
SELECT $groupsTbl.* FROM $groupsTbl
LEFT JOIN $rolesTbl ON $groupsTbl.uuid = $rolesTbl.groups_uuid
WHERE $groupsTbl.causes_uuid = '%s'
    AND $groupsTbl.deleted_at IS NULL
    AND (
        $groupsTbl.world_readable = 1
        OR (
            $rolesTbl.users_uuid = '%s' 
            AND $rolesTbl.deleted_at IS NULL
        )
    )
SQL,
                $causeUuid,
                $userUuid
            )
        );
    }

    protected static function _convertFromType(string $type): EnumGroupType
    {
        return EnumGroupType::from($type);
    }

    protected static function _convertFromWorldReadable(bool|string $worldReadable): bool
    {
        return (bool)$worldReadable;
    }

    public function userCanWrite(string $userUuid): bool
    {
        global $wpdb;

        $rolesTbl = Role::getTableName();

        return (bool)$wpdb->get_var(
            $wpdb->prepare(
                <<<SQL
SELECT count(*) FROM $rolesTbl
WHERE groups_uuid = '%s'
    AND users_uuid = '%s'
    AND permission = 'rw'
    AND deleted_at IS NULL
SQL,
                $this->uuid,
                $userUuid,
            )
        );
    }

    protected function _convertToType(): string
    {
        return $this->type->value;
    }

    protected function _convertToApiSignatures(): int
    {
        if (!isset($this->signatures)) {
            $this->signatures();
        }

        return $this->signatures;
    }

    public function signatures(): int
    {
        global $wpdb;

        $this->signatures = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(count) FROM " . SignatureEntry::getTableName() .
                " WHERE collected_by_groups_uuid = '%s'",
                $this->uuid
            )
        );

        return $this->signatures;
    }

    protected function _convertToApiObjectiveUuids(): array
    {
        if (!isset($this->objectiveUuids)) {
            $this->objectiveUuids();
        }

        return $this->objectiveUuids;
    }

    public function objectiveUuids(): array
    {
        global $wpdb;

        $this->objectiveUuids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT uuid FROM " . Objective::getTableName() .
                " WHERE groups_uuid = '%s'",
                $this->uuid
            )
        );

        return $this->objectiveUuids;
    }

    protected function _convertToApiRoleUuids(): array
    {
        if (!isset($this->roleUuids)) {
            $this->roleUuids();
        }

        return $this->roleUuids;
    }

    public function roleUuids(): array
    {
        global $wpdb;

        $this->roleUuids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT uuid FROM " . Role::getTableName() .
                " WHERE groups_uuid = '%s'",
                $this->uuid
            )
        );

        return $this->roleUuids;
    }
}