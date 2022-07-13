<?php

declare(strict_types=1);

namespace Collectme\Controller\Validators;

class AuthCookieValidator
{
    public static function check(?string $cookie): bool
    {
        if (empty($cookie)) {
            return false;
        }

        if (2 !== count(explode(' ', $cookie))) {
            return false;
        }

        [$uuid, $token] = explode(' ', $cookie);

        return UuidValidator::check($uuid)
            && 1 === preg_match('/[[:alnum:]]{64}/', $token);
    }
}