<?php

namespace utils\dbmanager\attributes;

use Attribute;

/**
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class UUIDv4Property
{

    /**
     * @param bool $binary
     */
    public function __construct(private readonly bool $binary = false){}

    /**
     * @return bool
     */
    public function isBinary(): bool{
        return $this->binary;
    }
}