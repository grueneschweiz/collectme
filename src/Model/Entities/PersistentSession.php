<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\DateProperty;
use Collectme\Model\Entity;
use Collectme\Model\JsonApi\ApiModelAttribute;
use Collectme\Model\JsonApi\ApiModelRelationship;
use Collectme\Model\JsonApi\ApiModelType;

#[ApiModelType('session')]
#[DBTable('sessions')]
class PersistentSession extends Entity
{
    public function __construct(
        ?string $uuid,

        #[ApiModelRelationship(User::class)]
        #[DBField('users_uuid')]
        public string $userUuid,

        #[ApiModelAttribute]
        #[DBField('login_counter')]
        public int $loginCounter,

        #[ApiModelAttribute]
        #[DateProperty]
        #[DBField('last_login')]
        public ?\DateTime $lastLogin,

        #[DBField('activation_secret')]
        public string $activationSecret,

        #[DBField('session_hash')]
        public string $sessionHash,

        #[ApiModelAttribute]
        #[DateProperty]
        #[DBField('activated_at')]
        public ?\DateTime $activated,

        #[DateProperty]
        #[DBField('closed_at')]
        public ?\DateTime $closed,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @throws CollectmeDBException
     */
    public static function getActive(string $uuid): self
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM " . self::getTableName() .
            " WHERE uuid = '%s'" .
            " AND deleted_at IS NULL" .
            " AND closed_at IS NULL" .
            " AND activated_at IS NOT NULL",
            $uuid
        );

        return static::getByQuery($query);
    }

    public function checkSessionSecret(string $sessionSecret): bool
    {
        return password_verify($sessionSecret, $this->sessionHash);
    }

    protected static function _convertFromLoginCounter(string|int $counter): int
    {
        return (int)$counter;
    }
}