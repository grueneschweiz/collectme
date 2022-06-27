<?php

declare(strict_types=1);

namespace Controller\Http;

use Collectme\Controller\Http\UuidValidator;
use PHPUnit\Framework\TestCase;

class UuidValidatorTest extends TestCase
{

    public function test_check__valid(): void
    {
        $this->assertTrue(
            UuidValidator::check(wp_generate_uuid4())
        );
    }

    public function test_check__invalid(): void
    {
        $this->assertFalse(
            UuidValidator::check('z'.substr(wp_generate_uuid4(), 1))
        );
    }
}
