<?php

declare(strict_types=1);

namespace Collectme\Model\Database;

use Collectme\Exceptions\CollectmeDBException;
use Ramsey\Uuid\Uuid;

use const Collectme\DB_PREFIX;


trait Persister
{
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

        if (1 !== $count) {
            throw new CollectmeDBException('Failed to update ' . static::class . ": $wpdb->last_error");
        }
    }

    private function convertDataForDb(): array
    {
        $propertiesMap = self::getInstanceDbPropertiesMap();

        $props = [];
        foreach ($propertiesMap as $propertyMap) {
            $props[$propertyMap['dbFieldName']] = $this->convertFieldValueForDb($propertyMap['instancePropertyName']);
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
     * @param string $instancePropertyName
     * @return mixed
     */
    private function convertFieldValueForDb(string $instancePropertyName): mixed
    {
        $dbGetterName = '_convertDb' . ucfirst($instancePropertyName);
        if (method_exists($this, $dbGetterName)) {
            return $this->$dbGetterName();
        }

        if (self::isDate($instancePropertyName)) {
            return $this->convertDateToString($this->$instancePropertyName);
        }

        return $this->$instancePropertyName;
    }

    private static function isDate(string $instancePropertyName): bool
    {
        try {
            $instanceProperty = new \ReflectionProperty(static::class, $instancePropertyName);
        } catch (\ReflectionException $e) {
            return false;
        }

        $dbAttributes = $instanceProperty->getAttributes(DBFieldDate::class);

        return !empty($dbAttributes);
    }

    private function convertDateToString(null|\DateTime $value): ?string
    {
        return $value?->format('Y-m-d H:i:s');
    }

    protected static function getTableName(): string
    {
        global $wpdb;

        $tableAttributes = (new \ReflectionClass(static::class))->getAttributes(DBTable::class);
        $tableBaseName = $tableAttributes[0]->newInstance()->name;

        return $wpdb->prefix . DB_PREFIX . $tableBaseName;
    }

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
        $data['uuid'] = Uuid::uuid4()->toString();

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

    protected static function getByQuery(string $query): static
    {
        global $wpdb;

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            throw new CollectmeDBException('Failed to get ' . static::class . ": $query");
        }

        return new static(...self::convertDataForModel($result));
    }

    private static function convertDataForModel(array $data): array
    {
        $propertiesMap = self::getInstanceDbPropertiesMap();

        $props = [];
        foreach ($propertiesMap as $propertyMap) {
            $props[$propertyMap['instancePropertyName']] = self::convertFieldValueForModel(
                $propertyMap['instancePropertyName'],
                $data[$propertyMap['dbFieldName']]
            );
        }

        return $props;
    }

    private static function convertFieldValueForModel(string $instancePropertyName, mixed $value): mixed
    {
        $modelGetterName = '_convertModel' . ucfirst($instancePropertyName);
        if (method_exists(self::class, $modelGetterName) || method_exists(static::class, $modelGetterName)) {
            return static::$modelGetterName($value);
        }

        if (self::isDate($instancePropertyName)) {
            return self::convertToDate($value);
        }

        return $value;
    }

    /**
     * @param string|null $date
     * @return ?\DateTime
     */
    private static function convertToDate(?string $date): \DateTime|null
    {
        if (!$date) {
            return null;
        }

        return date_create($date);
    }

    /**
     * @throws CollectmeDBException
     */
    public function delete(): void
    {
        $this->deleted = date_create();

        $this->update();
    }
}