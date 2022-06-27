<?php

declare(strict_types=1);

namespace Collectme\Controller\Http;

class UuidValidator
{
    public static function check(string $uuid): bool
    {
        return (bool)preg_match(
            '/^[\da-f]{8}-[\da-f]{4}-[0-5][\da-f]{3}-[089ab][\da-f]{3}-[\da-f]{12}$/i',
            $uuid
        );
    }
}