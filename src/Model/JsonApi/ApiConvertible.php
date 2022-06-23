<?php

declare(strict_types=1);

namespace Collectme\Model\JsonApi;

use JetBrains\PhpStorm\ArrayShape;

interface ApiConvertible
{
    #[ArrayShape(['id' => 'string', 'type' => 'string', 'attributes' => 'array'])]
    public function toApiModel(): array;

    #[ArrayShape(['id' => 'string', 'type' => 'string'])]
    public function toApiBaseModel(): array;

    public static function fromApiModelToPropsArray(array $apiModel): array;
}