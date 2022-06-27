<?php

declare(strict_types=1);

namespace Collectme\Controller\Http;

use Collectme\Model\JsonApi\ApiError;

trait UnauthorizedResponseMaker
{
    private function makeUnauthorizedResponse(): \WP_REST_Response
    {
        return new ResponseApiError(
            401,
            [new ApiError(401, 'Unauthorized')]
        );
    }
}