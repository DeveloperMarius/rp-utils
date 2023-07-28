<?php

namespace utils\dbmanager\attributes;

use Attribute;

/**
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ReadonlyProperty
{

    /**
     *
     */
    public function __construct(){}

}