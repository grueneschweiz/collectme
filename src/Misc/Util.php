<?php

declare(strict_types=1);

namespace Collectme\Misc;

class Util
{
    private static \DateTimeZone $timezone;

    public static function getTimeZone(): \DateTimeZone
    {
        if (!isset(self::$timezone)) {
            try {
                self::$timezone = new \DateTimeZone(get_option('timezone_string'));
            } catch (\Exception) {
                self::$timezone = new \DateTimeZone(date_default_timezone_get());
            }
        }

        return self::$timezone;
    }
}