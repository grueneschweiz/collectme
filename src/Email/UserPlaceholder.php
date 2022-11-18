<?php

declare(strict_types=1);

namespace Collectme\Email;

use Collectme\Model\Entities\User;

trait UserPlaceholder
{
    private function replaceUserPlaceholder(string $msg, User $user): string
    {
        $replacements = [
            '{{userUuid}}' => $user->uuid,
            '{{firstName}}' => $user->firstName,
            '{{lastName}}' => $user->lastName,
            '{{userEmail}}' => $user->email
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $msg
        );
    }
}