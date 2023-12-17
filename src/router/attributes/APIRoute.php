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

    public function __construct(#[ExpectedValues([Route::GET, Route::POST, Route::PUT, Route::PATCH, Route::DELETE, Route::OPTIONS])] string $method, string $route, ?array $settings = null, private readonly ?string $title = null, private readonly ?string $description = null, #[ExpectedValues([Request::CONTENT_TYPE_JSON, Request::CONTENT_TYPE_FORM_DATA, Request::CONTENT_TYPE_X_FORM_ENCODED, 'text/plain'])] private readonly string $content_type = Request::CONTENT_TYPE_JSON){
        parent::__construct($method, $route, $settings);
    }

    public function getRoute(): string{
        return '/api' . parent::getRoute();
    }

    public function getTitle(): ?string{
        return $this->title;
    }

    public function getDescription(): ?string{
        return $this->description;
    }

    public function getContentType(): string{
        return $this->content_type;
    }

}