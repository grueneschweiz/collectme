<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Exceptions\CollectmeException;

use const Collectme\DB_PREFIX;
use const Collectme\OPTION_KEY_DB_VERSION;

class DbInstaller
{
    /**
     * @throws CollectmeException
     */
    public static function removeTables(): void
    {
        // this function must be static, so we can use be used by the register_uninstall_hook

        global $wpdb;
        $prefix = $wpdb->prefix . DB_PREFIX;

        $tables = $wpdb->get_col("SHOW TABLES LIKE '$prefix%'");

        $queries = [];
        foreach ($tables as $table) {
            $queries[] = "DROP TABLE $table;";
        }

        self::executeTransactional($queries);
        delete_option(OPTION_KEY_DB_VERSION);
    }

    /**
     * @throws CollectmeException
     */
    public function setupTables(): void
    {
        // don't use dbDelta() as it doesn't support foreign key constraints (and has some other quirks too).

        $currentDbVersion = get_option(OPTION_KEY_DB_VERSION, '0');

        $sqlFileDir = COLLECTME_BASE_PATH . '/database';
        $sqlFiles = glob("$sqlFileDir/*.sql");

        foreach ($sqlFiles as $filePath) {
            $sqlFileVersion = str_ireplace('.sql', '', pathinfo($filePath, PATHINFO_FILENAME));
            if (0 > strcasecmp($currentDbVersion, $sqlFileVersion)) {
                $this->runSqlFromFile($filePath);
                update_option(OPTION_KEY_DB_VERSION, $sqlFileVersion);
            }
        }
    }

    /**
     * @throws CollectmeException
     */
    private function runSqlFromFile(string $pathToSqlFile): void
    {
        $sql = $this->getSql($pathToSqlFile);
        $sql = $this->stripComments($sql);
        $sql = $this->replaceSchemaAndTableNames($sql);

        $parts = $this->splitByDelimiter($sql);

        $statements = [];
        foreach ($parts as $part) {
            ['delimiter' => $delimiter, 'sql' => $partSql] = $part;

            $partSql = $this->removeDelimiterDefinitions($partSql);
            $partSql = $this->removeCreateSchemaStatements($partSql, $delimiter);
            $partSql = $this->removeUseStatements($partSql, $delimiter);
            $partSql = $this->stripEmptyLines($partSql);

            $partStatements = $this->splitStatements($partSql, $delimiter);
            foreach ($partStatements as $statement) {
                $statement = $this->stripTrailingDelimiter($statement, $delimiter);
                $statements[] = $this->cleanupWhitespace($statement);
            }
        }

        self::executeTransactional($statements);
    }

    /**
     * @throws CollectmeException
     */
    private function getSql(string $pathToSqlFile): string
    {
        $sql = file_get_contents($pathToSqlFile);

        if (!$sql) {
            throw new CollectmeException("Failed to read file: $pathToSqlFile");
        }

        return $sql;
    }

    private function stripComments(string $sql): array|string|null
    {
        // line comments
        $sql = preg_replace(
            '/-- .*$/m',
            '',
            $sql
        );

        // block comments
        return preg_replace(
            '/\/\*.*(?=\*\/)\*\//s',
            '',
            $sql
        );
    }

    private function replaceSchemaAndTableNames(string $sql): string
    {
        global $wpdb;
        $prefix = $wpdb->prefix . DB_PREFIX;

        return preg_replace(
            "/`collectme`.`([^`]+)`/",
            "`$prefix$1`",
            $sql
        );
    }

    /**
     * @param string $sql
     * @return array{array{delimiter: string, sql: string}}
     */
    private function splitByDelimiter(string $sql): array
    {
        $defaultDelimiter = ';';

        preg_match_all(
            '/^\s*?DELIMITER\s+(.*?)\s*$/m',
            $sql,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if (empty($matches[0])) {
            return [
                ['delimiter' => $defaultDelimiter, 'sql' => $sql]
            ];
        }

        $parts = [];
        $delimiter = $defaultDelimiter;

        $partStart = 0;
        foreach ($matches[0] as $index => $match) {
            $partEnd = $match[1];

            $parts[] = [
                'delimiter' => $delimiter,
                'sql' => substr($sql, $partStart, $partEnd - $partStart)
            ];

            $delimiter = $matches[1][$index][0];
            $partStart = $partEnd;
        }

        $parts[] = [
            'delimiter' => $delimiter,
            'sql' => substr($sql, $partStart)
        ];

        return $parts;
    }

    private function removeDelimiterDefinitions(string $sql): string
    {
        return preg_replace('/^\s*?DELIMITER\s+(.*?)\s*$/m', '', $sql);
    }

    private function removeCreateSchemaStatements(string $sql, string $sqlDelimiter): string
    {
        $sqlDelimiter = preg_quote($sqlDelimiter, '/');
        return preg_replace(
            "/CREATE\s+SCHEMA\s.+?$sqlDelimiter/",
            '',
            $sql
        );
    }

    private function removeUseStatements(string $sql, string $sqlDelimiter): string
    {
        $sqlDelimiter = preg_quote($sqlDelimiter, '/');
        return preg_replace(
            "/USE\s.+?$sqlDelimiter/",
            '',
            $sql
        );
    }

    private function stripEmptyLines(string $sql): string
    {
        return trim(
            preg_replace(
                '/^\s*$/m',
                '',
                $sql
            )
        );
    }

    private function splitStatements(string $sql, string $delimiter): array
    {
        return array_map(
            static fn($statement) => trim("$statement$delimiter"),
            explode("$delimiter\n", $sql)
        );
    }

    private function stripTrailingDelimiter(string $sql, mixed $sqlDelimiter): string
    {
        $sqlDelimiter = preg_quote($sqlDelimiter, '/');
        return preg_replace('/' . $sqlDelimiter . '+$/', '', $sql);
    }

    private function cleanupWhitespace(string $sql): string
    {
        return preg_replace('/\s+/', ' ', $sql);
    }

    /**
     * @throws CollectmeException
     */
    private static function executeTransactional(array $queries): void
    {
        // this function must be static, so we can use be used by the register_uninstall_hook

        global $wpdb;

        $wpdb->query('SET autocommit = 0;');
        $wpdb->query('START TRANSACTION');

        foreach ($queries as $sql) {
            if (false === $wpdb->query($sql)) {
                $error = $wpdb->last_error;
                $wpdb->query('ROLLBACK;');
                $wpdb->query('SET autocommit = 1;');
                throw new CollectmeException($error);
            }
        }

        $wpdb->query('COMMIT;');
        $wpdb->query('SET autocommit = 1;');
    }
}