<?php

declare(strict_types=1);

namespace Collectme\Controller\Http;

use Collectme\Model\JsonApi\ApiError;

trait ValidationErrorResponseMaker
{
    private function makeValidationErrorResponse(array $pointers = [], array $parameters = []): \WP_REST_Response
    {
        $errors = [];

        foreach ($pointers as $pointer) {
            $errors[] = new ApiError(
                422,
                'Unprocessable Entity',
                null,
                $pointer,
                null,
            );
        }

        foreach ($parameters as $parameter) {
            $errors[] = new ApiError(
                422,
                'Unprocessable Entity',
                null,
                null,
                $parameter
            );
        }

        return new ResponseApiError(
            422,
            $errors
        );
    }
}