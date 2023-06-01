<?php

namespace utils;

abstract class ConfigClass{

    private static string $default_config = '/res/config.env';
    private static bool $env_loaded = false;

    /**
     * @return void
     */
    public static function load(){
        EnvLoader::read(__DIR__ . '/../../../..' . self::$default_config);
        self::$env_loaded = true;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function getData(string $key, mixed $default = null){
        if(!self::$env_loaded){
            self::load();
        }
        return EnvLoader::get($key, $default);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments): mixed{
        if(str_starts_with($name, 'get')){
            $val_name = Util::replaceFromCamelCase(lcfirst(str_replace('get', '', $name)));
            return self::getData(strtoupper($val_name), $arguments[0] ?? null);
        }else if(str_starts_with($name, 'is')){
            $val_name = Util::replaceFromCamelCase(lcfirst(str_replace('is', '', $name)));
            $value = self::getData(strtoupper($val_name), $arguments[0] ?? null);
            return $value !== null ? $value === "1" || $value === "true" : null;
        }
        return null;
    }

}