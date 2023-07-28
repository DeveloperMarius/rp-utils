<?php

namespace utils\dbmanager\types;

use utils\dbmanager\DBType;

class DBTypeString extends DBType{

    /**
     * DBTypeString constructor.
     * @param int $size
     */
    public function __construct(int $size = 200){
        parent::initialize('VARCHAR(' . $size . ')', 'NOT NULL');
    }

    /**
     * @param int $size
     */
    public function setSize(int $size){
        $this->setDbType('VARCHAR(' . $size . ')');
    }

}