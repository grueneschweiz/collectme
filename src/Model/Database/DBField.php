<?php

declare(strict_types=1);

namespace Collectme\Model\Database;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DBField
{
    public function __construct(
        public ?string $name = null,
    ) {
    }
}