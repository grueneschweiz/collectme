<?php

declare(strict_types=1);

namespace Collectme\Model;

trait DateTimeTypeHandler
{
    private static \DateTimeZone $timezone;

    private static function isDateTime(string $instancePropertyName): bool
    {
        try {
            $instanceProperty = new \ReflectionProperty(static::class, $instancePropertyName);
        } catch (\ReflectionException $e) {
            return false;
        }

        $dbAttributes = $instanceProperty->getAttributes(DateProperty::class);

        return !empty($dbAttributes);
    }

    /**
     * @param string|null $date
     * @return ?\DateTime
     */
    private static function convertToDateTime(?string $date): \DateTime|null
    {
        if (!$date) {
            return null;
        }

        return date_create($date, self::getTimeZone());
    }

    private static function getTimeZone(): \DateTimeZone
    {
        if (!isset(self::$timezone)) {
            self::$timezone = new \DateTimeZone(get_option('timezone_string'));
        }

        return self::$timezone;
    }

    private function convertDateTimeToString(null|\DateTime $value): ?string
    {
        return $value?->format(DATE_RFC3339_EXTENDED);
    }
}