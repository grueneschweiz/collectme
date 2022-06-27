<?php

declare(strict_types=1);

namespace Collectme\Controller\Http;

use Collectme\Model\JsonApi\ApiError;

class ResponseApiError extends \WP_REST_Response
{
    /**
     * @param int $status HTTP status code
     * @param ApiError[] $errors
     */
    public function __construct(int $status, array $errors)
    {
        $data = [
            'errors' => array_values($errors)
        ];

        parent::__construct($data, $status);
    }
}