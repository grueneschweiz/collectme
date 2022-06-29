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
                array_walk_recursive($data, static function (&$value) {
                    if ($value instanceof ApiConvertible) {
                        $value = $value->toApiModel();
                    }
                });
            } elseif ($data instanceof ApiConvertible) {
                $data = $data->toApiModel();
            }

            return new ResponseApiSuccess($statusCode, $data);
        } catch (CollectmeException|\ReflectionException $e) {
            return new ResponseApiError(
                500,
                [new ApiError(500, 'Internal Server Error', exception: $e)]
            );
        }
    }
}