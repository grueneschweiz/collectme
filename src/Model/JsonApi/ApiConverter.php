<?php

declare(strict_types=1);

namespace Collectme\Model\JsonApi;

use Collectme\Exceptions\CollectmeException;
use Collectme\Model\DateTimeTypeHandler;

trait ApiConverter
{
    use DateTimeTypeHandler;

    /**
     * @throws CollectmeException
     * @throws \ReflectionException
     */
    public static function fromApiModelToPropsArray(array $apiModel): array
    {
        return [
            ...self::convertIdFromApi($apiModel),
            ...self::convertAttributesFromApi($apiModel),
            ...self::convertRelationshipsFromApi($apiModel),
        ];
    }

    /**
     * @throws CollectmeException
     */
    private static function convertIdFromApi(array $apiModel): array
    {
        if (!array_key_exists('id', $apiModel)) {
            return [];
        }

        return [self::getApiModelIdProperty()->name => $apiModel['id']];
    }

    /**
     * @throws CollectmeException
     */
    private static function getApiModelIdProperty(): \ReflectionProperty
    {
        $properties = static::getClassProperties();

        foreach ($properties as $property) {
            $attributes = $property->getAttributes(ApiModelId::class);

            if (!empty($attributes)) {
                return $property;
            }
        }

        throw new CollectmeException('Missing property with attribute ApiModelId in: ' . static::class);
    }

    /**
     * @return \ReflectionProperty[]
     */
    private static function getClassProperties(): array
    {
        return (new \ReflectionClass(static::class))->getProperties();
    }

    private static function convertAttributesFromApi(array $apiModel): array
    {
        if (!array_key_exists('attributes', $apiModel)) {
            return [];
        }

        $apiAttributes = $apiModel['attributes'];
        $propertiesMap = self::getInstanceApiAttributesMap();

        $props = [];
        foreach ($propertiesMap as $propertyMap) {
            ['instancePropertyName' => $propertyName, 'apiFieldName' => $apiFieldName] = $propertyMap;

            if (!array_key_exists($apiFieldName, $apiAttributes)) {
                continue;
            }

            $props[$propertyName] = self::convertFieldFromApi(
                $propertyName,
                $apiAttributes[$apiFieldName]
            );
        }

        return $props;
    }

    /**
     * Get array that maps instance property names to the api attribute names.
     *
     * Properties without the #[ApiModelAttribute] attribute are ignored.
     *
     * @return array{
     *     array{
     *          instancePropertyName: string,
     *          apiFieldName: string
     *    }
     * }
     */
    private static function getInstanceApiAttributesMap(): array
    {
        $instanceProperties = (new \ReflectionClass(static::class))->getProperties();

        $map = [];
        foreach ($instanceProperties as $instanceProperty) {
            $instancePropertyName = $instanceProperty->getName();

            $apiAttributes = $instanceProperty->getAttributes(ApiModelAttribute::class);

            if (empty($apiAttributes)) {
                continue;
            }

            $apiFieldName = $apiAttributes[0]->newInstance()->name;

            if (empty($apiFieldName)) {
                $apiFieldName = $instancePropertyName;
            }

            $map[] = [
                'instancePropertyName' => $instancePropertyName,
                'apiFieldName' => $apiFieldName,
            ];
        }

        return $map;
    }

