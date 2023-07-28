<?php

namespace utils\dbmanager;

use utils\db_config;

class DBTable{

    /**
     * DBTable constructor.
     * @param string $name
     * @param DBColumn[] $columns
     */
    public function __construct(private string $name, private array $columns = array()){}

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return DBColumn[]
     */
    public function getColumns(): array{
        return $this->columns;
    }

    /**
     * @param string $name
     * @return DBColumn|null
     */
    public function getColumn(string $name): ?DBColumn{
        $response = array_values(array_filter($this->getColumns(), function($column) use ($name){
            return $column->getName() === $name;
        }));
        return $response[0] ?? null;
    }

}