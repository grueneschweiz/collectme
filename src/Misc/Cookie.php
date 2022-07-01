<?php

declare(strict_types=1);

namespace Collectme\Misc;

class Cookie
{
    public function set(string $key, string $value, int $expires): void
    {
        setcookie($key, $value, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'secure' => true,
            'samesite' => 'Strict',
        ]);
    }

    public function get(string $key): ?string
    {
        return $_COOKIE[$key] ?? null;
    }
}