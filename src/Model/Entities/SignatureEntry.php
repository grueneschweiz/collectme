<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

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
        public string $activityLogUuid,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    protected static function _convertFromCount(string|int $count): int
    {
        return (int) $count;
    }
}