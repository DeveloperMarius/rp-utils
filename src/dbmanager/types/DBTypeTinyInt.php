<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeTinyInt extends DBType{

    /**
     * DBTypeTinyInt constructor.
     */
    public function __construct(){//0 bis 255
        parent::initialize('TINYINT(4)', 'NOT NULL');
    }

}