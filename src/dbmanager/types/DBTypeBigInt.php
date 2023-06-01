<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeBigInt extends DBType{

    /**
     * DBTypeBigInt constructor.
     */
    public function __construct(){
        parent::initialize('BIGINT(20)', 'NOT NULL');
    }

}