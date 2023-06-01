<?php
/**
 * @copyright (c) 2020, Marius Karstedt
 * @link https://marius-karstedt.de
 * All rights reserved.
 */

namespace utils;

use JetBrains\PhpStorm\Deprecated;

#[Deprecated(reason: 'Use Security:Hash()')]
class Hash {

    /**
     * @var PasswordHash|null $_hasher
     */
    private static ?PasswordHash $_hasher = null;

    /**
     * Disabled Captcha constructor.
     */
    private function __construct() {}

    /**
     * Disabled Clone Function
     */
    private function __clone() {}

    /**
     * Get a hash of text
     *
     * @param string $text The clear text
     * @return string The hash
     */
    public static function make(string $text): string{
        return static::getHasher()->HashPassword($text);
    }

    /**
     * @param string $text The cleartext
     * @param string $hash The hash
     * @return boolean
     */
    public static function check(string $text, string $hash): bool{
        return static::getHasher()->CheckPassword($text,$hash);
    }

    /**
     * Get the singleton password hasher object
     *
     * @return PasswordHash
     */
    private static function getHasher(): PasswordHash{
        if (static::$_hasher === NULL) {
            static::$_hasher = new PasswordHash(8, false);
        }
        return static::$_hasher;
    }
}
