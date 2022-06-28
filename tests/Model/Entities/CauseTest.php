<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Model\Entities\Cause;
use PHPUnit\Framework\TestCase;

class CauseTest extends TestCase
{

    public function test_get(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password()
        );
        $cause = $cause->save();

        $dbCause = Cause::get($cause->uuid);

        $this->assertSame($cause->uuid, $dbCause->uuid);
        $this->assertSame($cause->name, $dbCause->name);
    }

    public function test_save(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password()
        );
        $cause = $cause->save();

        $this->assertNotEmpty($cause->uuid);
        $this->assertNotEmpty($cause->created);
    }
}
