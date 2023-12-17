<?php

namespace utils\dbmanager\attributes;

use Attribute;

/**
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ExampleValue{

    public function __construct(protected readonly mixed $value){}

    public function getValue(): mixed{
        return $this->value;
    }
}