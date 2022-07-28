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
        ?string $uuid,

        #[DBField]
        public string $token,

        #[DBField]
        public string $email,

        #[DBField('first_name')]
        public string $firstName,

        #[DBField('last_name')]
        public string $lastName,

        #[DBField]
        public EnumLang $lang,

        #[DateProperty]
        #[DBField('valid_until')]
        public \DateTime $validUntil,

        #[DBField('users_uuid')]
        public ?string $userUuid = null,

        ?\DateTime $created = null,
        ?\DateTime $updated = null,
        ?\DateTime $deleted = null,
    ) {
        parent::__construct($uuid, $created, $updated, $deleted);
    }

    /**
     * @throws CollectmeDBException
     */
    public static function getByEmailAndToken(string $email, string $token): self
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM " . self::getTableName(
            ) . " WHERE email = '%s' AND valid_until > NOW() AND deleted_at IS NULL",
            $email
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        if (empty($results)) {
            throw new CollectmeDBException('Failed to get ' . static::class . ": $query");
        }

        foreach ($results as $result) {
            if (hash_equals($result['token'], $token)) {
                return new static(...self::convertFieldsFromDb($result));
            }
        }

        throw new CollectmeDBException('Failed to get ' . static::class . " for token: $token");
    }

    /**
     * @throws CollectmeDBException
     */
    public static function getByEmail(string $email): self
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM " . self::getTableName() .
            " WHERE email = '%s' AND valid_until > NOW() AND deleted_at IS NULL " .
            " ORDER BY valid_until DESC, created_at DESC LIMIT 1",
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