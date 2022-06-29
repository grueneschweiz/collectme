<?php

declare(strict_types=1);

namespace Collectme\Misc;

class DB
{
    /**
     * @throws \Exception
     */
    public static function transactional(callable $callback): mixed
    {
        global $wpdb;
        $wpdb->query('SET autocommit = 0;');
        $wpdb->query('START TRANSACTION;');

        try{
            $result = $callback();
            $wpdb->query('COMMIT;');
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK;');
            throw $e;
        } finally {
            $wpdb->query('SET autocommit = 1;');
        }

        return $result;
    }
}