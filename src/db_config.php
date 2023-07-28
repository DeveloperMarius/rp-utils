<?php

namespace utils;

use JetBrains\PhpStorm\Deprecated;
use PDO;
use PDOException;

#[Deprecated(
    reason: 'Deprecated',
    replacement: 'DBConfig'
)]
class db_config{

    /**
     * @var db_config $default - Default DB_Config
     */
    public static db_config $default;

    /**
     * @var string $db_name - Name of the database
     */
    private string $db_name;
    /**
     * @var string $db_host - Host of the database
     */
    private string $db_host;
    /**
     * @var string $db_username - Username
     */
    private string $db_username;
    /**
     * @var string $db_password - Password
     */
    private string $db_password;
    /**
     * @var PDO|null - PDO connection
     */
    private ?PDO $connection = null;

    /**
     * db_config constructor.
     * @param string $db_name
     * @param string $db_host
     * @param string $db_username
     * @param string $db_password
     */
    public function __construct(string $db_name, string $db_host, string $db_username, string $db_password){
        $this->db_name = $db_name;
        $this->db_host = $db_host;
        $this->db_username = $db_username;
        $this->db_password = $db_password;
    }

    /**
     * @return string
     */
    public function getDbName(): string{
        return $this->db_name;
    }

    /**
     * @return string
     */
    public function getDbHost(): string{
        return $this->db_host;
    }

    /**
     * @return string
     */
    public function getDbUsername(): string{
        return $this->db_username;
    }

    /**
     * @return string
     */
    public function getDbPassword(): string{
        return $this->db_password;
    }

    /**
     * @return PDO
     * @throws PDOException
     */
    public function getConnection(): PDO {
        if($this->connection == null){
            $dsn = 'mysql:host=' . $this->getDbHost() . ';dbname=' . $this->getDbName() . ';charset=utf8';
            $this->connection = new PDO($dsn, $this->getDbUsername(), $this->getDbPassword());
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->connection;
    }

}