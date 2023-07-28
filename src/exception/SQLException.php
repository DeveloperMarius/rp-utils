<?php

namespace utils\exception;

use Exception;
use JetBrains\PhpStorm\Pure;

class SQLException extends Exception{

    /**
     * @var string|null $query
     */
    private ?string $query;
    /**
     * @var array|null $info
     */
    private ?array $info;

    /**
     * AuthException constructor.
     * @param string $message
     * @param string|null $query
     * @param int $code
     * @param array|null $info
     * @param Exception|null $previous
     */
    #[Pure]
    public function __construct(string $message, ?string $query = null, int $code = 0, ?array $info = null, Exception $previous = null){
        $this->query = $query;
        $this->info = $info;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string|null
     */
    public function getQuery(): ?string{
        return $this->query;
    }

    /**
     * @return array|null
     */
    public function getInfo(): ?array{
        return $this->info;
    }
}