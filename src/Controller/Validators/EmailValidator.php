<?php

declare(strict_types=1);

namespace Collectme\Controller\Validators;

class EmailValidator
{
    public static function check(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        return false !== filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}