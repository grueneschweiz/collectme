<?php

declare(strict_types=1);

namespace Collectme\Model\Database;

use Collectme\Exceptions\CollectmeDBException;

interface Persistable
{
    /**
     * @throws CollectmeDBException
     */
    public function save(): static;

    /**
     * @throws CollectmeDBException
     */
    public static function get(string $uuid, bool $deleted = false): static;

    /**
     * @throws CollectmeDBException
     */
    public function delete(): void;
}