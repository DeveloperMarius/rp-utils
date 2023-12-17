<?php

namespace utils\router\attributes;
use Attribute;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class APIResponse {

    public function __construct(private readonly int $code, private readonly ?array $schema = null, private readonly ?string $description = null, private readonly string $content_type = 'application/json', private readonly ?string $generator = null){}

    public function getCode(): int{
        return $this->code;
    }

    /**
     * @return array|null
     */
    public function getSchema(): ?array{
        return $this->schema;
    }

    public function getDescription(): ?string{
        return $this->description;
    }

    public function getContentType(): string{
        return $this->content_type;
    }

    public function generateSchema(): array{
        $generator = $this->generator;
        if($generator === null)
            return $this->getSchema();
        return $generator($this->getSchema());
    }
}