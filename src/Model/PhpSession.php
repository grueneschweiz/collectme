<?php

declare(strict_types=1);

namespace Collectme\Model;

use Collectme\Model\Entities\PersistentSession;

use const Collectme\AUTH_SESSION_KEY;

class PhpSession
{
    public function get(): ?PersistentSession
    {
        session_start();

        if (empty($_SESSION[AUTH_SESSION_KEY])) {
            return null;
        }

        return $_SESSION[AUTH_SESSION_KEY];
    }

    public function set(PersistentSession $persistentSession): void
    {
        session_start();

        $_SESSION[AUTH_SESSION_KEY] = $persistentSession;
    }
}