    private static function convertFieldFromApi(string $instancePropertyName, mixed $value): mixed
    {
        $getterName = '_convertFromApi' . ucfirst($instancePropertyName);
        /** @noinspection DuplicatedCode */
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
     * @throws \ReflectionException
     */
    private static function convertRelationshipsFromApi(array $apiModel): array
    {
        if (!array_key_exists('relationships', $apiModel)) {
            return [];
        }

        $apiRelationships = $apiModel['relationships'];
        $propertiesMap = self::getInstanceApiRelationshipsMap();

        $props = [];
        foreach ($propertiesMap as $propertyMap) {
            ['instancePropertyName' => $propertyName, 'apiFieldName' => $apiFieldName] = $propertyMap;

            if (!array_key_exists($apiFieldName, $apiRelationships)) {
                continue;
            }

            if (array_key_exists('data', $apiRelationships[$apiFieldName])) {

                // to one relationship
                $props[$propertyName] = $apiRelationships[$apiFieldName]['data']['id'];
            } else {

                // to many relationship
                $ids = [];
                foreach ($apiRelationships[$apiFieldName] as $relationship) {
                    $ids[] = $relationship['data']['id'];
                }
                $props[$propertyName] = $ids;
            }

        }

        return $props;
    }

    /**
     * Get array that maps instance property names to the api relationship names.
     *
     * Properties without the #[ApiModelRelationship] attribute are ignored.
     *
     * @return array{
     *     array{
     *          instancePropertyName: string,
     *          apiFieldName: string
     *    }
     * }
     * @throws \ReflectionException
     */
    private static function getInstanceApiRelationshipsMap(): array
    {
        $instanceProperties = (new \ReflectionClass(static::class))->getProperties();

        $map = [];
        foreach ($instanceProperties as $instanceProperty) {
            $instancePropertyName = $instanceProperty->getName();

            $apiAttributes = $instanceProperty->getAttributes(ApiModelRelationship::class);

            if (empty($apiAttributes)) {
                continue;
            }

            $relatedClassFQN = $apiAttributes[0]->newInstance()->classFQN;
            $type = self::getApiModelType($relatedClassFQN);

            $map[] = [
                'instancePropertyName' => $instancePropertyName,
                'apiFieldName' => $type,
            ];
        }

        return $map;
    }

    /**
     * @throws \ReflectionException
     */
    protected static function getApiModelType(string $className = null): string
    {
        $className = $className ?? static::class;
        $attributes = (new \ReflectionClass($className))->getAttributes(ApiModelType::class);
        return $attributes[0]->newInstance()->typeName;
    }

    /**
     * @throws CollectmeException
     * @throws \ReflectionException
     */
    public function toApiModel(): ApiModel
    {
        $model = $this->toApiBaseModel();

        $model->attributes = $this->getApiModelAttributes();
        $model->relationships = $this->getApiModelRelationships();

        return $model;
    }

    /**
     * @throws CollectmeException
     * @throws \ReflectionException
     */
    public function toApiBaseModel(): ApiModel
    {
        return new ApiModel(
            id: $this->getApiModelId(),
            type: self::getApiModelType()
        );
    }

    /**
     * @throws CollectmeException
     */
    protected function getApiModelId(): ?string
    {
        return self::getApiModelIdProperty()->getValue($this);
    }

    protected function getApiModelAttributes(): array
    {
        $properties = self::getClassProperties();

        $attributes = [];
        foreach ($properties as $property) {
            $instanceAttrs = $property->getAttributes(ApiModelAttribute::class);

            if (!empty($instanceAttrs)) {
                $name = $instanceAttrs[0]->newInstance()->name ?? $property->name;
                $value = $this->getConvertedValueForApi($property->name);

                $attributes[$name] = $value;
            }
        }

        return $attributes;
    }

    /**
     * The property value, or the result of its api getter, if one exists.
     *
     * The api getter takes precedence over the property itself. The getter must
     * use the following naming pattern: _convertApi{InstancePropertyName}. So the db
     * getter for a property called 'created' has to be called _convertApiCreated.
     *
     * If a general getter, _convert{InstancePropertyName} exists but no api getter
     * the general getter is used. Where no getter exists, the unconverted value
     * of the property is returned.
     *
     * @param string $instancePropertyName
     * @return mixed
     */
    private function getConvertedValueForApi(string $instancePropertyName): mixed
    {
        $getterName = '_convertToApi' . ucfirst($instancePropertyName);
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

    /**
     * @throws \ReflectionException
     */
    protected function getApiModelRelationships(): array
    {
        $properties = self::getClassProperties();

        $relationships = [];
        foreach ($properties as $property) {
            $instanceAttrs = $property->getAttributes(ApiModelRelationship::class);

            if (!empty($instanceAttrs)) {
                $class = $instanceAttrs[0]->newInstance()->classFQN;
                $type = self::getApiModelType($class);

                $relationships[$type] = $this->getRelationshipData($property, $type);
            }
        }

        return $relationships;
    }

    private function getRelationshipData(\ReflectionProperty $property, string $type): array
    {
        $value = $this->getConvertedValueForApi($property->name);

        if (is_array($value)) {

            // to many relationship
            $data = [];
            foreach ($value as $id) {
                $data[] = [
                    'data' => [
                        'type' => $type,
                        'id' => $id,
                    ]
                ];
            }

        } else {

            // to one relationship
            $data = [
                'data' =>
                    [
                        'type' => $type,
                        'id' => $value,
                    ]
            ];
        }

        return $data;
    }
}