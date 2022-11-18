<?php

declare(strict_types=1);

namespace Email;

use Collectme\Email\LazyReplacer;
use PHPUnit\Framework\TestCase;

class LazyReplacerTest extends TestCase
{
    use LazyReplacer;

    public function test_lazyReplace(): void
    {
        $replacements = [
            '{{resolved}}' => 'present',
            '{{closure}}' => static fn() => 'found',
            '{{other}}' => static fn() => throw new \RuntimeException('this should not be called'),
        ];
        $template = '{{missing}} {{resolved}} {{closure}}';
        $expected = '{{missing}} present found';

        $result = $this->lazyReplace($template, $replacements);
        self::assertSame($expected, $result);
    }
}
