<?php

declare(strict_types=1);

namespace Collectme\Controller\Validators;

class StringValidator
{
    public static function check(?string $string, int $minLen = 0, int $maxLen = 0):bool
    {
        if (!is_string($string)) {
            return false;
        }

        if ($minLen > 0 && strlen($string) < $minLen) {
            return false;
        }

        if ($maxLen > 0 && strlen($string) > $maxLen) {
            return false;
        }

        return $string === strip_tags($string);
    }
}