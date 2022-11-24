<?php

declare(strict_types=1);

namespace Unit\Misc;

use Collectme\Misc\Util;
use Collectme\Model\Entities\EnumLang;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    /**
     * @dataProvider provideDetermineLocale
     */
    public function test_determineLocale(EnumLang $lang, string $expected): void
    {
        $this->assertEquals($expected, Util::determineLocale($lang));
    }

    public function provideDetermineLocale(): array
    {
        return [
            'de' => [EnumLang::DE, 'de_DE'],
            'en' => [EnumLang::EN, 'en_GB'],
            'fr' => [EnumLang::FR, 'en_US'], // not installed
        ];
    }
}
