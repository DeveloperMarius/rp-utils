<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeUUID extends DBType{

    /**
     * DBTypeUUID constructor.
     */
    public function __construct(){
        parent::initialize('UUID', 'NOT NULL');
    }

}