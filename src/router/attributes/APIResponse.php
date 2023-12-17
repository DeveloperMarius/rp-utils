<?php

namespace utils\router\attributes;
use Attribute;
use Closure;

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

    const successResponseGenerator = 'utils\\router\\utils\\RouterUtils::successResponseGenerator';

    public static function successResponseGenerator(mixed $data): array{
        return array(
            'success' => 'bool',
            'message' => 'string',
            'data' => $data,
            'meta' => array()
        );
    }

    const errorResponseGenerator = 'utils\\router\\utils\\RouterUtils::errorResponseGenerator';

    public static function errorResponseGenerator(mixed $data): array{
        return array(
            'success' => 'bool',
            'message' => 'string',
            'info' => 'string',
            'errors' => array(),
            'code' => 'integer',
            'data' => $data
        );
    }

    const successPaginationResponseGenerator = 'utils\\router\\utils\\RouterUtils::successPaginationResponseGenerator';

    public static function successPaginationResponseGenerator(mixed $data): array{
        return array(
            'success' => 'bool',
            'message' => 'string',
            'info' => 'string',
            'errors' => array(),
            'code' => 'integer',
            'data' => $data,
            'meta' => array(
                'current_page' => 'integer',
                'first_page' => 'integer',
                'last_page' => 'integer',
                'total_results' => 'integer',
                'page_size' => 'integer'
            )
        );
    }
}