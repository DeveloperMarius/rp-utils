<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeText extends DBType{

    /**
     * DBTypeText constructor.
     */
    public function __construct(){
        parent::initialize('TEXT', 'NOT NULL');
    }

}