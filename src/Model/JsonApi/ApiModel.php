<?php

declare(strict_types=1);

namespace Collectme\Model\JsonApi;

use JsonSerializable;

class ApiModel implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $type,
        public ?array $attributes = null,
        public ?array $relationships = null,
        public ?array $links = null,
        public ?array $meta = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => $this->type
        ];

        foreach (['attributes', 'relationships', 'links', 'meta'] as $type) {
            if (!empty($this->$type)) {
                $data[$type] = $this->$type;
            }
        }

        return $data;
    }
}