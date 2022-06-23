<?php

declare(strict_types=1);

namespace Model;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\User;


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
        $uuid = wp_generate_uuid4();
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

    public function test_toApiModel(): void
    {
        $userData = [
            'uuid' => wp_generate_uuid4(),
            'email' => 'mail@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'lang' => EnumLang::DE,
            'source' => 'test: some string',
            'created' => date_create(),
            'updated' => date_create(),
            'deleted' => date_create(),
        ];

        $user = new User(...$userData);

        $apiModel = $user->toApiModel();

        $this->assertEqualsCanonicalizing(
            [
                'id' => $userData['uuid'],
                'type' => 'user',
                'attributes' => [
                    'email' => $userData['email'],
                    'firstName' => $userData['firstName'],
                    'lastName' => $userData['lastName'],
                    'lang' => $userData['lang']->value,
                    'created' => $userData['created']->format(DATE_RFC3339_EXTENDED),
                    'updated' => $userData['updated']->format(DATE_RFC3339_EXTENDED),
                ]
            ],
            $apiModel
        );
    }

    public function test_fromApiModelToPropsArray(): void
    {
        $apiData = [
            'id' => wp_generate_uuid4(),
            'type' => 'user',
            'attributes' => [
                'email' => 'mail@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'lang' => 'f',
                'source' => 'via api',
                'created' => date_create()->format(DATE_RFC3339_EXTENDED),
                'updated' => date_create()->format(DATE_RFC3339_EXTENDED),
                'deleted' => date_create()->format(DATE_RFC3339_EXTENDED),
            ]
        ];


        $userProps = User::fromApiModelToPropsArray($apiData);
        $this->assertArrayNotHasKey('source', $userProps);

        /** @noinspection PhpParamsInspection */
        $user = new User(...$userProps, source: 'must be added manually');

        $this->assertSame($apiData['id'], $user->uuid);
        $this->assertSame($apiData['attributes']['email'], $user->email);
        $this->assertSame($apiData['attributes']['firstName'], $user->firstName);
        $this->assertSame($apiData['attributes']['lastName'], $user->lastName);
        $this->assertSame(EnumLang::FR, $user->lang);
        $this->assertSame('must be added manually', $user->source);
        $this->assertSame($apiData['attributes']['created'], $user->created->format(DATE_RFC3339_EXTENDED));
        $this->assertSame($apiData['attributes']['created'], $user->updated->format(DATE_RFC3339_EXTENDED));
        $this->assertNull($user->deleted);
    }
}
