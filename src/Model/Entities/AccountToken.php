<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBTable;
use Collectme\Model\DateProperty;
use Collectme\Model\Entity;

#[DBTable('account_tokens')]
class AccountToken extends Entity
{
    public function __construct(
        string $uuid,
        #[DBField] public string $token,
        #[DBField] public string $email,
        #[DBField('first_name')] public string $firstName,
        #[DBField('last_name')] public string $lastName,
        #[DBField] public EnumLang $lang,
        #[DateProperty] #[DBField('valid_until')] public \DateTime $validUntil,
        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null,
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @throws CollectmeDBException
     */
    public static function getByToken(string $token): self
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM " . self::getTableName() . " WHERE token = '%s' AND deleted_at IS NULL",
            $token
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