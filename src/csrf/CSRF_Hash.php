<?php

namespace utils\csrf;

class CSRF_Hash implements \JsonSerializable{

    private $hash;
    private $context;
    private $expire;

    /**
     * [__construct description]
     * @param string  $context   [description]
     * @param integer $time2Live Number of seconds before expiration
     */
    function __construct($context, $time2Live=0, $hashSize=64) {
        // Save context name
        $this->context = $context;

        // Generate hash
        $this->hash = $this->_generateHash($hashSize);

        // Set expiration time
        if ($time2Live > 0) {
            $this->expire = time() + $time2Live;
        }
        else {
            $this->expire = 0;
        }
    }

    /**
     * The hash function to use
     * @param  int $n 	Size in bytes
     * @return string 	The generated hash
     */
    private function _generateHash ($n) {
        return bin2hex(openssl_random_pseudo_bytes($n/2));
    }

    /**
     * Check if hash has expired
     * @return boolean
     */
    public function hasExpire () {
        if ($this->expire === 0 || $this->expire > time()) {
            return false;
        }
        return true;
    }

    /**
     * Verify hash
     * @return boolean
     */
    public function verify ($hash, $context='') {
        if (strcmp($context, $this->context) === 0 && !$this->hasExpire() && strcmp($hash, $this->hash) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Check Context
     * @return boolean
     */
    public function inContext ($context='') {
        if (strcmp($context, $this->context) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Get hash
     * @return string
     */
    public function get () {
        return $this->hash;
    }

    public function jsonSerialize(){
        return $this->get();
    }

}