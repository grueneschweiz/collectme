<?php

declare(strict_types=1);

namespace Collectme\Model\Database;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Util;
use Collectme\Model\DateTimeTypeHandler;

use const Collectme\DB_PREFIX;


trait Persister
{
    use DateTimeTypeHandler;

    /**
     * @throws CollectmeDBException
     */
    public function save(): static
    {
        if ($this->uuid) {
            $this->update();
        } else {
            $this->insert();
        }

        return self::get($this->uuid);
    }

    /**
     * @throws CollectmeDBException
     */
    private function update(): void
    {
        global $wpdb;

        $data = $this->convertDataForDb();

        $count = $wpdb->update(
            self::getTableName(),
            $data,
            ['uuid' => $this->uuid],
            $this->getFormatStrings($data),
            '%s'
        );

        if (false === $count) {
            throw new CollectmeDBException('Failed to update ' . static::class . ": $wpdb->last_error");
        }
    }

    private function convertDataForDb(): array
    {
        $propertiesMap = self::getInstanceDbPropertiesMap();

        $props = [];
        foreach ($propertiesMap as $propertyMap) {
            $props[$propertyMap['dbFieldName']] = $this->getConvertedValueForDb($propertyMap['instancePropertyName']);
        }

        return $props;
    }

    /**
     * Get array that maps instance property names to the database field names.
     *
     * Properties without the #[DBAttribute] attribute are ignored.
     *
     * @return array{
     *     array{
     *          instancePropertyName: string,
     *          dbFieldName: string
     *    }
     * }
     */
    private static function getInstanceDbPropertiesMap(): array
    {
        $instanceProperties = (new \ReflectionClass(static::class))->getProperties();

        $map = [];
        foreach ($instanceProperties as $instanceProperty) {
            $instancePropertyName = $instanceProperty->getName();

            $dbAttributes = $instanceProperty->getAttributes(DBField::class);

            if (empty($dbAttributes)) {
                continue;
            }

            $dbFieldName = $dbAttributes[0]->newInstance()->name;

            if (empty($dbFieldName)) {
                $dbFieldName = $instancePropertyName;
            }

            $map[] = [
                'instancePropertyName' => $instancePropertyName,
                'dbFieldName' => $dbFieldName,
            ];
        }

        return $map;
    }

    /**
     * The property value, or the result of its db getter, if one exists.
     *
     * The db getter takes precedence over the property itself. The getter must
     * use the following naming pattern: _convertDb{InstancePropertyName}. So the db
     * getter for a property called 'created' has to be called _convertDbCreated.
     *
     * If a general getter, _convert{InstancePropertyName} exists but no db getter
     * the general getter is used. Where no getter exists, the unconverted value
     * of the property is returned.
     *
     * @param string $instancePropertyName
     * @return mixed
     */
    private function getConvertedValueForDb(string $instancePropertyName): mixed
    {
        $getterName = '_convertToDb' . ucfirst($instancePropertyName);
        if (method_exists($this, $getterName)) {
            return $this->$getterName();
        }

        $getterName = '_convertTo' . ucfirst($instancePropertyName);
        if (method_exists($this, $getterName)) {
            return $this->$getterName();
        }

        if (self::isDateTime($instancePropertyName)) {
            return $this->convertDateTimeToString($this->$instancePropertyName);
        }

        return $this->$instancePropertyName;
    }

    protected static function getTableName(): string
    {
        global $wpdb;

        $tableAttributes = (new \ReflectionClass(static::class))->getAttributes(DBTable::class);
        $tableBaseName = $tableAttributes[0]->newInstance()->name;

        return $wpdb->prefix . DB_PREFIX . $tableBaseName;
    }

    /**
     * @throws CollectmeDBException
     */
    private function getFormatStrings(array $data): array
    {
        $formatStrings = [];

        foreach ($data as $field => $value) {
            if (!is_scalar($value) && !is_null($value)) {
                throw new CollectmeDBException("Invalid type for $field: " . gettype($value));
            }

            $formatStrings[] = match (gettype($value)) {
                'boolean', 'integer' => '%d',
                'double' => '%f',
                default => '%s'
            };
        }

        return $formatStrings;
    }

