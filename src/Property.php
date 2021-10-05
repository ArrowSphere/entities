<?php

declare(strict_types=1);

namespace ArrowSphere\Entities;

/**
 * @Annotation
 */
final class Property
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type = 'string';

    /**
     * @var bool
     */
    public $isArray = false;

    /**
     * @var bool
     */
    public $required = false;

    /**
     * @var bool
     */
    public $nullable = false;
}
