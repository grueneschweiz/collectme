<?php

declare(strict_types=1);

namespace Collectme\Controller\Validators;

class UrlValidator
{
    public static function check(?string $url, ?string $protocol = null): bool
    {
        if (empty($url)) {
            return false;
        }

        if (!empty($protocol)
            && !str_starts_with(strtolower($url), strtolower($protocol))
        ) {
            return false;
        }

        return false !== filter_var($url, FILTER_VALIDATE_URL);
    }
}