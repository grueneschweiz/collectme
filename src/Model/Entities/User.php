<?php

declare(strict_types=1);

namespace Collectme\Model;

#[DBTable('users')]
class User extends Entity
{
    #[DBAttribute]
    public string $email;

    #[DBAttribute('first_name')]
    public string $firstName;

    #[DBAttribute('last_name')]
    public string $lastName;

    #[DBAttribute]
    public EnumLang $lang;

    #[DBAttribute]
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