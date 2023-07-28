<?php

namespace utils;

/**
 * Class EnvLoader
 * @package utils
 */
class EnvLoader{

    /**
     * @var string $prefix
     */
    public static string $prefix = 'RP_';
    private static array $loaded_files = array();

    /**
     * @param string $filename
     * @param bool $force
     */
    public static function read(string $filename, bool $force = false){
        if(!$force && in_array($filename, self::$loaded_files))
            return;
        $content = file_get_contents($filename);
        $content_array = preg_split("/(\r\n|\n|\r)/", $content);
        if($content_array !== false){
            $content_array = array_filter($content_array, function ($line){
                return mb_strpos($line, '=', 0, 'UTF-8') !== false && !str_starts_with($line, '#');
            });
            $env_values = array();
            foreach($content_array as $line){
                $split = array_map('trim', explode('=', $line, 2));
                $env_values[$split[0]] = $split[1] ?? '';
            }
            array_map('\utils\EnvLoader::set', array_keys($env_values), array_values($env_values));
        }
        self::$loaded_files[] = $filename;
    }

    /**
     * @param string $filename
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function write(string $filename, string $key, mixed $value){
        $content = file_get_contents($filename);
        $content_array = preg_split("/(\r\n|\n|\r)/", $content);
        if($content_array !== false){
            $written = false;
            foreach($content_array as $i => $line){
                if(mb_strpos($line, '=', 0, 'UTF-8') === false && str_starts_with($line, '#'))
                    continue;
                $split = array_map('trim', explode('=', $line, 2));
                if($split[0] === strtoupper($key)){
                    $content_array[$i] = strtoupper($key) . '=' . $value;
                    $written = true;
                }
            }
            if(!$written)
                $content_array[] = strtoupper($key) . '=' . $value;
        }
        $write = '';
        foreach($content_array as $line){
            $write .= $line . "\n";
        }
        file_put_contents($filename, $write);
    }

    /**
     * @param string $key
     * @param string $value
     */
    public static function set(string $key, string $value){
        $_ENV[$key] = $value;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null){
        $key = strtoupper($key);
        return $_ENV[$key] ?? $default;
    }
}