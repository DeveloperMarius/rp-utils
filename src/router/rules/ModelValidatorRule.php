<?php

namespace utils\router\rules;

use Somnambulist\Components\Validation\Rule;
use utils\dbmanager\DBManager;

class ModelValidatorRule extends Rule{

    protected string $message = ':attribute existiert nicht';
    protected array $fillableParams = ['model'];

    public function check($value): bool
    {
        $this->assertHasRequiredParameters($this->fillableParams);
        $key = $this->parameter('model');

        if(str_contains($key, '\\')){
            $classname = $key;
        }else{
            $classname = DBManager::getModelNamespaceDefault() . '\\' . $key;
        }
        if(!class_exists($classname))
            return false;
        $reflection = new \ReflectionClass($classname);
        if(!is_array($value))
            $value = [$value];
        foreach($value as $id){
            if(is_object($id) && $id::class === $classname)
                continue;
            if($reflection->isEnum()){
                if($classname::tryFrom($id) === null)
                    return false;
            }else{
                if(!$classname::exists(array('id' => $id)))
                    return false;
            }
        }
        return true;
    }

}