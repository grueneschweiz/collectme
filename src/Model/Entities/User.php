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

    #[DBField]
    public string $source;

    public function __construct(
        ?string $uuid,
        string $email,
        string $firstName,
        string $lastName,
        EnumLang $lang,
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