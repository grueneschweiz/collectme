<?php

declare(strict_types=1);

namespace Collectme\Model;

#[\Attribute(\Attribute::TARGET_CLASS)]
class DBTable
{
    public function __construct(
        public string $name,
    ) {
    }
}