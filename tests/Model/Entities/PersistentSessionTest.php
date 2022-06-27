<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\PersistentSession;
use PHPUnit\Framework\TestCase;

class PersistentSessionTest extends TestCase
{

    public function test_getActive(): void
    {
        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $lastLogin = date_create('-2 days')->format(DATE_ATOM);
        $activated = date_create('-5 days')->format(DATE_ATOM);
        $userUuid = wp_generate_uuid4();

        $uuid = $this->insertTestTokenIntoDB(
            $userUuid,
            17,
            $lastLogin,
            $activationSecret,
            $sessionHash,
            $activated,
            null
        );

        $session = PersistentSession::getActive($uuid);

        $this->assertSame($uuid, $session->uuid);
        $this->assertSame($userUuid, $session->userUuid);
        $this->assertSame(17, $session->loginCounter);
        $this->assertSame($lastLogin, $session->lastLogin->format(DATE_ATOM));
        $this->assertSame($activationSecret, $session->activationSecret);
        $this->assertSame($sessionHash, $session->sessionHash);
        $this->assertSame($activated, $session->activated->format(DATE_ATOM));
        $this->assertNull($session->closed);
        $this->assertInstanceOf(\DateTime::class, $session->created);
        $this->assertInstanceOf(\DateTime::class, $session->updated);
        $this->assertNull($session->deleted);
    }

    private function insertTestTokenIntoDB(
        string $userUuid,
        int $loginCounter,
        string $lastLogin,
        string $activationSecret,
        string $sessionHash,
        ?string $activated,
        ?string $closed,
        ?string $deleted = null,
    ): string {
        global $wpdb;
        $uuid = wp_generate_uuid4();
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0;');
        $wpdb->query(
            "INSERT INTO {$wpdb->prefix}collectme_sessions (uuid, users_uuid, login_counter, last_login, activation_secret, session_hash) " .
            "VALUES ('$uuid', '$userUuid', '$loginCounter', '$lastLogin', '$activationSecret', '$sessionHash')"
        );
        if ($activated) {
            $wpdb->query("UPDATE {$wpdb->prefix}collectme_sessions SET activated_at = '$activated'");
        }
        if ($closed) {
            $wpdb->query("UPDATE {$wpdb->prefix}collectme_sessions SET closed_at = '$closed'");
        }
        if ($deleted) {
            $wpdb->query("UPDATE {$wpdb->prefix}collectme_sessions SET deleted_at = '$deleted'");
        }
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1;');

        return $uuid;
    }

    public function test_getActive__closed(): void
    {
        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $lastLogin = date_create('-2 days')->format(DATE_ATOM);
        $activated = date_create('-5 days')->format(DATE_ATOM);
        $closed = date_create('-1 days')->format(DATE_ATOM);
        $userUuid = wp_generate_uuid4();

        $uuid = $this->insertTestTokenIntoDB(
            $userUuid,
            17,
            $lastLogin,
            $activationSecret,
            $sessionHash,
            $activated,
            $closed
        );

        $this->expectException(CollectmeDBException::class);
        PersistentSession::getActive($uuid);
    }

    public function test_getActive__notActivated(): void
    {
        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $lastLogin = date_create('-2 days')->format(DATE_ATOM);
        $userUuid = wp_generate_uuid4();

        $uuid = $this->insertTestTokenIntoDB(
            $userUuid,
            17,
            $lastLogin,
            $activationSecret,
            $sessionHash,
            null,
            null
        );

        $this->expectException(CollectmeDBException::class);
        PersistentSession::getActive($uuid);
    }

    public function test_getActive__deleted(): void
    {
        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $lastLogin = date_create('-2 days')->format(DATE_ATOM);
        $activated = date_create('-5 days')->format(DATE_ATOM);
        $closed = date_create('-1 days')->format(DATE_ATOM);
        $deleted = date_create('-1 days')->format(DATE_ATOM);
        $userUuid = wp_generate_uuid4();

        $uuid = $this->insertTestTokenIntoDB(
            $userUuid,
            17,
            $lastLogin,
            $activationSecret,
            $sessionHash,
            $activated,
            $closed,
            deleted: $deleted
        );

        $this->expectException(CollectmeDBException::class);
        PersistentSession::getActive($uuid);
    }

    public function test_checkSessionSecret(): void
    {
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            null,
            wp_generate_uuid4(),
            5,
            date_create(),
            wp_generate_password(64, false, false),
            $sessionHash,
            date_create(),
            null
        );

