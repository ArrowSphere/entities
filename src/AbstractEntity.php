<?php

declare(strict_types=1);

namespace ArrowSphere\Entities;

use ArrowSphere\Entities\Exception\EntitiesException;
use DateTimeInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Exception;
use JsonSerializable;
use ReflectionClass;

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

        $errors = [];

        foreach ($properties as $property) {
            $annotation = $annotationReader->getPropertyAnnotation($property, Property::class);

            if ($annotation instanceof Property) {
                $name = $annotation->name ?? $property->getName();
                if (method_exists($property, 'getType')) {
                    $type = $annotation->type ?? $property->getType() ?? 'string';
                } else {
                    $type = $annotation->type ?? 'string';
                }

                $required = $annotation->required;
                $nullable = $annotation->nullable;
                $isArray = $annotation->isArray;

                $property->setAccessible(true);

                if (! array_key_exists($name, $data)) {
                    if ($required) {
                        $errors[] = sprintf('Missing field: %s', $name);
                    }

                    continue;
                }

                if (in_array($type, self::ALLOWED_TYPES, true)) {
                    $getValue = static function ($value) {
                        return $value;
                    };
                } elseif (in_array($type, self::SCALAR_TYPES, true)) {
                    $getValue = static function ($value) use ($type, $name, $nullable) {
                        if (! is_scalar($value) && ! ($value === null && $nullable)) {
                            throw new EntitiesException(sprintf(
                                'Invalid value for scalar field %s: type %s instead of %s',
                                $name,
                                gettype($value),
                                $type
                            ));
                        }

                        return $value;
                    };
                } elseif (class_exists($type)) {
                    $getValue = static function ($value) use ($type) {
                        if (! is_array($value) && is_subclass_of($type, self::class)) {
                            throw new EntitiesException(
                                sprintf(
                                    'Invalid value for type %s while building entity of type %s',
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
                                    'Unable to build element of type %s while building entity of type %s: %s',
                                    $type,
                                    static::class,
                                    $e->getMessage()
                                ),
                                (int)$e->getCode(),
                                $e
                            );
                        }
                    };
                } else {
                    $errors[] = sprintf('Missing class: %s', $type);

                    continue;
                }

                if ($isArray) {
                    $property->setValue($this, array_map(static function ($value) use ($getValue) {
                        return $getValue($value);
                    }, $data[$name]));
                } else {
                    $property->setValue($this, $getValue($data[$name]));
                }
            }
        }

        if (! empty($errors)) {
            throw new EntitiesException(implode(', ', $errors));
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
     * @return mixed
     */
    public function __call($method, $params)
    {
        $property = lcfirst(substr($method, 3));
        $prefix = substr($method, 0, 3);

        if (! in_array($prefix, ['get', 'set'])) {
            return null;
        }

        if ($prefix === 'get') {
            return $this->$property;
        }

        if ($prefix === 'set') {
            $this->$property = $params[0];

            return $this;
        }

        return null;
    }
}
