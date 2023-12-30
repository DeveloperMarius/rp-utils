<?php

namespace utils\dbmanager;

use ReflectionEnum;

class EnumDatabaseModel implements \JsonSerializable{

    public static function exists(string $value_or_name): bool{
        $class = new ReflectionEnum(static::class);
        if($class->isBacked()){
            if(sizeof(array_filter(static::cases(), fn($case) => $case->value === $value_or_name)) > 0)
                return true;
        }
        return sizeof(array_filter(static::cases(), fn($case) => $case->name === $value_or_name)) > 0;
    }

    public function jsonSerialize(): mixed{
        $class = new ReflectionEnum(static::class);
        if($class->isBacked()){
            return $this->value;
        }else{
            return $this->name;
        }
    }
}
