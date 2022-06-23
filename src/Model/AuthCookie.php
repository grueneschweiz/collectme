<?php

declare(strict_types=1);

namespace Collectme\Model;

use const Collectme\AUTH_COOKIE_KEY;
use const Collectme\AUTH_COOKIE_TTL;

class AuthCookie
{
    private string $sessionUuid;
    private string $sessionSecret;

    public function set(string $sessionUuid, string $sessionSecret): void
    {
        $this->sessionUuid = $sessionUuid;
        $this->sessionSecret = $sessionSecret;

        setcookie(
            AUTH_COOKIE_KEY,
            "$sessionUuid $sessionSecret",
            date_create(AUTH_COOKIE_TTL)->getTimestamp(),
            secure: true,
            httponly: true
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

        $data = explode(' ', $_COOKIE[AUTH_COOKIE_KEY]);
        $this->sessionUuid = $data[0];
        $this->sessionSecret = $data[1];

        return $this;
    }

    private function authCookieIsSet(): bool
    {
        return !empty($_COOKIE[AUTH_COOKIE_KEY]);
    }

    private function authCookieFormatIsValid(): bool
    {
        return preg_match('/[[[:alnum:]]-]{36} [[:alnum:]]{64}/', $_COOKIE[AUTH_COOKIE_KEY]);
    }

    public function getSessionUuid(): string
    {
        return $this->sessionUuid;
    }

    public function getSessionSecret(): string
    {
        return $this->sessionSecret;
    }
}