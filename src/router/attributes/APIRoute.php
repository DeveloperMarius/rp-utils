<?php

namespace utils\router\attributes;

use Attribute;
use JetBrains\PhpStorm\ExpectedValues;
use Pecee\Http\Input\Attributes\Route;
use Pecee\Http\Request;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class APIRoute extends Route
{

    public function __construct(#[ExpectedValues([Route::GET, Route::POST, Route::PUT, Route::PATCH, Route::DELETE, Route::OPTIONS])] string $method, string $route, ?array $settings = null, ?string $title = null, ?string $description = null, #[ExpectedValues([Request::CONTENT_TYPE_JSON, Request::CONTENT_TYPE_FORM_DATA, Request::CONTENT_TYPE_X_FORM_ENCODED, 'text/plain'])] string $request_content_type = Request::CONTENT_TYPE_JSON){
        parent::__construct($method, '/api' . $route, $settings, $title, $description, $request_content_type);
    }

}