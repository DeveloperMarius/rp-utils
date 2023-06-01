<?php

namespace utils;

use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Pure;

#[Deprecated]
class Validator{

    /**
     * @var mixed $value
     */
    private mixed $value;
    /**
     * @var bool $valid
     */
    private bool $valid = true;
    /**
     * @var array $errors
     */
    private array $errors = array();

    /**
     * Validator constructor.
     * @param mixed $value
     */
    public function __construct(mixed $value){
        $this->value = $value;
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
     * @return bool
     */
    public function valid(): bool{
        return $this->valid;
    }

    /**
     * @return bool
     */
    #[Pure]
    private function _isNotNull(): bool{
        return $this->getValue() !== null;
    }

    /**
     * @return $this
     */
    public function isNotNull(): self{
        return $this->check(function (){
            return $this->_isNotNull();
        });
    }

    /**
     * @return $this
     */
    public function require(): self{
        return $this->check(function (){
            return $this->_isNotNull();
        });
    }

    /**
     * @param string $pattern
     * @return bool
     */
    private function _matches(string $pattern): bool{
        return preg_match($pattern, $this->getValue());
    }

    /**
     * @param string $pattern
     * @return $this
     */
    public function matches(string $pattern){
        return $this->check(function () use ($pattern){
            return $this->_matches($pattern);
        });
    }

    /**
     * @param array $data
     * @return bool
     */
    private function _in(array $data): bool{
        return in_array($this->getValue(), $data);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function in(array $data){
        return $this->check(function () use ($data){
            return $this->_in($data);
        });
    }

    /**
     * @param string $data
     * @return bool
     */
    private function _equals(string $data): bool{
        return $data === $this->getValue();
    }

    /**
     * @param string $data
     * @return $this
     */
    public function equals(string $data){
        return $this->check(function () use ($data){
            return $this->_equals($data);
        });
    }

    /**
     * @param bool $forceType
     * @param bool $parse
     * @return bool
     */
    private function _isBoolean(bool $forceType = true, bool $parse = false): bool{
        if(!$forceType && is_string($this->getValue())){
            if(filter_var($this->getValue(), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null){
                if($parse)
                    $this->value = boolval($this->getValue());
                return true;
            }else
                return false;
        }
        return is_bool($this->getValue());
    }

    /**
     * @param bool $forceType
     * @param bool $parse
     * @return $this
     */
    public function isBoolean(bool $forceType = true, bool $parse = false): self{
        return $this->check(function () use ($forceType, $parse){
            return $this->_isBoolean($forceType, $parse);
        });
    }

    /**
     * @param bool $forceType
     * @param bool $parse
     * @return bool
     */
    private function _isInteger(bool $forceType = true, bool $parse = false): bool{
        if(!is_int($this->getValue())){
            if(!$forceType && is_string($this->getValue())){
                if(is_numeric($this->getValue()) && !$this->_contains('.')){
                    if($parse)
                        $this->value = intval($this->getValue());
                    return true;
                } else
                    return false;
            }
        }else
            return true;
        return false;
    }

    /**
     * @param bool $forceType
     * @param bool $parse
     * @return $this
     */
    public function isInteger(bool $forceType = true, bool $parse = false): self{
        return $this->check(function () use ($forceType, $parse){
            return $this->_isInteger($forceType, $parse);
        });
    }

    /**
     * @param bool $forceType
     * @param bool $parse
     * @return bool
     */
    private function _isFloat(bool $forceType = true, bool $parse = false): bool{
        if(!is_float($this->getValue())){
            if(!$forceType && (is_string($this->getValue()) || is_int($this->getValue()))){
                if(is_numeric($this->getValue())){
                    if($parse)
                        $this->value = floatval($this->getValue());
                    return true;
                } else
                    return false;
            }
        }else
            return true;
        return false;
    }

    /**
     * @param bool $forceType
     * @param bool $parse
     * @return $this
     */
    public function isFloat(bool $forceType = true, bool $parse = false): self{
        return $this->check(function () use ($forceType, $parse){
            return $this->_isFloat($forceType, $parse);
        });
    }

    /**
     * @return bool
     */
    private function _isArray(): bool{
        return is_array($this->value);
    }

    /**
     * @return $this
     */
    public function isArray(): self{
        return $this->check(function (){
            return $this->_isArray();
        });
    }

    /**
     * @return bool
     */
    private function _isAssociativeArray(): bool{
        if (array() === $this->getValue()) return false;
        return array_keys($this->getValue()) !== range(0, count($this->getValue()) - 1);
    }

    /**
     * @return $this
     */
    public function isAssociativeArray(): self{
        return $this->check(function (){
            return $this->_isAssociativeArray();
        });
    }

    /**
     * @return bool
     */
    private function _isSequentialArray(): bool{
        return !$this->_isAssociativeArray();
    }

    /**
     * @return $this
     */
    public function isSequentialArray(): self{
        return $this->check(function (){
            return $this->_isSequentialArray();
        });
    }

    /**
     * @return bool
     */
    private function _isString(): bool{
        return is_string($this->getValue());
    }

    /**
     * @return $this
     */
    public function isString(): self{
        return $this->check(function (){
            return $this->_isString();
        });
    }

    /**
     * @param int $length
     * @return bool
     */
    private function _maxLength(int $length): bool{
        if($this->_isArray()){
            return sizeof($this->getValue()) <= $length;
        }
        if($this->_isString()){
            return strlen($this->getValue()) <= $length;
        }
        if($this->_isInteger(false)){
            return strlen(strval($this->getValue())) <= $length;
        }
        return false;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function maxLength(int $length): self{
        return $this->check(function () use ($length){
            return $this->_maxLength($length);
        });
    }

    /**
     * @param int $length
     * @return bool
     */
    private function _minLength(int $length): bool{
        if($this->_isArray()){
            return sizeof($this->getValue()) >= $length;
        }
        if($this->_isString()){
            return strlen($this->getValue()) >= $length;
        }
        if($this->_isInteger(false)){
            return strlen(strval($this->getValue())) >= $length;
        }
        return false;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function minLength(int $length): self{
        return $this->check(function () use ($length){
            return $this->_minLength($length);
        });
    }

    /**
     * @param int $max
     * @return bool
     */
    private function _max(int $max): bool{
        if($this->_isInteger(false)){
            return intval($this->getValue()) <= $max;
        }
        if($this->_isFloat(false)){
            return floatval($this->getValue()) <= $max;
        }
        return false;
    }

    /**
     * @param int $max
     * @return $this
     */
    public function max(int $max): self{
        return $this->check(function () use ($max){
            return $this->_max($max);
        });
    }

    /**
     * @param int $min
     * @return bool
     */
    private function _min(int $min): bool{
        if($this->_isInteger(false)){
            return intval($this->getValue()) >= $min;
        }
        if($this->_isFloat(false)){
            return floatval($this->getValue()) >= $min;
        }
        return false;
    }

    /**
     * @param int $min
     * @return $this
     */
    public function min(int $min): self{
        return $this->check(function () use ($min){
            return $this->_min($min);
        });
    }

    /**
     * @return bool
     */
    private function _isEmpty(): bool{
        return empty($this->getValue());
    }

    /**
     * @return $this
     */
    public function isEmpty(): self{
        return $this->check(function (){
            return $this->_isEmail();
        });
    }

    /**
     * @return bool
     */
    private function _isEmail(): bool{
        return filter_var($this->getValue(),FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @return $this
     */
    public function isEmail(): self{
        return $this->check(function (){
            return $this->_isEmail();
        });
    }

    /**
     * @param string $pattern
     * @return $this
     */
    public function isPhone(string $pattern = '/^(1[567]\d)[ ]?(\d\d\d\d\d\d\d\d?)$/'): self{
        return $this->check(function () use ($pattern){
            return $this->_isPhone($pattern);
        });
    }

    /**
     * @param string $pattern
     * @return bool
     */
    private function _isPhone(string $pattern): bool{
        return preg_match($pattern, $this->getValue());
    }

    /**
     * @param mixed $needle
     * @return bool
     *
     * check for string, integer and array
     */
    private function _startsWith($needle): bool{
        if($this->_isString() || $this->_isInteger()){
            $length = strlen(strval($needle));
            return substr(strval($this->getValue()), 0, $length) === strval($needle);
        }
        if($this->_isSequentialArray()){
            return $this->getValue()[0] === $needle;
        }
        if($this->_isAssociativeArray()){
            return array_key_first($this->getValue()) === $needle;
        }
        return false;
    }

    /**
     * @param mixed $needle
     * @return $this
     */
    public function startsWith($needle): self{
        return $this->check(function () use ($needle){
            return $this->_startsWith($needle);
        });
    }

    /**
     * @param mixed $needle
     * @return bool
     *
     * check for string, integer and array
     */
    private function _endsWith($needle): bool{
        if($this->_isString() || $this->_isInteger()){
            $length = strlen(strval($needle));
            if(!$length) {
                return true;
            }
            return substr(strval($this->getValue()), -$length) === strval($needle);
        }
        if($this->_isSequentialArray()){
            return $this->getValue()[sizeof($this->getValue())-1] === $needle;
        }
        if($this->_isAssociativeArray()){
            return array_key_last($this->getValue()) === $needle;
        }
        return false;
    }

    /**
     * @param mixed $needle
     * @return $this
     */
    public function endsWith($needle): self{
        return $this->check(function () use ($needle){
            return $this->_endsWith($needle);
        });
    }

    /**
     * @param $needle
     * @return bool
     */
    private function _contains($needle): bool{
        if($this->_isString() || $this->_isInteger()){
            return str_contains(strval($this->getValue()), strval($needle));
        }
        if($this->_isSequentialArray()){
            return in_array($needle, $this->getValue());
        }
        if($this->_isAssociativeArray()){
            return array_search($needle, $this->getValue());
        }
        return false;
    }

    /**
     * @param $needle
     * @return $this
     */
    public function contains($needle): self{
        return $this->check(function () use ($needle){
             return $this->_contains($needle);
        });
    }

    /**
     * @param $key
     * @return bool
     */
    private function _arrayKeyExists($key): bool{
        return array_key_exists($key, $this->getValue());
    }

    /**
     * @param $key
     * @return $this
     */
    public function arrayKeyExists($key): self{
        return $this->check(function () use ($key){
            return $this->_arrayKeyExists($key);
        });
    }

    /**
     * @param callable $function
     * @return $this
     */
    private function check(callable $function): self{
        if($this->valid())
            $this->valid = $function();
        return $this;
    }

}