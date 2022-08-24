<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Model\AuthCookie;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\User;
use Collectme\Model\PhpSession;
use WP_REST_Request;


class Auth
{
    private static Auth $instance;
    private PersistentSession $persistentSession;

    public function __construct(
        private readonly PhpSession $phpSession,
        private readonly AuthCookie $authCookie,
    ) {
        self::$instance = $this;
    }

    /**
     * @throws CollectmeException
     */
    public static function getInstance(): Auth
    {
        if (!isset(self::$instance)) {
            throw new CollectmeException('Auth not initialized.');
        }
        return self::$instance;
    }

    /**
     * @throws CollectmeDBException
     */
    public function isAuthenticated(): bool
    {
        $session = $this->getPersistentSession();
        return $session && $session->isActive();
    }

    /**
     * @throws CollectmeDBException
     */
    public function getPersistentSession(): ?PersistentSession
    {
        if (!isset($this->persistentSession)) {
            $persistentSession = $this->phpSession->get();

            if ($persistentSession) {
                $this->persistentSession = $persistentSession;
            } else {
                $this->loginWithAuthCookie();
            }

            if (!isset($this->persistentSession)) {
                return null;
            }
        }

        return $this->persistentSession;
    }

    public function setPersistentSession(PersistentSession $persistentSession): void
    {
        $this->persistentSession = $persistentSession;
    }

    /**
     * @throws CollectmeDBException
     */
    private function loginWithAuthCookie(): void
    {
        $authCookie = $this->authCookie->get();

        if (!$authCookie) {
            return;
        }

        try {
            $session = PersistentSession::getActive($authCookie->getSessionUuid());
            if ($session->isActive()
                && $session->checkSessionSecret($authCookie->getSessionSecret())
            ) {
                $validSession = $session;
            } else {
                return;
            }
        } catch (CollectmeDBException) {
            // no active session for given session uuid
            return;
        }

        // note login
        if ($validSession->lastLogin < date_create('-10 seconds', Util::getTimeZone())) {
            ++$validSession->loginCounter;
        }
        $validSession->lastLogin = date_create('now', Util::getTimeZone());
        $validSession = $validSession->save();

        // set session
        $this->persistentSession = $validSession;
        $this->phpSession->set($validSession);
    }

    public function getClaimedSessionUuid(): ?string
    {
        return $this->authCookie->get()?->getSessionUuid();
    }

    /**
     * @throws CollectmeDBException
     */
    public function createPersistentSession(User $user, bool $activated): void
    {
        $activationSecret = wp_generate_password(64, false);
        $sessionSecret = wp_generate_password(64, false);
        $activatedAt = $activated ? date_create('now', Util::getTimeZone()) : null;

        $session = new PersistentSession(
            null,
            $user->uuid,
            0,
            null,
            $activationSecret,
            password_hash($sessionSecret, PASSWORD_DEFAULT),
            $activatedAt,
            null
        );

        $session->save();

        $this->persistentSession = $session;

        $this->authCookie->set($session->uuid, $sessionSecret);
    }

    /**
     * @throws CollectmeDBException
     * @throws \Exception
     */
    public function getOrSetupUserFromAccountToken(AccountToken $accountToken, string $causeUuid): User
    {
        if ($accountToken->userUuid) {
            $user = User::get($accountToken->userUuid);
            if (!$user->hasCause($causeUuid)) {
                $this->setupUserForCause($user, $causeUuid);
            }
            return $user;
        }

        try {
            $user = User::getByEmail($accountToken->email);
        } catch (CollectmeDBException) {
            return $this->createUserFromAccountToken($accountToken, $causeUuid);
        }

        $user->addCause($causeUuid);
        $accountToken->userUuid = $user->uuid;
        $accountToken->save();

        return $user;
    }

    /**
     * @throws CollectmeDBException
     * @throws \Exception
     */
    public function getOrSetupUser(
        string $email,
        string $firstName,
        string $lastName,
        EnumLang $lang,
        string $source,
        string $causeUuid
    ): User
    {
        try {
            $user = User::getByEmail($email);
        } catch (CollectmeDBException) {
            $user = new User(
                null,
                $email,
                $firstName,
                $lastName,
                $lang,
                $source,
            );
            $user = $user->save();
        }

        if (!$user->hasCause($causeUuid)) {
            $this->setupUserForCause($user, $causeUuid);
        }

        return $user;
    }

    /**
     * @throws \Exception
     */
    private function setupUserForCause(User $user, string $causeUuid): void
    {
        $group = null;
        DB::transactional(static function () use (&$user, &$group, $causeUuid) {
            $group = new Group(
                null,
                $user->firstName,
                EnumGroupType::PERSON,
                $causeUuid,
                false
            );
            $group->save();

            $role = new Role(
                null,
                $user->uuid,
                $group->uuid,
                EnumPermission::READ_WRITE
            );
            $role->save();

            $user->addCause($causeUuid);
        });

        do_action('collectme_after_user_setup', $user, $group->uuid, $causeUuid);
    }

    /**
     * @throws \Exception
     */
    private function createUserFromAccountToken(AccountToken $accountToken, string $causeUuid): User
    {
        return DB::transactional(static function () use ($accountToken, $causeUuid) {
            $user = new User(
                null,
                $accountToken->email,
                $accountToken->firstName,
                $accountToken->lastName,
                $accountToken->lang,
                'Account Token'
            );
            $user->save();

            $group = new Group(
                null,
                $accountToken->firstName,
                EnumGroupType::PERSON,
                $causeUuid,
                false
            );
            $group->save();

            $role = new Role(
                null,
                $user->uuid,
                $group->uuid,
                EnumPermission::READ_WRITE
            );
            $role->save();

            $user->addCause($causeUuid);
            $accountToken->userUuid = $user->uuid;
            $accountToken->save();

            return $user;
        });
    }

    /**
     * @throws CollectmeDBException
     * @throws CollectmeException
     */
    public function logout(): void
    {
        $session = $this->getPersistentSession();

        if (!$session) {
            throw new CollectmeException('Can not logout from session if not logged in.');
        }

        $session->closed = date_create('-1 second', Util::getTimeZone());
        $session->save();

        $this->authCookie->invalidate();
        $this->phpSession->reset();
    }

    /**
     * @throws CollectmeException
     */
    public function getUserUuid(): string
    {
        $session = $this->getPersistentSession();

        if (!$session) {
            throw new CollectmeException('Can not get user id if not logged in.');
        }

        return $session->userUuid;
    }

    public function isAuthenticatedAndHasValidNonce(WP_REST_Request $request): bool {
        return $this->check($request, [$this, 'isNonceValid'], [$this, 'isAuthenticated']);
    }

    public function isNonceValid(WP_REST_Request $request): bool
    {
        $nonce = $request->get_header('X-WP-Nonce');
        return 1 === wp_verify_nonce($nonce, 'wp_rest');
    }

    public function check(WP_REST_Request $request, callable ...$checks): bool
    {
        foreach($checks as $check) {
            if (!$check($request)) {
                return false;
            }
        }
        return true;
    }
}