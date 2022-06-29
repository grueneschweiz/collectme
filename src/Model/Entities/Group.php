<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

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
    #[ApiModelAttribute]
    private int $signatures;

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

    protected static function _convertFromType(string $type): EnumGroupType
    {
        return EnumGroupType::from($type);
    }

    protected static function _convertFromWorldReadable(bool|string $worldReadable): bool
    {
        return (bool)$worldReadable;
    }

    protected function _convertToType(): string
    {
        return $this->type->value;
    }

    protected function _convertToApiSignatures(): int
    {
        if (!isset($this->signatures)){
            $this->signatures();
        }
        
        return $this->signatures;
    }
    
    protected function _convertToApiObjectiveUuids(): array
    {
        if (!isset($this->objectiveUuids)){
            $this->objectiveUuids();
        }

        return $this->objectiveUuids;
    }
    
    protected function _convertToApiRoleUuids(): array
    {
        if (!isset($this->roleUuids)){
            $this->roleUuids();
        }

        return $this->roleUuids;
    }

    public function signatures(): int
    {
        global $wpdb;

        $this->signatures = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(count) FROM " . SignatureEntry::getTableName() .
                " WHERE collected_by_groups_uuid = '%s'",
                $this->uuid
            )
        );

        return $this->signatures;
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