    /**
     * @throws CollectmeDBException
     */
    private function insert(): void
    {
        global $wpdb;

        $data = $this->convertDataForDb();

        // manually set uuid, as we can't get it back from the
        // database if we let the trigger create it on insert
        $data['uuid'] = wp_generate_uuid4();

        $count = $wpdb->insert(
            self::getTableName(),
            $data,
            $this->getFormatStrings($data)
        );

        if (1 !== $count) {
            throw new CollectmeDBException('Failed to insert ' . static::class . ": $wpdb->last_error");
        }

        $this->uuid = $data['uuid'];
    }

    /**
     * @throws CollectmeDBException
     */
    public static function get(string $uuid, bool $deleted = false): static
    {
        global $wpdb;

        $deletedQuery = $deleted ? '' : ' AND deleted_at IS NULL';

        $query = $wpdb->prepare(
            "SELECT * FROM " . self::getTableName() . " WHERE uuid = '%s' $deletedQuery",
            $uuid
        );

        return static::getByQuery($query);
    }

    /**
     * @throws CollectmeDBException
     */
    protected static function getByQuery(string $query): static
    {
        global $wpdb;

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            throw new CollectmeDBException('Failed to get ' . static::class . ": $query");
        }

        return new static(...self::convertFieldsFromDb($result));
    }

    /**
     * @return static[]
     * @throws CollectmeDBException
     */
    public static function getMany(array $uuids): array {
        global $wpdb;

        $uuids = array_unique($uuids);
        $count = count($uuids);

        if ($count === 0) {
            return [];
        }

        $groupsTbl = self::getTableName();
        $placeholders = implode(',', array_fill(0, $count, '%s'));

        $entities = self::findByQuery(
            $wpdb->prepare(
                "SELECT * FROM $groupsTbl WHERE uuid IN ($placeholders) AND deleted_at IS NULL",
                ...$uuids
            )
        );

        if ($count !== count($entities)) {
            throw new CollectmeDBException('Failed to getMany ' . static::class . ": Cannot find entities for all given uuids.");
        }

        return $entities;
    }

    /**
     * @param string $query
     * @return static[]
     * @throws CollectmeDBException
     */
    protected static function findByQuery(string $query): array
    {
        global $wpdb;

        $result = $wpdb->get_results($query, ARRAY_A);

        if ($wpdb->error || $wpdb->last_error) {
            throw new CollectmeDBException('Failed to find ' . static::class . ": $wpdb->last_error");
        }

        if (empty($result)) {
            return [];
        }

        return array_map(
            static fn($item) => new static(...self::convertFieldsFromDb($item)),
            $result
        );
    }

    protected static function convertFieldsFromDb(array $data): array
    {
        $propertiesMap = self::getInstanceDbPropertiesMap();

        $props = [];
        foreach ($propertiesMap as $propertyMap) {
            $props[$propertyMap['instancePropertyName']] = self::convertFieldFromDb(
                $propertyMap['instancePropertyName'],
                $data[$propertyMap['dbFieldName']]
            );
        }

        return $props;
    }

    private static function convertFieldFromDb(string $instancePropertyName, mixed $value): mixed
    {
        $getterName = '_convertFromDb' . ucfirst($instancePropertyName);
        if (method_exists(self::class, $getterName) || method_exists(static::class, $getterName)) {
            return static::$getterName($value);
        }

        $getterName = '_convertFrom' . ucfirst($instancePropertyName);
        if (method_exists(self::class, $getterName) || method_exists(static::class, $getterName)) {
            return static::$getterName($value);
        }

        if (self::isDateTime($instancePropertyName)) {
            return self::convertToDateTime($value);
        }

        return $value;
    }

    /**
     * @throws CollectmeDBException
     */
    public function delete(): void
    {
        $this->deleted = date_create('-1 second', Util::getTimeZone());

        $this->update();
    }
}