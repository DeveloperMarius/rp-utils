<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeFloat extends DBType{

    /**
     * DBTypeFloat constructor.
     */
    public function __construct(){
        parent::initialize('FLOAT', 'NOT NULL');
    }

}