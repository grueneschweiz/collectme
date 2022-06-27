<?php

declare(strict_types=1);

namespace Collectme\Model\JsonApi;

use JsonSerializable;

class ApiError implements JsonSerializable
{
    public function __construct(
        public int $status, 
        public string $title,
        public ?string $detail = null,
        public ?string $pointer = null,
        public ?string $parameter = null,
        public ?\Throwable $exception = null,
    )
    {
    }

    public function __toString(): string
    {
        /** @noinspection JsonEncodingApiUsageInspection */
        return json_encode($this->jsonSerialize());
    }

    public function jsonSerialize(): array
    {
        $data = [
            'status' => $this->status,
            'title' => $this->title,
        ];

        if (!empty($this->detail)) {
            $data['detail'] = $this->detail;
        }

        if (!empty($this->pointer) || !empty($this->parameter)) {
            $data['source'] = [];
        }

        if (!empty($this->pointer)) {
            $data['source']['pointer'] = $this->pointer;
        }

        if (!empty($this->parameter)) {
            $data['source']['parameter'] = $this->parameter;
        }

        if (!empty($this->exception) && defined('WP_DEBUG') && WP_DEBUG ) {
            $data['meta']['exception'] = $this->exception;
        }

        return $data;
    }
}