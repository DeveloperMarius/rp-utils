<?php

namespace utils\router\attributes;

use Attribute;

/**
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RoutePermission
{

    /**
     * @param string $permission
     */
    public function __construct(private string $permission){}

    /**
     * @return string
     */
    public function getPermission(): string{
        return $this->permission;
    }

}