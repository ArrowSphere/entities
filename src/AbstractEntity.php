<?php

declare(strict_types=1);

namespace ArrowSphere\Entities;

use ArrowSphere\Entities\Exception\EntitiesException;
use ArrowSphere\Entities\Exception\InvalidEntityException;
use ArrowSphere\Entities\Exception\MissingFieldException;
use ArrowSphere\Entities\Exception\NonExistingFieldException;
use DateTimeInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Exception;
use JsonSerializable;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Class AbstractEntity
 */
abstract class AbstractEntity implements JsonSerializable
{
    private const ALLOWED_TYPES = [
        'array',
        'object',
    ];

    private const SCALAR_TYPES = [
        'int',
        'float',
        'bool',
        'string',
    ];

    /**
     * AbstractEntity constructor.
     *
     * @param array $data
     *
     * @throws EntitiesException
     */
    public function __construct(array $data = [])
    {
        $annotationReader = new AnnotationReader();

        $reflectionClass = new ReflectionClass(static::class);
        $properties = $reflectionClass->getProperties();

        $missingFields = [];
        $missingClasses = [];

        foreach ($properties as $property) {
            $annotation = $annotationReader->getPropertyAnnotation($property, Property::class);

            if (! $annotation instanceof Property) {
                continue;
            }

            $name = $annotation->name ?? $property->getName();
            if (method_exists($property, 'getType')) {
                $typedProperty = $property->getType() instanceof ReflectionNamedType ? $property->getType()->getName() : null;
                $type = $annotation->type ?? $typedProperty ?? 'string';
            } else {
                $type = $annotation->type ?? 'string';
            }

            $required = $annotation->required;
            $nullable = $annotation->nullable;
            $isArray = $annotation->isArray;

            $property->setAccessible(true);

            if (! array_key_exists($name, $data)) {
                if ($required) {
                    $missingFields[] = $name;
                }

                continue;
            }

            if (in_array($type, self::ALLOWED_TYPES, true)) {
                $getValue = [__CLASS__, 'getValueForAllowedType'];
            } elseif (in_array($type, self::SCALAR_TYPES, true)) {
                $getValue = [__CLASS__, 'getValueForScalarType'];
            } elseif (class_exists($type)) {
                $getValue = [__CLASS__, 'getValueForClassType'];
            } else {
                $missingClasses[] = sprintf(
                    'Missing class %s for field %s',
                    $type,
                    $property->getName()
                );

                continue;
            }

            if (! is_callable($getValue)) {
                throw new InvalidEntityException(
                    sprintf('%s is not a callable', print_r($getValue, true))
                );
            }

            if ($isArray) {
                $property->setValue($this, array_map(static function ($value) use ($getValue, $type, $name, $nullable) {
                    return $getValue($value, $type, $name, $nullable);
                }, $data[$name]));
            } else {
                $property->setValue($this, $getValue($data[$name], $type, $name, $nullable));
            }

            unset($data[$name]);
        }

        if (! empty($missingClasses)) {
            throw new InvalidEntityException(
                sprintf(
                    'Some classes are missing while building entity of type %s: %s',
                    static::class,
                    implode(', ', $missingClasses)
                )
            );
        }

        if (! empty($data)) {
            throw new NonExistingFieldException(
                sprintf(
                    'Non existing fields while building entity of type %s: %s',
                    static::class,
                    implode(', ', array_keys($data))
                )
            );
        }

        if (! empty($missingFields)) {
            throw new MissingFieldException(
                sprintf(
                    'Missing fields while building entity of type %s: %s',
                    static::class,
                    implode(', ', $missingFields)
                )
            );
        }
    }

    /**
     * @param mixed $value
     * @param string $type
     * @param string $name
     * @param bool $nullable
     *
     * @return mixed
     */
    private static function getValueForAllowedType($value, string $type, string $name, bool $nullable)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @param string $type
     * @param string $name
     * @param bool $nullable
     *
     * @return bool|float|int|string|null
     *
     * @throws EntitiesException
     */
    private static function getValueForScalarType($value, string $type, string $name, bool $nullable)
    {
        if ($value === null && $nullable) {
            return null;
        }

        if (! is_scalar($value)) {
            throw new EntitiesException(sprintf(
                'Invalid value for scalar field %s: type %s instead of %s while building entity of type %s',
                $name,
                gettype($value),
                $type,
                static::class
            ));
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param string $type
     * @param string $name
     * @param bool $nullable
     *
     * @return mixed|null
     *
     * @throws EntitiesException
     */
    private static function getValueForClassType($value, string $type, string $name, bool $nullable)
    {
        if ($value === null && $nullable) {
            return null;
        }

        if (! is_array($value) && is_subclass_of($type, self::class)) {
            throw new EntitiesException(
                sprintf(
                    'Invalid value for field %s of type %s while building entity of type %s',
                    $name,
                    $type,
                    static::class
                )
            );
        }

        try {
            return new $type($value);
        } catch (Exception $e) {
            throw new EntitiesException(
                sprintf(
                    'Unable to build field %s of type %s while building entity of type %s: %s',
                    $name,
                    $type,
                    static::class,
                    $e->getMessage()
                ),
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Indicates which fields are used when using json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $fields = [];

        $annotationReader = new AnnotationReader();

        $reflectionClass = new ReflectionClass(static::class);
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $property) {
            $annotation = $annotationReader->getPropertyAnnotation($property, Property::class);

            if ($annotation instanceof Property) {
                $name = $annotation->name ?? $property->getName();
                $required = $annotation->required;

                $property->setAccessible(true);

                if ($required !== false || $property->getValue($this) !== null) {
                    $fields[$name] = $property->getValue($this);
                    if ($fields[$name] instanceof JsonSerializable) {
                        $fields[$name] = $fields[$name]->jsonSerialize();
                    } elseif ($fields[$name] instanceof DateTimeInterface) {
                        $fields[$name] = $fields[$name]->format(DateTimeInterface::ATOM);
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Magic getters and setters.
     *
     * @param string $method
     * @param array $params
     *
     * @return mixed|null
     */
    public function __call(string $method, array $params)
    {
        $property = lcfirst(substr($method, 3));
        $prefix = substr($method, 0, 3);

        if ($prefix === 'get') {
            return $this->getProperty($property);
        }

        if ($prefix === 'set') {
            return $this->setProperty($property, $params[0]);
        }

        return null;
    }

    /**
     * Sets the value of a property.
     *
     * @param string $property
     * @param mixed $value
     *
     * @return static
     */
    public function setProperty(string $property, $value): self
    {
        $this->$property = $value;

        return $this;
    }

    /**
     * Returns the value of a property.
     *
     * @param string $property
     *
     * @return mixed
     */
    public function getProperty(string $property)
    {
        return $this->$property;
    }
}
