<?php

namespace utils\dbmanager;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;

class DBColumn{

    /**
     * @var bool $auto_increment
     */
    private bool $auto_increment = false;
    /**
     * @var string|null $foreign_key
     */
    private ?string $foreign_key = null;
    /**
     * @var bool $primary_key
     */
    private bool $primary_key = false;
    /**
     * @var DBFunction[] $db_functions_insert
     */
    private array $db_functions_insert = array();
    /**
     * @var DBFunction[] $db_functions_response
     */
    private array $db_functions_response = array();
    /**
     * @var mixed|null $default_generator
     */
    private mixed $default_generator = null;

    /**
     * DBColumn constructor.
     * @param string $name
     * @param DBType $type
     */
    public function __construct(private string $name, private DBType $type){}

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return DBType
     */
    public function getType(): DBType{
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isAutoIncrement(): bool{
        return $this->auto_increment;
    }

    /**
     * @param bool $auto_increment
     * @return self
     */
    public function setAutoIncrement(bool $auto_increment): self{
        $this->auto_increment = $auto_increment;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey(): bool{
        return $this->primary_key;
    }

    /**
     * @param bool $primary_key
     * @return self
     */
    public function setPrimaryKey(bool $primary_key): self{
        $this->primary_key = $primary_key;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasForeignKey(): bool{
        return $this->foreign_key !== null;
    }

    /**
     * @return string|null
     */
    private function getForeignKey(): ?string{
        return $this->foreign_key;
    }

    /**
     * @param DBFunction $function
     * @return void
     */
    public function applyBeforeInsert(DBFunction $function){
        $this->db_functions_insert[] = $function;
    }

    /**
     * @param DBFunction $function
     * @return void
     */
    public function applyBeforeResponse(DBFunction $function){
        $this->db_functions_response[] = $function;
    }

    /**
     * @param bool $foreign_key
     * @param string|DBColumn|null $references
     * @param string|null $references_table
     * @param string|null $on_delete
     * @param string|null $on_update
     */
    public function setForeignKey(bool $foreign_key, string|DBColumn|null $references = null, ?string $references_table = null, #[ExpectedValues([null, "CASCADE", "SET NULL", "SET DEFAULT"])] ?string $on_delete = null, #[ExpectedValues([null, "CASCADE", "SET NULL", "SET DEFAULT"])] ?string $on_update = null): void{
        if($foreign_key && $references !== null && $references_table !== null){
            $this->foreign_key = "FOREIGN KEY (`" . $this->getName() . "`) REFERENCES `" . $references_table . "` (`" . ($references instanceof DBColumn ? $references->getName() : $references) . "`)" .
                ($on_delete !== null ? " ON DELETE " . $on_delete : "") .
                ($on_update !== null ? " ON UPDATE " . $on_update : "");
        }else{
            $this->foreign_key = null;
        }
    }

    /* Other */

    /**
     * @return string
     */
    #[Pure]
    public function getColumnString(): string{
        return "`{$this->getName()}` {$this->getType()->getDbType()} {$this->getType()->getDbTypeExtra()}" .
            ($this->getType()->hasDefault() ? " DEFAULT " . $this->parseValueForDbString($this->getType()->getDefault()) : "") .
            ($this->isAutoIncrement() ? " AUTO_INCREMENT" : "");
    }

    /**
     * @return array
     */
    #[Pure]
    public function getColumnExtras(): array{
        $extras = array();
        if($this->isPrimaryKey()){
            $extras[] = "PRIMARY KEY (`" . $this->getName() . "`)";
        }
        if($this->hasForeignKey()){
            $extras[] = $this->getForeignKey();
        }
        return $extras;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function parseValueForDbString(mixed $value): string{
        if($value === null){
            $value = "NULL";
        }else if(is_string($value)){
            $value = "'" . $value . "'";
        }
        foreach($this->db_functions_insert as $function){
            $value = DBFunction::perform($function, $value);
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function transformInsertValueKey(mixed $value): string{
        foreach($this->db_functions_insert as $function){
            $value = DBFunction::perform($function, $value);
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function transformResponseValueKey(mixed $value): string{
        foreach($this->db_functions_response as $function){
            $value = DBFunction::perform($function, $value);
        }
        return $value;
    }

    /**
     * @param mixed $default_generator
     */
    public function setDefaultGenerator(mixed $default_generator): void{
        $this->default_generator = $default_generator;
    }

    /**
     * @return string
     */
    public function generateDefault(): mixed{
        if(is_callable($this->default_generator)){
            return ($this->default_generator)();
        }else{
            return $this->default_generator;
        }
    }

}