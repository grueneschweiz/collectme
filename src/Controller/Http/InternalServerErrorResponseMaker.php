<?php

declare(strict_types=1);

namespace Collectme\Controller\Http;

use Collectme\Model\JsonApi\ApiError;
use WP_REST_Response;

trait InternalServerErrorResponseMaker
{
    public function makeInternalServerErrorResponse(\Exception $e): WP_REST_Response
    {
        return new ResponseApiError(
            500,
            [new ApiError(500, 'Internal Server Error', exception: $e)]
        );
    }
}