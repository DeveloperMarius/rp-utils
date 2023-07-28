<?php

namespace utils\exception;

use Exception;

class ModelException extends Exception{

    public function __construct(string $message = "", string $traceAsString = "", int $code = 0, ?Throwable $previous = null){
        parent::__construct($message, $code, $previous);
    }

}