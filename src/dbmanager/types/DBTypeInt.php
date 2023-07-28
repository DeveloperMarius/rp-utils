<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeInt extends DBType{

    /**
     * DBTypeInt constructor.
     */
    public function __construct(){
        parent::initialize('INT(11)', 'NOT NULL');
    }

}