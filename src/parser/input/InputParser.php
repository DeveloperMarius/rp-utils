<?php

namespace utils\parser\input;

use JetBrains\PhpStorm\Deprecated;
use utils\Validator;

#[Deprecated]
class InputParser{

    private ?Validator $validator = null;

    public function __construct(private mixed $value){}

    /**
     * @return mixed
     */
    public function getValue(): mixed{
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    private function setValue(mixed $value): self{
        $this->value = $value;
        return $this;
    }

    /**
     * @return Validator
     */
    public function validate(): Validator{
        if($this->validator === null)
            $this->validator = new Validator($this->getValue());
        $this->validator->setValue($this->getValue());
        return $this->validator;
    }

    /**
     * @param array $allowed_tags
     * @return $this
     */
    public function stripTags(array $allowed_tags = array()): self{
        return $this->setValue(strip_tags($this->getValue(), $allowed_tags));
    }

    /**
     * @return $this
     */
    public function stripTags2(): self{
        return $this->setValue(filter_var($this->getValue(), FILTER_SANITIZE_STRING));
    }

    /**
     * @return $this
     */
    public function htmlSpecialChars(): self{
        return $this->setValue(htmlspecialchars($this->getValue(), ENT_QUOTES | ENT_HTML5));
    }

    /**
     * @return $this
     */
    public function sanitize(): self{
        return $this->htmlSpecialChars();
    }

    /**
     * @return $this
     */
    public function sanitizeEmailAddress(): self{
        return $this->setValue(filter_var($this->getValue(), FILTER_SANITIZE_EMAIL));
    }

    /**
     * @return bool|null
     */
    public function toBoolean(): ?bool{
        if(filter_var($this->getValue(), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null){
            $this->setValue(boolval($this->getValue()));
            return $this->getValue();
        }
        return null;
    }

    /**
     * @return string
     */
    public function toString(): string{
        $this->setValue(strval($this->getValue()));
        return $this->getValue();
    }

    /**
     * @return int|null
     */
    public function toInteger(): ?int{
        if(is_string($this->getValue()) && is_numeric($this->getValue()) && !str_contains($this->getValue(), '.')){
            $this->setValue(intval($this->getValue()));
            return $this->getValue();
        }
        return null;
    }

}