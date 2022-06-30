<?php

declare(strict_types=1);

namespace Collectme\Model;

class Filter
{
    public function __construct(
        public ?string $field,
        public null|int|float|string|bool $value,
        public string $operator = '=',
    )
    {
    }

    public function addToQuery(string $query, array &$args): string
    {
        if ($this->field === null || $this->value === null) {
            return $query;
        }

        // warning, this is error-prone, but satisfies the current needs
        if (!str_contains($query, 'WHERE')) {
            $query .= ' WHERE ';
        } else {
            $query .= ' AND ';
        }

        $query .= $this->getSql();
        $args[] = $this->value;

        return $query;
    }

    private function getSql(): string
    {
        $placeholder = match(true) {
            is_int($this->value), is_bool($this->value) => '%d',
            is_float($this->value) => '%f',
            default => "'%s'",
        };

        return "{$this->field} {$this->operator} $placeholder";
    }
}