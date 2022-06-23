<?php

declare(strict_types=1);

namespace Collectme\Model\JsonApi;

interface ApiConvertible
{
    public function toApiModel(): ApiModel;

    public function toApiBaseModel(): ApiModel;

    public static function fromApiModelToPropsArray(array $apiModel): array;
}