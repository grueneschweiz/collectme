<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\AuthCookie;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\PersistentSession;
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

    public function setPersistentSession(PersistentSession $persistentSession): void
    {
        $this->persistentSession = $persistentSession;
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

    private function loginWithAuthCookie(): void
    {
        $authCookie = $this->authCookie->get();

        if (!$authCookie) {
            return;
        }

        try {
            $session = PersistentSession::getActive($authCookie->getSessionUuid());
            if ($session->checkSessionSecret($authCookie->getSessionSecret())) {
                $validSession = $session;
            } else {
                return;
            }
        } catch (CollectmeDBException) {
            // no active session for given session uuid and secret
            return;
        }

        if ($validSession) {
            $this->persistentSession = $validSession;
            $this->phpSession->set($validSession);
        }
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
     */
    public function getOrCreateUserFromAccountToken(AccountToken $accountToken): User
    {
        if ($accountToken->userUuid) {
            return User::get($accountToken->userUuid);
        }

        try {
            $user = User::getByEmail($accountToken->email);
        } catch (CollectmeDBException) {
            $user = new User(
                null,
                $accountToken->email,
                $accountToken->firstName,
                $accountToken->lastName,
                $accountToken->lang,
                'Account Token'
            );
            $user->save();
        }

        $accountToken->userUuid = $user->uuid;
        $accountToken->save();

        return $user;
    }
}