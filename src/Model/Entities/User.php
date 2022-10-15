<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\Entity;
use Collectme\Model\JsonApi\ApiModelAttribute;
use Collectme\Model\JsonApi\ApiModelType;

#[ApiModelType('user')]
#[DBTable('users')]
class User extends Entity
{
    #[ApiModelAttribute]
    #[DBField]
    public string $email;

    #[ApiModelAttribute]
    #[DBField('first_name')]
    public string $firstName;

    #[ApiModelAttribute]
    #[DBField('last_name')]
    public string $lastName;

    #[ApiModelAttribute]
    #[DBField]
    public EnumLang $lang;

    #[DBField('mail_permission')]
    public bool $mailPermission;

    #[DBField]
    public string $source;

    public function __construct(
        ?string $uuid,
        string $email,
        string $firstName,
        string $lastName,
        EnumLang $lang,
        bool $mailPermission,
        string $source,
        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null,
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);

        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->lang = $lang;
        $this->mailPermission = $mailPermission;
        $this->source = $source;
    }

    /**
     * @throws CollectmeDBException
     */
    public static function getByEmail(string $email): self
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM " . self::getTableName() . " WHERE email = '%s' AND deleted_at IS NULL",
            $email
        );

        return self::getByQuery($query);
    }

    /**
     * @return User[]
     * @throws CollectmeDBException
     */
    public static function findByCause(string $causeUuid): array
    {
        global $wpdb;

        $usersTbl = self::getTableName();
        $userCausesTbl = UserCause::getTableName();

        $query = $wpdb->prepare(
            "SELECT {$usersTbl}.* FROM {$usersTbl}" .
            " INNER JOIN {$userCausesTbl} ON {$usersTbl}.uuid = {$userCausesTbl}.users_uuid" .
            " WHERE {$userCausesTbl}.causes_uuid = '%s'" .
            " AND {$usersTbl}.deleted_at IS NULL" .
            " AND {$userCausesTbl}.deleted_at IS NULL",
            $causeUuid
        );

        return self::findByQuery($query);
    }

    /**
     * @return User[]
     * @throws CollectmeDBException
     */
    public static function findWithWritePermissionForGroup(string $groupUuid): array
    {
        global $wpdb;

        $usersTbl = self::getTableName();
        $rolesTbl = Role::getTableName();

        $permission = EnumPermission::READ_WRITE->value;

        return self::findByQuery(
            $wpdb->prepare(
                <<<SQL
SELECT DISTINCT $usersTbl.* FROM $usersTbl
INNER JOIN $rolesTbl ON $usersTbl.uuid = $rolesTbl.users_uuid
WHERE $rolesTbl.groups_uuid = '%s'
AND $rolesTbl.permission = '$permission'
AND $rolesTbl.deleted_at IS NULL
AND $usersTbl.deleted_at IS NULL
SQL,
                $groupUuid
            )
        );
    }

    /**
     * Convert lang string to enum
     *
     * @param string $lang
     * @return EnumLang
     * @noinspection PhpUnused
     */
    protected static function _convertFromLang(string $lang): EnumLang
    {
        return EnumLang::from($lang);
    }

    protected static function _convertFromMailPermission(string $permission): bool
    {
        return (bool)$permission;
    }

    /**
     * @throws CollectmeDBException
     */
    public function addCause(string $causeUuid): void
    {
        if ($this->hasCause($causeUuid)) {
            return;
        }

        $userCause = new UserCause(
            null,
            $this->uuid,
            $causeUuid
        );
        $userCause->save();
    }

    /**
     * @throws CollectmeDBException
     */
    public function hasCause(string $causeUuid): bool
    {
        return !empty(UserCause::findByUserAndCause($this->uuid, $causeUuid));
    }

    /**
     * @return Cause[]
     * @throws CollectmeDBException
     */
    public function causes(): array
    {
        return Cause::findByUser($this->uuid);
    }

    /**
     * Return lang enum as string
     *
     * @return string
     * @noinspection PhpUnused
     */
    protected function _convertToLang(): string
    {
        return $this->lang->value;
    }
}