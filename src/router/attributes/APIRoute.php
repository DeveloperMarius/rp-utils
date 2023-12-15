<?php

namespace utils\router\attributes;

use Attribute;
use JetBrains\PhpStorm\ExpectedValues;
use Pecee\Http\Input\Attributes\Route;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class APIRoute extends Route
{

    public function __construct(#[ExpectedValues([Route::GET, Route::POST, Route::PUT, Route::PATCH, Route::DELETE, Route::OPTIONS])] string $method, string $route, ?array $settings = null, private readonly ?string $summary = null){
        parent::__construct($method, $route, $settings);
    }

    public function getRoute(): string{
        return '/api' . parent::getRoute();
    }

    public function getSummary(): ?string{
        return $this->summary;
    }

}