<?php

declare(strict_types=1);

namespace Collectme\Model\JsonApi;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ApiModelType
{
    public function __construct(public string $typeName)
    {
    }
}