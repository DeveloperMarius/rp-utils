<?php

namespace utils;

use utils\exception\ModelException;

class DataClass implements \JsonSerializable{

    private function getValueByPath(array $data, array $path): mixed{
        $value = $data;
        foreach($path as $key){
            if(!isset($value[$key])){
                //Cannot find path -> return null
                return null;
            }
            $value = $value[$key];
        }
        return $value;
    }

    /**
     * Unset given key from array.
     * @link https://gist.github.com/nmfzone/7931deb2153da292aca0eab0ed2f789b
     */
    private function unsetArrayValue(array &$array, string|array $parents, $glue = '->'): void{
        if (!is_array($parents)) {
            $parents = array_filter(explode($glue, $parents), 'strlen');
        }

        $key = array_shift($parents);

        if (empty($parents)) {
            unset($array[$key]);
        } elseif (array_key_exists($key, $array)) {
            if (!is_array($array[$key])) {
                $this->unsetArrayValue($array[$key], $parents);
                //Cleanup empty arrays when all keys were unset
                if(empty($array[$key])){
                    unset($array[$key]);
                }
            } elseif (array_key_exists(0, $array[$key])) {
                foreach ($array[$key] as &$arr) {
                    $arrr = isset($arr[$key]) && is_array($arr[$key]) ? $arr[$key] : $arr;
                    $this->unsetArrayValue($arrr, $parents);
                    $arr = $arrr;
                }
            } else {
                $this->unsetArrayValue($array[$key], $parents);
                //Cleanup empty arrays when all keys were unset
                if(empty($array[$key])){
                    unset($array[$key]);
                }
            }
        }
    }

    /*
     * Cases: [
     *  'payment_type->card_number', //Set payment_type->card_number to card_number
     *  ['payment_type->card_number' => 'payment_type'] //Set payment_type->card_number to payment_type,
     *  ['paymentType' => 'payment_type] //Set paymentType to payment_type
     * ]
     *
     */
    public function setProperties(array $data, array $types = array(), array $mappings = array()): void{
        $new_data = $data;
        foreach($mappings as $original_key_path => $new_key){
            $property_path = explode('->', $original_key_path);

            $original_key = $property_path[sizeof($property_path) - 1];

            //If the original key is the same as the new key, we can skip the mapping
            if(sizeof($property_path) === 0 && $original_key === $new_key)
                continue;

            $value = $this->getValueByPath($new_data, $property_path);
            //When value cannot be found, no mapping needs to happen
            if($value === null)
                continue;
            $this->unsetArrayValue($new_data, $property_path);

            $new_data[$new_key] = $value;
        }
        foreach($new_data as $key => $value){
            if(isset($types[$key]) && $value !== null){
                $type = $types[$key];

                if(is_array($value)) {
                    if (Util::isAssocArray($value)) {
                        if(sizeof($value) === 0)
                            continue;
                        $this->$key = $this->valueToObject($type, $value);
                    } else {
                        $entries = array();
                        foreach ($value as $valueEntry) {
                            if(is_array($valueEntry) && sizeof($valueEntry) === 0)
                                continue;
                            $entries[] = $this->valueToObject($type, $valueEntry);
                        }
                        $this->$key = $entries;
                    }
                }else{
                    $this->$key = $this->valueToObject($type, $value);
                }
                continue;
            }
            $this->$key = $value;
        }
    }

    private function valueToObject(string $className, mixed $value): mixed{
        if(is_string($value) || is_numeric($value)) {
            $class = new \ReflectionClass($className);
            if ($class->isEnum()) {
                $enum = new \ReflectionEnum($className);
                if($enum->isBacked()){
                    foreach ($enum->getCases() as $case) {
                        if($case->getBackingValue() === $value){
                            return $case->getValue();
                        }
                    }
                    throw new \Exception($className . ' not found for value: ' . $value);
                }else {
                    foreach ($enum->getCases() as $case) {
                        if ($case->name === $value) {
                            return $case->getValue();
                        }
                    }
                    throw new \Exception($className . ' not found for name: ' . $value);
                }
            }
        }
        if(is_array($value)){
            return new $className($value);
        }
        return $value;
    }

    protected static ?string $_objectClass = null;

    /**
     * @throws ModelException
     */
    protected static function getObjectClass(): string{
        if(!isset(static::$_objectClass)){
            $called_class = get_called_class();
            if($called_class === '')
                throw new ModelException('get_called_class() is null');
            return $called_class;
        }
        return static::$_objectClass;
    }

    /**
     * @throws ModelException
     */
    protected static function getModelName(): string{
        $path = explode('\\', self::getObjectClass());
        return end($path);
    }

    /**
     * @throws ModelException
     */
    public function toArray(bool $cleanData = false): array{
        $vars = get_object_vars($this);
        if(!$cleanData && str_starts_with(self::getModelName(), 'gastrovia\\panel\\models')){
            $vars['__type__'] = self::getModelName();
            if(isset($vars['password']))
                $vars['password'] = '<**Protected Property**>';
        }
        foreach($vars as $key => $value){
            if($value instanceof DataClass)
                $vars[$key] = $value->toArray();
        }
        return $vars;
    }

    /**
     * @throws ModelException
     */
    public function __toString(): string{
        return self::class . ': ' . json_encode($this->toArray());
    }

    /**
     * @throws ModelException
     */
    public function jsonSerialize(): array{
        return static::toArray();
    }

    public function __call(string $name, array $arguments): mixed{
        if(str_starts_with($name, 'get')){
            $val_name = Util::replaceFromCamelCase(lcfirst(str_replace('get', '', $name)));
            return $this->$val_name;
        }else if(str_starts_with($name, 'is')){
            $name = Util::replaceFromCamelCase(lcfirst(str_replace('is', '', $name)));
            $name2 = 'is_' . $name;
            return isset($this->$name) ? $this->$name === 1 || $this->$name === true : (isset($this->$name2) ? $this->$name2 === 1 || $this->$name2 === true : null);
        }else if(str_starts_with($name, 'has')){
            $name = Util::replaceFromCamelCase(lcfirst(str_replace('has', '', $name)));
            return $this->$name !== null;
        }
        return null;
    }
}