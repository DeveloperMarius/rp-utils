<?php

namespace utils\csrf;

/**
 * By: https://github.com/GramThanos/php-csrf
 *
 * Class CSRF
 * @package utils\csrf
 */
class CSRF{

    private string $name;
    private array $hashes;
    private int $hashTime2Live;
    private int $hashSize;
    private string $inputName;

    /**
     * Initialize a CSRF instance
     * @param string  $session_name  Session name
     * @param string  $input_name     Form name
     * @param integer $hashTime2Live Default seconds hash before expiration
     * @param integer $hashSize      Default hash size in chars
     */
    function __construct ($session_name = 'csrf-lib', $input_name = 'key-awesome', $hashTime2Live = 0, $hashSize = 64) {
        // Session mods
        $this->name = $session_name;
        // Form input name
        $this->inputName = $input_name;
        // Default time before expire for hashes
        $this->hashTime2Live = $hashTime2Live;
        // Default hash size
        $this->hashSize = $hashSize;
        // Load hash list
        $this->_load();
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return int
     */
    public function getHashSize(): int{
        return $this->hashSize;
    }

    /**
     * @return int
     */
    public function getHashTime2Live(): int{
        return $this->hashTime2Live;
    }

    /**
     * @return string
     */
    public function getInputName(): string{
        return $this->inputName;
    }

    /**
     * Generate a CSRF_Hash
     * @param  string  $context    Name of the form
     * @param  integer $time2Live  Seconds before expiration
     * @param  integer $max_hashes Clear old context hashes if more than this number
     * @return CSRF_Hash
     */
    private function generateHash($context = '', $time2Live = -1, $max_hashes = 5) {
        // If no time2live (or invalid) use default
        if ($time2Live < 0) $time2Live = $this->hashTime2Live;
        // Generate new hash
        $hash = new CSRF_Hash($context, $time2Live, $this->hashSize);
        // Save it
        array_push($this->hashes, $hash);

        /*if ($this->clearHashes($context, $max_hashes) === 0) {
            $this->_save();
        }*/
        $this->_save();
        // Return hash info
        return $hash;
    }

    /**
     * Generate a string hash
     * @param  string  $context    Name of the form
     * @param  integer $time2Live  Seconds before expire
     * @param  integer $max_hashes Clear old context hashes if more than this number
     * @return integer             hash as a string
     */
    public function string($context = '', $time2Live = -1, $max_hashes = 5) {
        // Generate hash
        $hash = $this->generateHash ($context, $time2Live, $max_hashes);
        // Generate html input string
        return $hash->get();
    }

    /**
     * Get the hashes of a context
     * @param  string  $context    the group to clean
     * @param  integer $max_hashes max hashes to get
     * @return array               array of hashes as strings
     */
    public function getHashes($context = '', $max_hashes = -1) {
        $len = count($this->hashes);
        $hashes = array();
        // Check in the hash list
        for ($i = $len - 1; $i >= 0 && $len > 0; $i--) {
            if ($this->hashes[$i]->inContext($context)) {
                array_push($hashes, $this->hashes[$i]->get());
                $len--;
            }
        }
        return $hashes;
    }

    /**
     * Clear the hashes of a context
     * @param  string  $context    the group to clean
     * @param  integer $max_hashes ignore first x hashes
     * @return integer             number of deleted hashes
     */
    public function clearHashes($context = '', $max_hashes = 0) {
        $ignore = $max_hashes;
        $deleted = 0;
        // Check in the hash list
        for ($i = count($this->hashes) - 1; $i >= 0; $i--) {
            if ($this->hashes[$i]->inContext($context) && $ignore-- <= 0) {
                array_splice($this->hashes, $i, 1);
                $deleted++;
            }
        }
        if ($deleted > 0) {
            $this->_save();
        }
        return $deleted;
    }

    /**
     * @param string $context
     * @param string|null $hash
     * @return bool
     */
    public function clearHash($context = '', $hash = null): bool{
        // Check in the hash list
        if (is_null($hash)) {
            if(isset($_SERVER['HTTP-X-CSRF-TOKEN'])){
                $hash = $_SERVER['HTTP-X-CSRF-TOKEN'];
            }else if (isset($_POST[$this->inputName])) {
                $hash = $_POST[$this->inputName];
            } else if (isset($_GET[$this->inputName])) {
                $hash = $_GET[$this->inputName];
            } else {
                return false;
            }
        }
        for ($i = count($this->hashes) - 1; $i >= 0; $i--) {
            if ($this->hashes[$i]->inContext($context)) {
                if(strcmp($hash, $this->hashes[$i]->get()) === 0){
                    array_splice($this->hashes, $i, 1);
                    $this->_save();
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Validate by context
     * @param  string $context Name of the form
     * @return boolean         Valid or not
     */
    public function validate($context = '', $hash = null) {
        // If hash was not given, find hash
        if (is_null($hash)) {
            if(isset($_SERVER['HTTP-X-CSRF-TOKEN'])){
                $hash = $_SERVER['HTTP-X-CSRF-TOKEN'];
            }else if (isset($_POST[$this->inputName])) {
                $hash = $_POST[$this->inputName];
            } else if (isset($_GET[$this->inputName])) {
                $hash = $_GET[$this->inputName];
            } else {
                return false;
            }
        }

        // Check in the hash list
        for ($i = count($this->hashes) - 1; $i >= 0; $i--) {
            if ($this->hashes[$i]->verify($hash, $context)) {
                array_splice($this->hashes, $i, 1);
                $this->_save();
                return true;
            }
        }
        return false;
    }

    /**
     * Load hash list
     */
    private function _load() {
        $this->hashes = array();
        // If there are hashes on the session
        if (isset($_SESSION[$this->name])) {
            // Load session hashes
            $session_hashes = unserialize($_SESSION[$this->name]);
            // Ignore expired
            for ($i = count($session_hashes) - 1; $i >= 0; $i--) {
                // If an expired found, the rest will be expired
                if ($session_hashes[$i]->hasExpire()) {
                    break;
                }
                array_unshift($this->hashes, $session_hashes[$i]);
            }
            if (count($this->hashes) != count($session_hashes)) {
                $this->_save();
            }
        }
    }

    /**
     * Save hash list
     */
    private function _save(){
        $_SESSION[$this->name] = serialize($this->hashes);
    }


}