        $this->assertTrue($session->checkSessionSecret($sessionSecret));
        $this->assertFalse($session->checkSessionSecret($sessionHash));
        $this->assertFalse($session->checkSessionSecret('asdf'));
    }

    public function test_fromApiModelToPropsArray(): void
    {
        $uuid = wp_generate_uuid4();
        $userUuid = wp_generate_uuid4();
        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);

        $apiData = [
            'id' => $uuid,
            'type' => 'session',
            'attributes' => [
                'loginCounter' => 6,
                'lastLogin' => date_create()->format(DATE_ATOM),
                'activated' => date_create()->format(DATE_ATOM),
                'created' => date_create()->format(DATE_ATOM),
                'updated' => date_create()->format(DATE_ATOM),
                'deleted' => date_create()->format(DATE_ATOM),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'id' => $userUuid,
                        'type' => 'user'
                    ]
                ],
            ]
        ];

        $props = PersistentSession::fromApiModelToPropsArray($apiData);

        /** @noinspection PhpParamsInspection */
        $session = new PersistentSession(
            ...$props,
            activationSecret: $activationSecret,
            sessionHash: $sessionHash,
            closed: null,
        );

        $this->assertSame($uuid, $session->uuid);
        $this->assertSame($userUuid, $session->userUuid);
        $this->assertSame($apiData['attributes']['loginCounter'], $session->loginCounter);
        $this->assertSame($apiData['attributes']['lastLogin'], $session->lastLogin->format(DATE_ATOM));
        $this->assertSame($apiData['attributes']['activated'], $session->activated->format(DATE_ATOM));
        $this->assertSame($apiData['attributes']['created'], $session->created->format(DATE_ATOM));
        $this->assertSame($apiData['attributes']['updated'], $session->updated->format(DATE_ATOM));
    }

    public function test_toApiModel(): void
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
            date_create('2022-06-26T22:00:00+00:00'),
        );

        $apiModel = $session->toApiModel();

        $this->assertSame($session->uuid, $apiModel->id);
        $this->assertSame('session', $apiModel->type);
        $this->assertSame(5, $apiModel->attributes['loginCounter']);
        $this->assertSame(date_create('2022-06-26T20:30:00+00:00')->format(DATE_RFC3339_EXTENDED), $apiModel->attributes['lastLogin']);
        $this->assertArrayNotHasKey('activationSecret', $apiModel->attributes);
        $this->assertArrayNotHasKey('sessionHash', $apiModel->attributes);
        $this->assertSame(date_create('2022-06-26T21:00:00+00:00')->format(DATE_RFC3339_EXTENDED), $apiModel->attributes['activated']);
        $this->assertArrayNotHasKey('closed', $apiModel->attributes);
        $this->assertArrayHasKey('created', $apiModel->attributes);
        $this->assertArrayHasKey('updated', $apiModel->attributes);
        $this->assertArrayNotHasKey('deleted', $apiModel->attributes);

        $this->assertSame($session->userUuid, $apiModel->relationships['user']['data']['id']);
        $this->assertSame('user', $apiModel->relationships['user']['data']['type']);
    }

    public function test_isActive__notActivated(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            wp_generate_password(64, false, false),
            'invalid',
            null,
            null,
        );

        $this->assertFalse($session->isActive());
    }

    public function test_isActive__activatedNotClosed(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            wp_generate_password(64, false, false),
            'invalid',
            date_create(),
            null,
        );

        $this->assertTrue($session->isActive());
    }

    public function test_isActive__activatedClosed(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            wp_generate_password(64, false, false),
            'invalid',
            date_create(),
            date_create(),
        );

        $this->assertFalse($session->isActive());
    }

    public function test_isActivated__true(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            wp_generate_password(64, false, false),
            'invalid',
            date_create(),
            null,
        );

        $this->assertTrue($session->isActivated());
    }

    public function test_isActivated__false(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            wp_generate_password(64, false, false),
            'invalid',
            null,
            null,
        );

        $this->assertFalse($session->isActivated());
    }

    public function test_isClosed__true(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            wp_generate_password(64, false, false),
            'invalid',
            null,
            date_create(),
        );

        $this->assertTrue($session->isClosed());
    }

    public function test_isClosed__false(): void
    {
        $session = new PersistentSession(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            0,
            null,
            wp_generate_password(64, false, false),
            'invalid',
            null,
            null,
        );

        $this->assertFalse($session->isClosed());
    }
}
