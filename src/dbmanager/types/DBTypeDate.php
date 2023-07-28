<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeDate extends DBType{

    /**
     * DBTypeDate constructor.
     */
    public function __construct(){
        parent::initialize('DATE', 'NOT NULL');
    }

}