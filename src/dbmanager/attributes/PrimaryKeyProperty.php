<?php

namespace utils\dbmanager\attributes;

use Attribute;

/**
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class PrimaryKeyProperty
{

    /**
     * @param bool $auto_increment
     */
    public function __construct(private readonly bool $auto_increment = true){}

    /**
     * @return bool
     */
    public function isAutoIncrement(): bool{
        return $this->auto_increment;
    }
}