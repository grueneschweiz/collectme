<?php

declare(strict_types=1);

namespace Collectme\Model\JsonApi;

use Collectme\Exceptions\CollectmeException;
use Collectme\Model\DateTimeTypeHandler;
use JetBrains\PhpStorm\ArrayShape;

trait ApiConverter
{
    use DateTimeTypeHandler;

    /**
     * @throws CollectmeException
     * @throws \ReflectionException
     */
    #[ArrayShape([
        'id' => 'string',
        'type' => 'string',
        'attributes' => 'array',
        'relationships' => 'array',
    ])]
    public function toApiModel(): array
    {
        $model = $this->toApiBaseModel();

        $attributes = $this->getApiModelAttributes();
        $relationships = $this->getApiModelRelationships();

        if ($attributes) {
            $model['attributes'] = $attributes;
        }

        if ($relationships) {
            $model['relationships'] = $relationships;
        }

        return $model;
    }

    /**
     * @throws CollectmeException
     * @throws \ReflectionException
     */
    #[ArrayShape([
            'id' => 'string',
            'type' => 'string'
        ]
    )]
    public function toApiBaseModel(): array
    {
        return [
            'id' => $this->getApiModelId(),
            'type' => $this->getApiModelType()
        ];
    }

    /**
     * @throws CollectmeException
     */
    protected function getApiModelId(): ?string
    {
        return self::getApiModelIdProperty()->getValue($this);
    }

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

    /**
     * @throws \ReflectionException
     */
    protected function getApiModelType(string $className = null): string
    {
        $className = $className ?? static::class;
        $attributes = (new \ReflectionClass($className))->getAttributes(ApiModelType::class);
        return $attributes[0]->newInstance()->typeName;
    }

    protected function getApiModelAttributes(): array
    {
        $properties = self::getClassProperties();

        $attributes = [];
        foreach ($properties as $property) {
            $instanceAttrs = $property->getAttributes(ApiModelAttribute::class);

            if (!empty($instanceAttrs)) {
                $name = $instanceAttrs[0]->newInstance()->attributeName ?? $property->name;
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
                $class = $instanceAttrs[0]->newInstance()->className;
                $id = $property->getValue($this);

                $type = $this->getApiModelType($class);

                $relationships[$type] = [
                    'data' => [
                        'type' => $type,
                        'id' => $id
                    ]
                ];
            }
        }

        return $relationships;
    }

    /**
     * @throws CollectmeException
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

    private static function convertAttributesFromApi(array $apiModel): array
    {
        if (!array_key_exists('attributes', $apiModel)) {
            return [];
        }

        $apiAttributes = $apiModel['attributes'];
        $propertiesMap = self::getInstanceApiPropertiesMap(ApiModelAttribute::class);

        $props = [];
        foreach ($propertiesMap as $propertyMap) {
            ['instancePropertyName' => $propertyName, 'apiFieldName' => $apiFieldName ] = $propertyMap;

            if (! array_key_exists($apiFieldName, $apiAttributes)) {
                continue;
            }

            $props[$propertyName] = self::convertFieldFromApi(
                $propertyName,
                $apiAttributes[$apiFieldName]
            );
        }

        return $props;
    }

    private static function convertRelationshipsFromApi(array $apiModel): array
    {
        if (!array_key_exists('relationships', $apiModel)) {
            return [];
        }

        $apiRelationships = $apiModel['relationships'];
        $propertiesMap = self::getInstanceApiPropertiesMap(ApiModelRelationship::class);

        $props = [];
        foreach ($propertiesMap as $propertyMap) {
            ['instancePropertyName' => $propertyName, 'apiFieldName' => $apiFieldName ] = $propertyMap;

            if (! array_key_exists($apiFieldName, $apiRelationships)) {
                continue;
            }

            $props[$propertyName] = $apiRelationships[$apiFieldName]['data']['id'];
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
    private static function getInstanceApiPropertiesMap(string $attributeClass): array
    {
        $instanceProperties = (new \ReflectionClass(static::class))->getProperties();

        $map = [];
        foreach ($instanceProperties as $instanceProperty) {
            $instancePropertyName = $instanceProperty->getName();

            $apiAttributes = $instanceProperty->getAttributes($attributeClass);

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

    /** @noinspection DuplicatedCode */

    private static function convertFieldFromApi(string $instancePropertyName, mixed $value): mixed
    {
        $getterName = '_convertFromApi' . ucfirst($instancePropertyName);
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
}