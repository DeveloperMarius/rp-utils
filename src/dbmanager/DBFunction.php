<?php

namespace utils\dbmanager;

enum DBFunction{

    case UUID_TO_BIN;
    case BIN_TO_UUID;

    public static function perform(DBFunction $function, string $key): string{
        return match ($function) {
            DBFunction::UUID_TO_BIN => 'UUID_TO_BIN(' . $key . ')',
            DBFunction::BIN_TO_UUID => 'BIN_TO_UUID(' . $key . ')',
            default => $key,
        };
    }

}