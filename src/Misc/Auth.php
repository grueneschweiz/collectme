<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Model\AuthCookie;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\User;
use Collectme\Model\PhpSession;

class Auth
{
    private PersistentSession $persistentSession;

    public function __construct(
        private readonly PhpSession $phpSession,
        private readonly AuthCookie $authCookie,
    ) {
    }

    public function isAuthenticated(): bool
    {
        $session = $this->getPersistentSession();
        return $session && $session->isActive();
    }

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
            // no active session for given session uuid and secret
            return;
        }

        // note login
        ++$validSession->loginCounter;
        $validSession->lastLogin = date_create();
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
        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $activatedAt = $activated ? date_create() : null;

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
     * @throws \Exception
     */
    private function setupUserForCause(User $user, string $causeUuid): void
    {
        DB::transactional(static function () use ($user, $causeUuid) {
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

            return $user;
        });
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

        $session->closed = date_create('-1 second');
        $session->save();

        $this->authCookie->invalidate();
        $this->phpSession->reset();
    }

    public function getUserUuid(): string
    {
        $session = $this->getPersistentSession();

        if (!$session) {
            throw new CollectmeException('Can not get user id if not logged in.');
        }

        return $session->userUuid;
    }
}