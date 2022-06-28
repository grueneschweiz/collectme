<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

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

    protected static function _convertFromObjective(string|int $objective): int
    {
        return (int)$objective;
    }
}