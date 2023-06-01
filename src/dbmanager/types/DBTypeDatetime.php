<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeDatetime extends DBType{

    /**
     * DBTypeDatetime constructor.
     */
    public function __construct(){
        parent::initialize('DATETIME', 'NOT NULL');
    }

}