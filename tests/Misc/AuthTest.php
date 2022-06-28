<?php

declare(strict_types=1);

namespace Misc;

use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Auth;
use Collectme\Misc\Cookie;
use Collectme\Model\AuthCookie;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\User;
use Collectme\Model\PhpSession;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{

    public function test_getPersistentSession__fromPhpSession(): void
    {
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            5,
            date_create('2022-06-26T20:30:00+00:00'),
            wp_generate_password(64, false, false),
            $sessionHash,
            date_create('2022-06-26T21:00:00+00:00'),
            null,
        );

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $phpSession->expects($this->once())
            ->method('get')
            ->willReturn($session);

        $auth = new Auth($phpSession, $authCookie);

        $this->assertSame($session, $auth->getPersistentSession());
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

    public function test_getOrCreateUserFromAccountToken__linkedUser(): void
    {
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

        $user = $auth->getOrCreateUserFromAccountToken($accountToken);

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

    public function test_getOrCreateUserFromAccountToken__getByEmail(): void
    {
        $email = $this->uniqueEmail();
        $userUuid = $this->insertTestUserIntoDB($email, 'first', 'last', 'e', 'test');

        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, $email, 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::get($uuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $user = $auth->getOrCreateUserFromAccountToken($accountToken);

        // test correct user
        $this->assertSame($userUuid, $user->uuid);
    }

    public function test_getOrCreateUserFromAccountToken__createUser(): void
    {
        $email = $this->uniqueEmail();
        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, $email, 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::get($uuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $user = $auth->getOrCreateUserFromAccountToken($accountToken);

        // test correct user
        $this->assertSame($email, $user->email);
    }

    public function test_getOrCreateUserFromAccountToken__autolinking(): void
    {
        $email = $this->uniqueEmail();
        $userUuid = $this->insertTestUserIntoDB($email, 'first', 'last', 'e', 'test');

        $token = wp_generate_password(64, false, false);
        $validUntil = date_create('+5 years')->format(DATE_ATOM);
        $uuid = $this->insertTestTokenIntoDB($token, $email, 'Jane', 'Doe', 'd', $validUntil);

        $accountToken = AccountToken::get($uuid);

        $phpSession = $this->createMock(PhpSession::class);
        $authCookie = $this->createMock(AuthCookie::class);

        $auth = new Auth($phpSession, $authCookie);

        $auth->getOrCreateUserFromAccountToken($accountToken);

        $accountToken = AccountToken::get($uuid);
        $this->assertSame($userUuid, $accountToken->userUuid);
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
}
