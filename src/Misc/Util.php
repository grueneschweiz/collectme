<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Model\Entities\EnumLang;

class Util
{
    private static \DateTimeZone $timezone;

    /**
     * Map EnumLang to the available locales. Lazily populated.
     *
     * @var Array<string, string>
     */
    private static array $localeMap;

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

    public static function determineLocale(EnumLang $lang): string
    {
        if (!isset(self::$localeMap[$lang->value])) {
            $candidates = array_filter(
                get_available_languages(),
                static fn($locale) => str_starts_with($locale, $lang->value)
            );

            if (function_exists('pll_the_languages')) {
                $polylangLocales = array_map(
                    static fn($pllLang) => str_replace('-', '_', $pllLang['locale']),
                    pll_the_languages( ['raw' => true ] ),
                );

                $candidates = array_intersect($candidates, $polylangLocales);
            }

            self::$localeMap[$lang->value] = empty($candidates)
                ? determine_locale()
                : array_values($candidates)[0];
        }

        return self::$localeMap[$lang->value];
    }
}