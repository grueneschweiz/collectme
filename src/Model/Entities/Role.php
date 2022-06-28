<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\Entity;

#[DBTable('roles')]
class Role extends Entity
{
    public function __construct(
        ?string $uuid,

        #[DBField('users_uuid')]
        public string $userUuid,

        #[DBField('groups_uuid')]
        public string $groupUuid,

        #[DBField]
        public EnumPermission $permission,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
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