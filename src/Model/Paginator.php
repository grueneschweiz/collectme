<?php

declare(strict_types=1);

namespace Collectme\Model;

class Paginator
{
    public function __construct(
        public int $perPage = 10,
        public ?string $cursor = null,
        public EnumPaginationCursorPointsTo $cursorPointsTo = EnumPaginationCursorPointsTo::LAST,
        public EnumPaginationOrder $order = EnumPaginationOrder::ASC,
    ) {
    }

    public function addToQuery(string $query, array &$args, string $paginatorField, string $table): string
    {
        if ($this->cursor) {
            // warning, this is error-prone, but satisfies the current needs
            if (!str_contains($query, 'WHERE')) {
                $query .= ' WHERE';
            } else {
                $query .= ' AND';
            }

            $query .= " $paginatorField {$this->getWhereOperator()} (SELECT $paginatorField FROM $table WHERE uuid = '%s')";
            $args[] = $this->cursor;
        }

        $query .= " ORDER BY $paginatorField {$this->getSqlOrder()}";
        $query .= " LIMIT {$this->perPage}";

        return "SELECT * FROM ({$query}) AS collectme_paginator_base_tbl ORDER BY $paginatorField {$this->order->value}";
    }

    private function getWhereOperator(): string
    {
        return match (true) {
            $this->cursorPointsTo === EnumPaginationCursorPointsTo::FIRST && $this->order === EnumPaginationOrder::ASC => '<',
            $this->cursorPointsTo === EnumPaginationCursorPointsTo::LAST && $this->order === EnumPaginationOrder::ASC => '>',
            $this->cursorPointsTo === EnumPaginationCursorPointsTo::FIRST && $this->order === EnumPaginationOrder::DESC => '>',
            $this->cursorPointsTo === EnumPaginationCursorPointsTo::LAST && $this->order === EnumPaginationOrder::DESC => '<',
        };
    }

    private function getSqlOrder(): string
    {
        return match (true) {
            $this->cursorPointsTo === EnumPaginationCursorPointsTo::FIRST && $this->order === EnumPaginationOrder::ASC => 'DESC',
            $this->cursorPointsTo === EnumPaginationCursorPointsTo::LAST && $this->order === EnumPaginationOrder::ASC => 'ASC',
            $this->cursorPointsTo === EnumPaginationCursorPointsTo::FIRST && $this->order === EnumPaginationOrder::DESC => 'ASC',
            $this->cursorPointsTo === EnumPaginationCursorPointsTo::LAST && $this->order === EnumPaginationOrder::DESC => 'DESC',
        };
    }
}
