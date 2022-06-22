<?php

declare(strict_types=1);

namespace Model;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\EnumLang;
use Collectme\Model\User;
use Ramsey\Uuid\Uuid;


class UserTest extends \WP_UnitTestCase
{
    public function test_example(): void
    {
        $this->assertTrue(true);
    }

    public function test_get(): void
    {
        $uuid = $this->insertTestUserIntoDB('mail@example.com', 'first', 'last', 'e', 'test');

        $user = User::get($uuid);

        $this->assertSame($uuid, $user->uuid);
        $this->assertSame('mail@example.com', $user->email);
        $this->assertSame('first', $user->firstName);
        $this->assertSame('last', $user->lastName);
        $this->assertSame(EnumLang::EN, $user->lang);
        $this->assertSame('test', $user->source);
        $this->assertInstanceOf(\DateTime::class, $user->created);
        $this->assertInstanceOf(\DateTime::class, $user->updated);
        $this->assertNull($user->deleted);
    }

    private function insertTestUserIntoDB(
        string $email,
        string $firstName,
        string $lastName,
        string $lang,
        string $source
    ): string {
        global $wpdb;
        $uuid = Uuid::uuid4()->toString();
        $wpdb->query(
            "INSERT INTO {$wpdb->prefix}collectme_users (uuid, email, first_name, last_name, lang, source) " .
            "VALUES ('$uuid', '$email', '$firstName', '$lastName', '$lang', '$source')"
        );

        return $uuid;
    }

    public function test_save__create(): void
    {
        $userData = [
            'uuid' => null,
            'email' => 'mail@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'lang' => EnumLang::DE,
            'source' => 'test: some string'
        ];

        $user = new User(...$userData);
        $user->save();

        $dbUser = User::get($user->uuid);

        $this->assertSame($userData['email'], $dbUser->email);
        $this->assertSame($userData['firstName'], $dbUser->firstName);
        $this->assertSame($userData['lastName'], $dbUser->lastName);
        $this->assertSame($userData['lang'], $dbUser->lang);
        $this->assertSame($userData['source'], $dbUser->source);
        $this->assertInstanceOf(\DateTime::class, $dbUser->created);
        $this->assertInstanceOf(\DateTime::class, $dbUser->updated);
        $this->assertNull($dbUser->deleted);
    }

    public function test_save__update(): void
    {
        $userData = [
            'uuid' => null,
            'email' => 'mail@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'lang' => EnumLang::FR,
            'source' => 'test: some string'
        ];

        $user = new User(...$userData);
        $user->save();

        $user->firstName = 'Jane';
        $dbUser = $user->save();

        $this->assertSame('Jane', $dbUser->firstName);
    }

    public function test_delete(): void
    {
        $uuid = $this->insertTestUserIntoDB('mail@example.com', 'first', 'last', 'e', 'test');

        $user = User::get($uuid);
        $user->delete();

        $dbUser = User::get($uuid, true);
        $this->assertInstanceOf(\DateTime::class, $dbUser->deleted);

        $this->expectException(CollectmeDBException::class);
        User::get($uuid);
    }

}
