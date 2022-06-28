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

    public function reset(): void
    {
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 1,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        unset($_SESSION[AUTH_SESSION_KEY]);
        session_regenerate_id(true);
    }
}