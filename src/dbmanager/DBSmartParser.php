<?php

namespace utils\dbmanager;

use utils\Time;
use utils\UnixTimestamp;

class DBSmartParser{

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __construct(private string $key, private mixed $value){}

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
     * @return DBSmartParser
     */
    private function setValue(mixed $value): self{
        $this->value = $value;
        return $this;
    }

    /**
     * @return DBSmartParser
     */
    public function parse(): self{
        if(is_string($this->getValue())){
            $this->setValue(trim($this->getValue()));
        }else if(is_bool($this->getValue())){
            $this->setValue($this->getValue() ? 1 : 0);
        }else if($this->getValue() instanceof \BackedEnum){
            $this->setValue($this->getValue()->value);
        }else if($this->getValue() instanceof \UnitEnum){
            $this->setValue($this->getValue()->name);
        }else if($this->getValue() instanceof DatabaseModel && $this->getValue()::hasId()){
            $this->setValue($this->getValue()->getId());
        }else if($this->getValue() instanceof UnixTimestamp){
            $this->setValue($this->getValue()->getMilliseconds(true));
        }else if($this->getValue() instanceof Time){
            $this->setValue($this->getValue()->format('Y-m-d H:i:s'));
        }
        switch($this->getKey()){
            case 'email':
                $this->setValue(strtolower($this->getValue()));
                break;
            case 'phone':
                $this->setValue(preg_replace('/[^0-9+]/', '', $this->getValue()));
                break;
        }
        return $this;
    }
}