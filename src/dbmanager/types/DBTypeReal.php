<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeReal extends DBType{

    /**
     * DBTypeReal constructor.
     */
    public function __construct(){
        parent::initialize('REAL', 'NOT NULL');
    }

}