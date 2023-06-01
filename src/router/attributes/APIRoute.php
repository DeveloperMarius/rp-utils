<?php

namespace utils\router\attributes;

use Attribute;
use Pecee\Http\Input\Attributes\Route;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class APIRoute extends Route
{

    public function getRoute(): string{
        return '/api' . parent::getRoute();
    }

}