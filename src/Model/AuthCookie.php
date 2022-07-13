<?php

declare(strict_types=1);

namespace Collectme\Model;

use Collectme\Controller\Validators\AuthCookieValidator;
use Collectme\Misc\Cookie;

use const Collectme\AUTH_COOKIE_KEY;
use const Collectme\AUTH_COOKIE_TTL;

class AuthCookie
{
    private string $sessionUuid;
    private string $sessionSecret;

    public function __construct(
        private readonly Cookie $cookie
    ) {
    }

    public function set(string $sessionUuid, string $sessionSecret): void
    {
        $this->sessionUuid = $sessionUuid;
        $this->sessionSecret = $sessionSecret;

        $this->cookie->set(
            AUTH_COOKIE_KEY,
            "$sessionUuid $sessionSecret",
            date_create(AUTH_COOKIE_TTL)->getTimestamp()
        );
    }

    public function get(): ?AuthCookie
    {
        if (!empty($this->sessionUuid)
            && !empty($this->sessionSecret)) {
            return $this;
        }

        if (!$this->authCookieIsSet()) {
            return null;
        }

        if (!$this->authCookieFormatIsValid()) {
            return null;
        }

        $data = explode(' ', $this->cookie->get(AUTH_COOKIE_KEY));
        $this->sessionUuid = $data[0];
        $this->sessionSecret = $data[1];

        return $this;
    }

    private function authCookieIsSet(): bool
    {
        return (bool)$this->cookie->get(AUTH_COOKIE_KEY);
    }

    private function authCookieFormatIsValid(): bool
    {
        return AuthCookieValidator::check(
            $this->cookie->get(AUTH_COOKIE_KEY)
        );
    }

    public function getSessionUuid(): string
    {
        return $this->sessionUuid;
    }

    public function getSessionSecret(): string
    {
        return $this->sessionSecret;
    }

    public function invalidate(): void
    {
        $this->cookie->set(
            AUTH_COOKIE_KEY,
            '',
            time() - 1
        );
    }
}