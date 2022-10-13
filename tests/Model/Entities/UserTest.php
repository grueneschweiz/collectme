<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\User;
use Collectme\Model\Entities\UserCause;


class UserTest extends \WP_UnitTestCase
{
    public function test_get(): void
    {
        $email = $this->uniqueEmail();
        $uuid = $this->insertTestUserIntoDB($email, 'first', 'last', 'e', 'test');

        $user = User::get($uuid);

        $this->assertSame($uuid, $user->uuid);
        $this->assertSame($email, $user->email);
        $this->assertSame('first', $user->firstName);
        $this->assertSame('last', $user->lastName);
        $this->assertSame(EnumLang::EN, $user->lang);
        $this->assertSame('test', $user->source);
        $this->assertInstanceOf(\DateTime::class, $user->created);
        $this->assertInstanceOf(\DateTime::class, $user->updated);
        $this->assertNull($user->deleted);
    }

    public function test_getByEmail(): void
    {
        $email = $this->uniqueEmail();
        $uuid = $this->insertTestUserIntoDB($email, 'first', 'last', 'e', 'test');

        $user = User::getByEmail($email);

        $this->assertSame($uuid, $user->uuid);
        $this->assertSame($email, $user->email);
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
            'email' => $this->uniqueEmail(),
            'firstName' => 'John',
            'lastName' => 'Doe',
            'lang' => EnumLang::DE,
            'mailPermission' => true,
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
            'email' => $this->uniqueEmail(),
            'firstName' => 'John',
            'lastName' => 'Doe',
            'lang' => EnumLang::FR,
            'mailPermission' => true,
            'source' => 'test: some string'
        ];

        $user = new User(...$userData);
        $user->save();

        $user->firstName = 'Jane';
        $dbUser = $user->save();

        $this->assertSame('Jane', $dbUser->firstName);
    }

    public function test_addCause__exists(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'John',
            'Doe',
            EnumLang::FR,
            true,
            'add cause test'
        );
        $user->save();

        $cause = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause->save();

        $userCause = new UserCause(
            null,
            $user->uuid,
            $cause->uuid
        );
        $userCause->save();

        $user->addCause($cause->uuid);
        $this->assertSame($cause->uuid, $user->causes()[0]->uuid);
        $this->assertCount(1, $user->causes());
    }

    public function test_addCause__add(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'John',
            'Doe',
            EnumLang::FR,
            true,
            'add cause test'
        );
        $user->save();

        $cause = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause->save();

        $user->addCause($cause->uuid);
        $this->assertSame($cause->uuid, $user->causes()[0]->uuid);
        $this->assertCount(1, $user->causes());
    }

    public function test_hasCause__true(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'John',
            'Doe',
            EnumLang::FR,
            true,
            'add cause test'
        );
        $user->save();

        $cause = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause->save();

        $userCause = new UserCause(
            null,
            $user->uuid,
            $cause->uuid
        );
        $userCause->save();

        $this->assertTrue($user->hasCause($cause->uuid));
    }

    public function test_hasCause__false(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'John',
            'Doe',
            EnumLang::FR,
            true,
            'add cause test'
        );
        $user->save();

        $cause = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause->save();

        $this->assertFalse($user->hasCause($cause->uuid));
    }

    public function test_findByCause(): void
    {
        $user1 = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user1->save();

        $user2 = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user2->save();

        $user3 = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user3->save();


        $cause1 = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause1->save();

        $cause2 = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause2->save();

        $userCause1 = new UserCause(
            null,
            $user1->uuid,
            $cause1->uuid
        );
        $userCause1->save();

        $userCause2 = new UserCause(
            null,
            $user2->uuid,
            $cause1->uuid
        );
        $userCause2->save();

        $userCause3 = new UserCause(
            null,
            $user3->uuid,
            $cause2->uuid
        );
        $userCause3->save();

        $users = User::findByCause($cause1->uuid);

        $this->assertEqualsCanonicalizing(
            [$user1->uuid, $user2->uuid],
            [$users[0]->uuid, $users[1]->uuid]
        );
        $this->assertCount(2, $users);
    }

    public function test_delete(): void
    {
        $uuid = $this->insertTestUserIntoDB($this->uniqueEmail(), 'first', 'last', 'e', 'test');

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
            'email' => $this->uniqueEmail(),
            'firstName' => 'John',
            'lastName' => 'Doe',
            'lang' => EnumLang::DE,
            'mailPermission' => true,
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
            $apiModel->toArray()
        );
    }

    public function test_fromApiModelToPropsArray(): void
    {
        $apiData = [
            'id' => wp_generate_uuid4(),
            'type' => 'user',
            'attributes' => [
                'email' => $this->uniqueEmail(),
                'firstName' => 'John',
                'lastName' => 'Doe',
                'lang' => 'f',
                'source' => 'via api',
                'created' => date_create()->format(DATE_ATOM),
                'updated' => date_create()->format(DATE_ATOM),
                'deleted' => date_create()->format(DATE_ATOM),
            ]
        ];


        $userProps = User::fromApiModelToPropsArray($apiData);
        $this->assertArrayNotHasKey('source', $userProps);

        /** @noinspection PhpParamsInspection */
        $user = new User(...$userProps, source: 'must be added manually', mailPermission: true);

        $this->assertSame($apiData['id'], $user->uuid);
        $this->assertSame($apiData['attributes']['email'], $user->email);
        $this->assertSame($apiData['attributes']['firstName'], $user->firstName);
        $this->assertSame($apiData['attributes']['lastName'], $user->lastName);
        $this->assertSame(EnumLang::FR, $user->lang);
        $this->assertSame('must be added manually', $user->source);
        $this->assertSame($apiData['attributes']['created'], $user->created->format(DATE_ATOM));
        $this->assertSame($apiData['attributes']['created'], $user->updated->format(DATE_ATOM));
        $this->assertNull($user->deleted);
    }

    private function uniqueEmail(): string {
        return wp_generate_uuid4() . '@example.com';
    }
}
