<?php

declare(strict_types=1);

namespace Collectme\Controller\Validators;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\Cause;

class CauseUuidValidator
{
    public static function check(?string $uuid): bool
    {
        if (!UuidValidator::check($uuid)) {
            return false;
        }

        try {
            Cause::get($uuid);
        } catch (CollectmeDBException) {
            return false;
        }

        return true;
    }
}