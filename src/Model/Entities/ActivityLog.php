<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\Entity;
use Collectme\Model\JsonApi\ApiModelAttribute;
use Collectme\Model\JsonApi\ApiModelRelationship;
use Collectme\Model\JsonApi\ApiModelType;

#[DBTable('activity_logs')]
#[ApiModelType('activity')]
class ActivityLog extends Entity
{
    public function __construct(
        ?string $uuid,

        #[ApiModelAttribute]
        #[DBField]
        public EnumActivityType $type,

        #[ApiModelAttribute]
        #[DBField]
        public int $count,

        #[ApiModelRelationship(Cause::class)]
        #[DBField('causes_uuid')]
        public string $causeUuid,

        #[ApiModelRelationship(Group::class)]
        #[DBField('groups_uuid')]
        public string $groupUuid,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    protected static function _convertFromType(string $type): EnumActivityType
    {
        return EnumActivityType::from($type);
    }

    protected static function _convertFromCount(int|string $count): int
    {
        return (int)$count;
    }

    protected function _convertToType(): string
    {
        return $this->type->value;
    }
}