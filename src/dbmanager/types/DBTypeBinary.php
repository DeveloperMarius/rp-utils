<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeBinary extends DBType{

    /**
     * DBTypeInt constructor.
     */
    public function __construct(int $size = 20){
        parent::initialize('BINARY(' . $size . ')', 'NOT NULL');
    }

}