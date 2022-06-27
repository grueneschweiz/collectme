<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\EnumLang;
use PHPUnit\Framework\TestCase;

class AccountTokenTest extends TestCase
{
    public function test_getByToken(): void
    {
        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, 'mail@example.com', 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::getByToken($token);

        $this->assertSame($uuid, $accountToken->uuid);
        $this->assertSame($token, $accountToken->token);
        $this->assertSame('mail@example.com', $accountToken->email);
        $this->assertSame('Jane', $accountToken->firstName);
        $this->assertSame('Doe', $accountToken->lastName);
        $this->assertSame(EnumLang::DE, $accountToken->lang);
        $this->assertSame($validUntil, $accountToken->validUntil->format(DATE_ATOM));
        $this->assertInstanceOf(\DateTime::class, $accountToken->created);
        $this->assertInstanceOf(\DateTime::class, $accountToken->updated);
        $this->assertNull($accountToken->deleted);
    }

    private function insertTestTokenIntoDB(
        string $token,
        string $email,
        string $firstName,
        string $lastName,
        string $lang,
        string $validUntil
    ): string {
        global $wpdb;
        $uuid = wp_generate_uuid4();
        $wpdb->query(
            "INSERT INTO {$wpdb->prefix}collectme_account_tokens (uuid, token, email, first_name, last_name, lang, valid_until) " .
            "VALUES ('$uuid', '$token', '$email', '$firstName', '$lastName', '$lang', '$validUntil')"
        );

        return $uuid;
    }

    public function test_getByToken__expired(): void
    {
        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('-1 second')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, 'mail@example.com', 'Jane', 'Doe', 'd', $validUntil);

        $this->expectException(CollectmeDBException::class);
        $accountToken = AccountToken::getByToken($token);
    }
}
