<?php

declare(strict_types=1);

namespace Unit\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Util;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\EnumLang;
use PHPUnit\Framework\TestCase;

class AccountTokenTest extends TestCase
{
    public function test_getByEmailAndToken(): void
    {
        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years', Util::getTimeZone())->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, 'mail@example.com', 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::getByEmailAndToken('mail@example.com', $token);

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

    public function test_getByEmailAndToken__expired(): void
    {
        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('-1 second')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, 'mail@example.com', 'Jane', 'Doe', 'd', $validUntil);

        $this->expectException(CollectmeDBException::class);
        $accountToken = AccountToken::getByEmailAndToken('mail@example.com', $token);
    }

    public function test_getByEmailAndToken__multiple(): void
    {
        $token1 = wp_generate_password(64, false, false);
        $token2 = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years', Util::getTimeZone())->format(DATE_ATOM);
        $uuid1 = $this->insertTestTokenIntoDB($token1, 'mail@example.com', 'Jane', 'Doe', 'd', $validUntil);
        $uuid2 = $this->insertTestTokenIntoDB($token2, 'mail@example.com', 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::getByEmailAndToken('mail@example.com', $token2);

        $this->assertSame($uuid2, $accountToken->uuid);
        $this->assertSame($token2, $accountToken->token);
        $this->assertSame('mail@example.com', $accountToken->email);
        $this->assertSame('Jane', $accountToken->firstName);
        $this->assertSame('Doe', $accountToken->lastName);
        $this->assertSame(EnumLang::DE, $accountToken->lang);
        $this->assertSame($validUntil, $accountToken->validUntil->format(DATE_ATOM));
        $this->assertInstanceOf(\DateTime::class, $accountToken->created);
        $this->assertInstanceOf(\DateTime::class, $accountToken->updated);
        $this->assertNull($accountToken->deleted);
    }

    public function test_getByEmailAndToken__noValidToken(): void
    {
        $token1 = wp_generate_password(64, false, false);
        $token2 = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $uuid1 = $this->insertTestTokenIntoDB($token1, 'mail@example.com', 'Jane', 'Doe', 'd', $validUntil);

        $this->expectException(CollectmeDBException::class);
        $accountToken = AccountToken::getByEmailAndToken('mail@example.com', $token2);
    }

    public function test_getByEmail(): void
    {
        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years', Util::getTimeZone())->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, 'c@example.com', 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::getByEmail('c@example.com');

        $this->assertSame($uuid, $accountToken->uuid);
    }

    public function test_getByEmail__longestValid(): void
    {
        $token1 = wp_generate_password(64, false, false);
        $token2 = wp_generate_password(64, false, false);
        $token3 = wp_generate_password(64, false, false);
        $uuid1 = $this->insertTestTokenIntoDB($token1, 'a@example.com', 'Jane', 'Doe', 'd', date_create('+5 years', Util::getTimeZone())->format(DATE_ATOM));
        $uuid2 = $this->insertTestTokenIntoDB($token2, 'a@example.com', 'Jane', 'Doe', 'd', date_create('+10 years', Util::getTimeZone())->format(DATE_ATOM));
        $uuid3 = $this->insertTestTokenIntoDB($token3, 'a@example.com', 'Jane', 'Doe', 'd', date_create('+2 years', Util::getTimeZone())->format(DATE_ATOM));

        $accountToken = AccountToken::getByEmail('a@example.com');

        $this->assertSame($uuid2, $accountToken->uuid);
    }

    public function test_getByEmail__expired(): void
    {
        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('-1 day')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, 'b@example.com', 'Jane', 'Doe', 'd', $validUntil);

        $this->expectException(CollectmeDBException::class);
        $accountToken = AccountToken::getByEmail('b@example.com');
    }
}
