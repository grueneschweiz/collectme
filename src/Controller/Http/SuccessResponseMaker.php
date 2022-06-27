<?php

declare(strict_types=1);

namespace Collectme\Controller\Http;

use Collectme\Exceptions\CollectmeException;
use Collectme\Model\JsonApi\ApiConvertible;
use Collectme\Model\JsonApi\ApiError;

trait SuccessResponseMaker
{
    protected function makeSuccessResponse(int $statusCode, null|array|ApiConvertible $data): ResponseApiSuccess|ResponseApiError
    {
        try {
            if (is_array($data)) {
                $data = array_map(static fn($item) => $item->toApiModel(), $data);
            }

            return new ResponseApiSuccess($statusCode, $data?->toApiModel());
        } catch (CollectmeException|\ReflectionException $e) {
            return new ResponseApiError(
                500,
                [new ApiError(500, 'Internal Server Error', exception: $e)]
            );
        }
    }
}