<?php

namespace utils\dbmanager;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;

class DBManagerOperator{

    const
        OPERATOR_BIGGER = '>',
        OPERATOR_BIGGER_EQUAL = '>=',
        OPERATOR_EQUAL = '=',
        OPERATOR_EQUAL_NOT = '<>',
        OPERATOR_SMALLER = '<',
        OPERATOR_SMALLER_EQUAL = '<=',
        OPERATOR_IS = 'is',
        OPERATOR_NOT = 'not',
        OPERATOR_IS_NOT = 'is not',
        OPERATOR_LIKE = 'LIKE',
        OPERATOR_IN = 'IN',
        OPERATOR_NOT_IN = 'NOT IN',

        OPERATOR_AND = 'AND',
        OPERATOR_OR = 'OR';

    /**
     * @var string $key
     */
    private string $key;
    /**
     * @var mixed $value
     */
    private mixed $value;
    /**
     * @var string $operator
     */
    #[ExpectedValues([self::OPERATOR_BIGGER, self::OPERATOR_BIGGER_EQUAL, self::OPERATOR_EQUAL, self::OPERATOR_EQUAL_NOT, self::OPERATOR_SMALLER, self::OPERATOR_SMALLER_EQUAL, self::OPERATOR_IS, self::OPERATOR_NOT, self::OPERATOR_IS_NOT, self::OPERATOR_LIKE, self::OPERATOR_IN, self::OPERATOR_NOT_IN])]
    private string $operator;
    /**
     * @var string $nextOperator
     */
    #[ExpectedValues([self::OPERATOR_AND, self::OPERATOR_OR])]
    private string $nextOperator;

    /**
     * DatabaseManagerOperator constructor.
     * @param string $key
     * @param mixed $value
     * @param string $operator
     * @param string $nextOperator
     */
    public function __construct(string $key, mixed $value, #[ExpectedValues([self::OPERATOR_BIGGER, self::OPERATOR_BIGGER_EQUAL, self::OPERATOR_EQUAL, self::OPERATOR_EQUAL_NOT, self::OPERATOR_SMALLER, self::OPERATOR_SMALLER_EQUAL, self::OPERATOR_IS, self::OPERATOR_NOT, self::OPERATOR_IS_NOT, self::OPERATOR_LIKE, self::OPERATOR_IN, self::OPERATOR_NOT_IN])] string $operator, #[ExpectedValues([self::OPERATOR_AND, self::OPERATOR_OR])] string $nextOperator = self::OPERATOR_AND){
        $this->key = $key;
        $this->value = $value;
        $this->operator = $operator;
        $this->nextOperator = $nextOperator;
    }

    /**
     * @return string
     */
    public function getKey(): string{
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed{
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue(mixed $value): void{
        $this->value = $value;
    }

    /**
     * @param bool $auto_detect
     * @return string
     */
    #[Pure]
    public function getOperator(bool $auto_detect = true): string{
        if($auto_detect){
            if($this->getValue() === null){
                if($this->operator === self::OPERATOR_EQUAL)
                    return self::OPERATOR_IS;
                else if($this->operator === self::OPERATOR_EQUAL_NOT || $this->operator === self::OPERATOR_NOT)
                    return self::OPERATOR_IS_NOT;
            }
        }
        return $this->operator;
    }

    /**
     * @return string
     */
    public function getNextOperator(): string{
        return $this->nextOperator;
    }

}