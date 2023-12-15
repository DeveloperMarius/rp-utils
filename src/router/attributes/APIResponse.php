<?php

namespace utils\router\attributes;
use Attribute;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class APIResponse {

    public function __construct(private readonly int $code, private readonly array $content = array(), private readonly ?string $description = null, private readonly string $content_type = 'application/json'){}

    public function getCode(): int{
        return $this->code;
    }

    public function getContent(): array{
        return $this->content;
    }

    public function getDescription(): ?string{
        return $this->description;
    }

    public function getContentType(): string{
        return $this->content_type;
    }

}