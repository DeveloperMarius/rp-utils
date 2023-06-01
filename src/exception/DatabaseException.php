<?php

namespace utils\exception;

use Exception;
use JetBrains\PhpStorm\Pure;

class DatabaseException extends Exception{

    /**
     * @var string|null $method
     */
    private ?string $method;

    /**
     * DatabaseException constructor.
     * @param string|null $method
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    #[Pure]
    public function __construct(?string $method = null, string $message = '', int $code = 0, Exception $previous = null){
        $this->method = $method;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string|null
     */
    #[Pure]
    public function getMethod(): ?string{
        return $this->method;
    }

    /**
     * @return bool
     */
    #[Pure]
    public function hasMethod(): bool{
        return $this->getMethod() !== null;
    }
}