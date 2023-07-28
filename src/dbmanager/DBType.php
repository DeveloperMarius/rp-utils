<?php

namespace utils\dbmanager;

class DBType{

    /**
     * @var string $db_type
     */
    private string $db_type;
    /**
     * @var string $db_type_extra
     */
    private string $db_type_extra;
    /**
     * @var mixed $default
     */
    private mixed $default;

    /**
     * @param string $db_type
     * @param string $db_type_extra
     * @param mixed $default
     */
    public function initialize(string $db_type, string $db_type_extra, mixed $default = ''){
        $this->db_type = $db_type;
        $this->db_type_extra = $db_type_extra;
        $this->default = $default;
    }

    /* Getters */

    /**
     * @return string
     */
    public function getDbType(): string{
        return $this->db_type;
    }

    /**
     * @param string $db_type
     */
    protected function setDbType(string $db_type): void{
        $this->db_type = $db_type;
    }

    /**
     * @return string
     */
    public function getDbTypeExtra(): string{
        return $this->db_type_extra;
    }

    /**
     * @return bool
     */
    public function hasDefault(): bool{
        return $this->default !== '';
    }

    /**
     * @return mixed
     */
    public function getDefault(): mixed{
        return $this->default;
    }

    /* Setters */

    /**
     * @param string $db_type_extra
     * @return self
     */
    public function setDbTypeExtra(string $db_type_extra): self{
        $this->db_type_extra = $db_type_extra;
        return $this;
    }

    /**
     * @return self
     */
    public function setDbTypeDefaultNull(): self{
        $this->db_type_extra = 'NULL DEFAULT NULL';
        return $this;
    }

    /**
     * @return self
     */
    public function setDbTypeNull(): self{
        $this->db_type_extra = 'NULL';
        return $this;
    }

    /**
     * @param string $db_type_extra
     * @return self
     */
    public function addToDbTypeExtra(string $db_type_extra): self{
        $this->db_type_extra = $this->getDbTypeExtra() . ' ' . $db_type_extra;
        return $this;
    }

    /**
     * @param mixed $default
     * @return self
     */
    public function setDefault(mixed $default): self{
        $this->default = $default;
        return $this;
    }

}