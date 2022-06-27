<?php

declare(strict_types=1);

namespace Collectme\Controller\Http;

use Collectme\Model\JsonApi\ApiModel;

class ResponseApiSuccess extends \WP_REST_Response
{
    /**
     * @param int $status HTTP status code
     * @param null|ApiModel|ApiModel[] $models
     */
    public function __construct(int $status, null|ApiModel|array $models)
    {
        parent::__construct($models, $status);
    }

    /**
     * @param mixed $data
     */
    public function set_data(mixed $data): void
    {
        if (!(is_array($data) && array_key_exists('data', $data))) {
            $data = [
                'data' => $data
            ];
        }

        parent::set_data($data);
    }


}