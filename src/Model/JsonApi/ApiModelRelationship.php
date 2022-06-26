<?php

declare(strict_types=1);

namespace Collectme\Model\JsonApi;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ApiModelRelationship
{
    public function __construct(
        public string $classFQN,
    ) {
    }
}