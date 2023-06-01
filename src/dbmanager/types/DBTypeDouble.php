<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeDouble extends DBType{

    /**
     * DBTypeDouble constructor.
     */
    public function __construct(){
        parent::initialize('DOUBLE', 'NOT NULL');
    }

}