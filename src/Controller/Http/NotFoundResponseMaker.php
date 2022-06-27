<?php

declare(strict_types=1);

namespace Collectme\Controller\Http;

use Collectme\Model\JsonApi\ApiError;

trait NotFoundResponseMaker
{
    private function makeNotFoundResponse(): \WP_REST_Response
    {
        return new ResponseApiError(
            404,
            [new ApiError(404, 'Not Found')]
        );
    }
}