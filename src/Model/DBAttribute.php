<?php

declare(strict_types=1);

namespace Collectme\Model;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DBAttribute
{
    public function __construct(
        public ?string $name = null,
    ) {
    }
}