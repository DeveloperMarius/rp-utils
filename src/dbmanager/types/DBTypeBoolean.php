<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeBoolean extends DBType{

    /**
     * DBTypeDouble constructor.
     */
    public function __construct(){
        parent::initialize('BOOLEAN', 'NOT NULL');
    }

}