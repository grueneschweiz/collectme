<?php

declare(strict_types=1);

namespace Misc;

use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Auth;
use Collectme\Misc\Cookie;
use Collectme\Model\AuthCookie;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\User;
use Collectme\Model\PhpSession;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{

    public function test_getPersistentSession__fromPhpSession(): void
    {
        $userUuid = $this->insertTestUserIntoDB($this->uniqueEmail(), 'first', 'last', 'e', 'test');
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            null,
            $userUuid,
            5,
            date_create('2022-06-26T20:30:00+00:00'),
            wp_generate_password(64, false, false),
            $sessionHash,
            date_create('2022-06-26T21:00:00+00:00'),
            null,
        );
        $session = $session->save();

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $phpSession->expects($this->once())
            ->method('get')
            ->willReturn($session);

        $auth = new Auth($phpSession, $authCookie);

        $this->assertEquals($session, $auth->getPersistentSession());
    }

    public function test_getPersistentSession__none(): void
    {
        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $phpSession->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $authCookie->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $auth = new Auth($phpSession, $authCookie);

        $this->assertNull($auth->getPersistentSession());
    }

    public function test_getPersistentSession__fromAuthCookie__valid(): void
    {
        $userUuid = $this->insertTestUserIntoDB($this->uniqueEmail(), 'first', 'last', 'e', 'test');

        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            null,
            $userUuid,
            5,
            date_create('2022-06-26T20:30:00+00:00'),
            wp_generate_password(64, false, false),
            $sessionHash,
            date_create('2022-06-26T21:00:00+00:00'),
            null,
        );
        $session->save();

        $cookieMock = $this->createMock(Cookie::class);

        $authCookie = new AuthCookie($cookieMock);
        $authCookie->set($session->uuid, $sessionSecret);

        $phpSessionMock = $this->createMock(PhpSession::class);
        $authCookieMock = $this->createMock(AuthCookie::class);

        $phpSessionMock->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $phpSessionMock->expects($this->once())
            ->method('set')
            ->with($this->callback(fn($subject) => $subject->uuid === $session->uuid));

        $authCookieMock->expects($this->once())
            ->method('get')
            ->willReturn($authCookie);

        $auth = new Auth($phpSessionMock, $authCookieMock);

        $this->assertSame($session->uuid, $auth->getPersistentSession()->uuid);
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

    private function uniqueEmail(): string
    {
        return wp_generate_uuid4() . '@example.com';
    }

    public function test_getPersistentSession__fromAuthCookie__invalid(): void
    {
        $phpSessionMock = $this->createMock(PhpSession::class);
        $authCookieMock = $this->createMock(AuthCookie::class);

        $phpSessionMock->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $authCookieMock->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $auth = new Auth($phpSessionMock, $authCookieMock);

        $this->assertNull($auth->getPersistentSession());
    }

    public function test_createPersistentSession__notActivated(): void
    {
        $userUuid = $this->insertTestUserIntoDB($this->uniqueEmail(), 'first', 'last', 'e', 'test');
        $user = User::get($userUuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $auth->createPersistentSession($user, false);

        global $wpdb;
        $session = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}collectme_sessions WHERE users_uuid = '$userUuid'",
            ARRAY_A
        );

        $this->assertIsArray($session);
        $this->assertNull($session['activated_at']);
    }

    public function test_getPersistentSession__fromAuthCookie__valid__checkLoginStats(): void
    {
        $userUuid = $this->insertTestUserIntoDB($this->uniqueEmail(), 'first', 'last', 'e', 'test');

        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            null,
            $userUuid,
            5,
            date_create('2022-06-26T20:30:00+00:00'),
            wp_generate_password(64, false, false),
            $sessionHash,
            date_create('2022-06-26T21:00:00+00:00'),
            null,
        );
        $session->save();

        $cookieMock = $this->createMock(Cookie::class);

        $authCookie = new AuthCookie($cookieMock);
        $authCookie->set($session->uuid, $sessionSecret);

        $phpSessionMock = $this->createMock(PhpSession::class);
        $authCookieMock = $this->createMock(AuthCookie::class);

        $phpSessionMock->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $phpSessionMock->expects($this->once())
            ->method('set')
            ->with($this->callback(fn($subject) => $subject->uuid === $session->uuid));

        $authCookieMock->expects($this->once())
            ->method('get')
            ->willReturn($authCookie);

        $auth = new Auth($phpSessionMock, $authCookieMock);

        $this->assertSame($session->loginCounter + 1, $auth->getPersistentSession()->loginCounter);
        $this->assertGreaterThan($session->lastLogin, $auth->getPersistentSession()->lastLogin);
    }

    public function test_createPersistentSession__activated(): void
    {
        $userUuid = $this->insertTestUserIntoDB($this->uniqueEmail(), 'first', 'last', 'e', 'test');
        $user = User::get($userUuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $auth->createPersistentSession($user, true);

        global $wpdb;
        $session = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}collectme_sessions WHERE users_uuid = '$userUuid'",
            ARRAY_A
        );

        $this->assertIsArray($session);
        $this->assertNotNull($session['activated_at']);
    }

    public function test_isAuthenticated__active(): void
    {
        $session = new PersistentSession(
            null,
            wp_generate_uuid4(),
            5,
            date_create('2022-06-26T20:30:00+00:00'),
            wp_generate_password(64, false, false),
            'asdf',
            date_create('2022-06-26T21:00:00+00:00'),
            null,
        );

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);
        $auth->setPersistentSession($session);

        $this->assertTrue($auth->isAuthenticated());
    }

    public function test_isAuthenticated__closed(): void
    {
        $session = new PersistentSession(
            null,
            wp_generate_uuid4(),
            5,
            date_create('2022-06-26T20:30:00+00:00'),
            wp_generate_password(64, false, false),
            wp_hash_password(wp_generate_password(64, false, false)),
            date_create('2022-06-26T21:00:00+00:00'),
            date_create(),
        );

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);
        $auth->setPersistentSession($session);

        $this->assertFalse($auth->isAuthenticated());
    }

    public function test_isAuthenticated__none(): void
    {
        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $this->assertFalse($auth->isAuthenticated());
    }

    public function test_getOrSetupUserFromAccountToken__linkedUser(): void
    {
        $cause = new Cause(
            null,
            'test_'. wp_generate_password(),
        );
        $cause->save();

        $userUuid = $this->insertTestUserIntoDB($this->uniqueEmail(), 'first', 'last', 'e', 'test');

        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB(
            $token,
            'NOT_mail@example.com',
            'Jane',
            'Doe',
            'd',
            $validUntil,
            $userUuid
        );

        $accountToken = AccountToken::get($uuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $user = $auth->getOrSetupUserFromAccountToken($accountToken, $cause->uuid);

        $this->assertSame($userUuid, $user->uuid);
    }

    private function insertTestTokenIntoDB(
        string $token,
        string $email,
        string $firstName,
        string $lastName,
        string $lang,
        string $validUntil,
        ?string $userUuid = null,
    ): string {
        global $wpdb;
        $uuid = wp_generate_uuid4();

        $userKey = '';
        $userValue = '';
        if ($userUuid) {
            $userKey = ', users_uuid';
            $userValue = ", '$userUuid'";
        }

        $wpdb->query(
            "INSERT INTO {$wpdb->prefix}collectme_account_tokens (uuid, token, email, first_name, last_name, lang, valid_until$userKey) " .
            "VALUES ('$uuid', '$token', '$email', '$firstName', '$lastName', '$lang', '$validUntil'$userValue)"
        );

        return $uuid;
    }

    public function test_getOrSetupUserFromAccountToken__getByEmail(): void
    {
        $cause = new Cause(
            null,
            'test_'. wp_generate_password(),
        );
        $cause->save();

        $email = $this->uniqueEmail();
        $userUuid = $this->insertTestUserIntoDB($email, 'first', 'last', 'e', 'test');

        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, $email, 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::get($uuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $user = $auth->getOrSetupUserFromAccountToken($accountToken, $cause->uuid);

        // test correct user
        $this->assertSame($userUuid, $user->uuid);
    }

    public function test_getOrSetupUserFromAccountToken__createUser(): void
    {
        $cause = new Cause(
            null,
            'test_'. wp_generate_password(),
        );
        $cause->save();

        $email = $this->uniqueEmail();
        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, $email, 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::get($uuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $user = $auth->getOrSetupUserFromAccountToken($accountToken, $cause->uuid);

        // test correct user
        $this->assertSame($email, $user->email);

        // test linked objects
        $causeUuid = $user->causes()[0]->uuid;
        $this->assertSame($cause->uuid, $causeUuid);

        $groups = Group::findByCauseAndReadableByUser($cause->uuid, $user->uuid);
        $this->assertCount(1, $groups);
        $this->assertSame($user->firstName, $groups[0]->name);
        $this->assertSame(EnumGroupType::PERSON, $groups[0]->type);
        $this->assertFalse($groups[0]->worldReadable);

        $this->assertTrue($groups[0]->userCanWrite($user->uuid));
    }

    public function test_getOrSetupUserFromAccountToken__autolinking(): void
    {
        $cause = new Cause(
            null,
            'test_'. wp_generate_password(),
        );
        $cause->save();

        $email = $this->uniqueEmail();
        $userUuid = $this->insertTestUserIntoDB($email, 'first', 'last', 'e', 'test');

        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, $email, 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::get($uuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $auth->getOrSetupUserFromAccountToken($accountToken, $cause->uuid);

        $accountToken = AccountToken::get($uuid);
        $this->assertSame($userUuid, $accountToken->userUuid);
    }

    public function test_getOrSetupUserFromAccountToken__autosetup(): void
    {
        $cause = new Cause(
            null,
            'test_'. wp_generate_password(),
        );
        $cause->save();

        $email = $this->uniqueEmail();
        $userUuid = $this->insertTestUserIntoDB($email, 'first', 'last', 'e', 'test');

        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $tokenUuid = $this->insertTestTokenIntoDB($token, $email, 'Jane', 'Doe', 'd', $validUntil, $userUuid);

        $accountToken = AccountToken::get($tokenUuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $auth->getOrSetupUserFromAccountToken($accountToken, $cause->uuid);

        // test linked objects
        $user = User::get($userUuid);
        $causeUuid = $user->causes()[0]->uuid;
        $this->assertSame($cause->uuid, $causeUuid);

        $groups = Group::findByCauseAndReadableByUser($cause->uuid, $user->uuid);
        $this->assertCount(1, $groups);
        $this->assertSame($user->firstName, $groups[0]->name);
        $this->assertSame(EnumGroupType::PERSON, $groups[0]->type);
        $this->assertFalse($groups[0]->worldReadable);

        $this->assertTrue($groups[0]->userCanWrite($user->uuid));
    }

    public function test_logout__success(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'John',
            'Doe',
            EnumLang::FR,
            'test: some string',
        );
        $user->save();

        $session = new PersistentSession(
            null,
            $user->uuid,
            5,
            date_create('2022-06-26T20:30:00+00:00'),
            wp_generate_password(64, false, false),
            wp_hash_password(wp_generate_password(64, false, false)),
            date_create('2022-06-26T21:00:00+00:00'),
            null,
        );

        $phpSessionMock = $this->createMock(PhpSession::class);
        $authCookieMock = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSessionMock, $authCookieMock);
        $auth->setPersistentSession($session);

        $authCookieMock->expects($this->once())
            ->method('invalidate');

        $phpSessionMock->expects($this->once())
            ->method('reset');

        $auth->logout();

        $this->assertFalse($auth->isAuthenticated());
        $this->assertInstanceOf(\DateTime::class, $auth->getPersistentSession()->closed);
    }

    public function test_logout__fail(): void
    {
        $phpSessionMock = $this->createMock(PhpSession::class);
        $authCookieMock = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSessionMock, $authCookieMock);

        $this->expectException(CollectmeException::class);
        $auth->logout();
    }

    public function test_isAuthenticatedAndHasValidNonce__valid(): void
    {
        $session = new PersistentSession(
            null,
            wp_generate_uuid4(),
            5,
            date_create('2022-06-26T20:30:00+00:00'),
            wp_generate_password(64, false, false),
            'asdf',
            date_create('2022-06-26T21:00:00+00:00'),
            null,
        );

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);
        $auth->setPersistentSession($session);

        $request = new \WP_REST_Request();
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $this->assertTrue($auth->isAuthenticatedAndHasValidNonce($request));
    }

    public function test_isAuthenticatedAndHasValidNonce__invalid(): void
    {
        $session = new PersistentSession(
            null,
            wp_generate_uuid4(),
            5,
            date_create('2022-06-26T20:30:00+00:00'),
            wp_generate_password(64, false, false),
            'asdf',
            date_create('2022-06-26T21:00:00+00:00'),
            null,
        );

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);
        $auth->setPersistentSession($session);

        $request = new \WP_REST_Request();
        $request->set_header('X-WP-Nonce', wp_create_nonce('something else'));

        $this->assertFalse($auth->isAuthenticatedAndHasValidNonce($request));
    }

    public function test_getOrSetupUser__create(): void
    {
        $cause = new Cause(
            null,
            'test_'. wp_generate_password(),
        );
        $cause->save();

        $auth = new Auth(
            $this->createMock(PhpSession::class),
            $this->createMock(AuthCookie::class),
        );

        $email = wp_generate_uuid4().'@mail.com';

        $user = $auth->getOrSetupUser(
            $email,
            'John',
            'Doe',
            EnumLang::FR,
            'test: some string',
            $cause->uuid,
        );

        self::assertNotEmpty($user->uuid);
        self::assertTrue($user->hasCause($cause->uuid));
    }

    public function test_getOrSetupUser__user_exists(): void
    {
        $cause = new Cause(
            null,
            'test_'. wp_generate_password(),
        );
        $cause->save();

        $existingUser = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            'test: some string',
        );
        $existingUser->save();

        $auth = new Auth(
            $this->createMock(PhpSession::class),
            $this->createMock(AuthCookie::class),
        );

        $user = $auth->getOrSetupUser(
            $existingUser->email,
            'Should be ignored',
            'Should be ignored',
            EnumLang::EN,
            'Should be ignored',
            $cause->uuid,
        );

        self::assertSame($existingUser->uuid, $user->uuid);
        self::assertSame($existingUser->email, $user->email);
        self::assertSame('Jane', $user->firstName);
        self::assertSame('Doe', $user->lastName);
        self::assertSame(EnumLang::FR, $user->lang);
        self::assertSame('test: some string', $user->source);
        self::assertTrue($existingUser->hasCause($cause->uuid));
    }
}
