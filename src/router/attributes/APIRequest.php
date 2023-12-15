<?php

namespace utils\router\attributes;
use Attribute;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class APIRequest {

    public function __construct(private readonly array $parameters = array(), private array $body = array(), private readonly ?string $summary = null, private readonly string $content_type = 'application/json'){}

}