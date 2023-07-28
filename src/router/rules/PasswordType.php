<?php

namespace utils\router\rules;

enum PasswordType: string{

    case SECURITY_LOW = 'low';
    case SECURITY_NORMAL = 'normal';
    case SECURITY_HIGH = 'height';
    case PLESK = 'plesk';

    public function generate(): string{
        return match($this){
            self::SECURITY_LOW => (new \utils\PasswordGenerator(6))
                ->setUseNumbers(true)
                ->setUseUpperCaseLetters(true)
                ->setStartWithLetter(true)
                ->generate(),
            self::SECURITY_NORMAL => (new \utils\PasswordGenerator(6))
                ->setUseNumbers(true)
                ->setUseSpecialCharacters(true)
                ->setUseUpperCaseLetters(true)
                ->setStartWithLetter(true)
                ->generate(),
            self::SECURITY_HIGH => (new \utils\PasswordGenerator(15))
                ->setUseNumbers(true)
                ->setUseSpecialCharacters(true)
                ->setUseUpperCaseLetters(true)
                ->setStartWithLetter(true)
                ->generate(),
            self::PLESK => (new \utils\PasswordGenerator(12))
                ->setUseNumbers(true)
                ->setUseSpecialCharacters(true)
                ->setUseUpperCaseLetters(true)
                ->setStartWithLetter(true)
                ->setSpecialCharactersData(["!", "@", "#", "$", "%", "^", "&", "*", "?", "_", "~"])
                ->generate(),
        };
    }

    public function validate(string $password): bool{
        return match ($this){
            self::SECURITY_LOW => strlen($password) >= 6 && preg_match_all('/[A-Z]/', $password) >= 1 && preg_match_all('/[a-z]/', $password) >= 1 && preg_match_all('/[0-9]/', $password) >= 1,
            self::SECURITY_NORMAL => strlen($password) >= 6 && preg_match_all('/[A-Z]/', $password) >= 1 && preg_match_all('/[a-z]/', $password) >= 1 && preg_match_all('/[0-9]/', $password) >= 1 && preg_match_all('/[\?!_\-=\)\({}\[\];,\."\/\\\\@#%\$&\^\*~\'<>]/', $password) >= 1,
            self::SECURITY_HIGH => strlen($password) >= 15 && preg_match_all('/[A-Z]/', $password) >= 1 && preg_match_all('/[a-z]/', $password) >= 1 && preg_match_all('/[0-9]/', $password) >= 1 && preg_match_all('/[\?!_\-=\)\({}\[\];,\."\/\\\\@#%\$&\^\*~\'<>]/', $password) >= 3,
            self::PLESK => strlen($password) >= 12
        };
    }
}