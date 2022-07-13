<?php

declare(strict_types=1);

namespace Collectme\Controller\Validators;

class TokenValidator
{
    public static function check(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        return 1 === preg_match('/^[[:alnum:]]{64}$/', $token);
    }
}