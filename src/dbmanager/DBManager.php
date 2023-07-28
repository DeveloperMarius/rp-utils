<?php
/**
 * @copyright (c) 2020, Marius Karstedt
 * @link https://marius-karstedt.de
 * All rights reserved.
 */

namespace utils\dbmanager;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Pure;
use PDO;
use PDOException;
use PDOStatement;
use utils\exception\EntityNotFound;
use utils\exception\SQLException;
use utils\Util;

/**
 * Class DBManager
 * @package utils\dbmanager
 *
 * TODO: multiple executions in a row? clear results from last
 *
 * Emoji Support: `html` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
 */
class DBManager{

    /**
     * @var DBManager|null $DBManager
     */
    private static ?DBManager $DBManager = null;

    /**
     * @var String $table
     */
    private string $table = 'null';
    /**
     * @var PDO $_dbconn
     */
    private PDO $_dbconn;
    /**
     * @var DBConfig $db_config
     */
    private DBConfig $db_config;
    /**
     * @var string $order_type
     */
    private string $order_type = 'ASC';
    /**
     * @var string|int $order_key
     */
    private string|int $order_key = 1;
    /**
     * @var bool $ignorecase
     */
    private bool $ignorecase = false;
    /**
     * @var bool $smart_parser
     */
    private bool $smart_parser = true;
    /**
     * @var bool $encode_htmlspecialchars
     */
    #[Deprecated]
    private bool $encode_htmlspecialchars = true;
    /**
     * @var bool $id
     */
    #[Deprecated]
    private bool $id = true;
    /**
     * @var bool $uuid
     */
    #[Deprecated]
    private bool $uuid = false;
    /**
     * @var bool $bin_uuid
     */
    #[Deprecated]
    private bool $bin_uuid = false;
    /**
     * @var DBTable|null $table_structure
     */
    private ?DBTable $table_structure = null;
    /**
     * @var int|null $limit
     */
    private ?int $limit = null;
    /**
     * @var int|null $offset
     */
    private ?int $offset = null;
    /**
     * @var PDOStatement|null $statement
     */
    private ?PDOStatement $statement = null;
    /**
     * @var array|null $where
     */
    private ?array $where = null;
    /**
     * @var bool|null $execute_status
     */
    private ?bool $execute_status = null;
    /**
     * @var string|null $model_namespace
     */
    private ?string $model_namespace = null;
    /**
     * @var string|null $returning
     */
    private ?string $returning = null;
    /**
     * @var mixed|null $returning_value
     */
    private mixed $returning_value = null;

    const
        CALLBACK_ID = 1,
        CALLBACK_STATUS = 2;

    /* Default context alias */

    /**
     * @var string $context_default
     */
    private static string $context_default = 'default';

    /**
     * @param string $context_default
     */
    public static function setContextDefault(string $context_default): void{
        self::$context_default = $context_default;
    }

    /**
     * @return string
     */
    public static function getContextDefault(): string{
        return self::$context_default;
    }

    /* default model namespace */

    /**
     * @var string $context_default
     */
    private static string $model_namespace_default = 'default';

    /**
     * @param string $model_namespace_default
     */
    public static function setModelNamespaceDefault(string $model_namespace_default): void{
        self::$model_namespace_default = $model_namespace_default;
    }

    /**
     * @return string
     */
    public static function getModelNamespaceDefault(): string{
        return self::$model_namespace_default;
    }

    /* db_manager */

    /**
     * @var array $db_manager
     */
    private static array $db_manager = array();

    /**
     * @param string|null $context
     * @return DBManager
     * @throws SQLException
     */
    public static function setUpDBManager(?string $context = null): DBManager{
        if($context === null)
            $context = self::getContextDefault();
        self::$db_manager[$context] = new DBManager(self::getDbConfig($context) ?? null);

        return self::$db_manager[$context];
    }

    /**
     * @param string|null $context
     * @return DBManager|null
     * @throws SQLException
     */
    public static function getDBManager(?string $context = null): ?DBManager{
        if($context === null)
            $context = self::getContextDefault();
        $dbmanager = self::$db_manager[$context] ?? null;
        if($dbmanager === null){
            if(isset(self::$db_configs[$context])){
                $dbmanager = self::setUpDBManager($context);
            }
        }
        return $dbmanager ?? null;
    }

    /**
     * @param DBConfig|null $db_config
     * @return DBManager
     * @throws SQLException
     */
    #[Deprecated(
        reason: 'backwards compatibility. Use getDBManager() with context instead'
    )]
    public static function getDBManagerByDbConfig(?DBConfig $db_config){
        return new DBManager($db_config);
    }

    /* db_configs */

    /**
     * @var array $db_configs
     */
    private static array $db_configs = array();

    /**
     * @param DBConfig $db_config
     * @param string|null $context
     */
    public static function setUpDbConfig(DBConfig $db_config, ?string $context = null){
        if($context === null)
            $context = self::getContextDefault();
        self::$db_configs[$context] = $db_config;
    }

    /**
     * @param string|null $context
     * @return DBConfig|null
     */
    public static function getDbConfig(?string $context = null): ?DBConfig{
        if($context === null)
            $context = self::getContextDefault();
        return self::$db_configs[$context] ?? null;
    }

    /* end db_configs */

    /**
     * DBManager constructor.
     * @param DBConfig|null $db_config
     * @throws SQLException
     */
    private function __construct(?DBConfig $db_config = null){
        if($db_config == null){
            if(DBConfig::$default !== null)
                $this->db_config = DBConfig::$default;
            else
                throw new SQLException('No DBConfig defined');
        } else
            $this->db_config = $db_config;
        try{
            $this->model_namespace = $this->db_config->getModelNamespace();
            $this->_dbconn = $this->db_config->getConnection();
        }catch(PDOException $e){
            throw new SQLException('Error connecting to Database (' . $e->getMessage() . ')');
        }
    }

    /*#[Deprecated]
    public static function getDBManager(?db_config $db_config = null): ?DBManager{
        if(self::$DBManager === null){
            return new DBManager($db_config);
        }else{
            if($db_config == null)
                $db_config = db_config::$default;
            if(self::$DBManager->getDbConfig()->getDbName() !== $db_config->getDbName()){
                self::$DBManager = null;
                return self::getDBManager($db_config);
            }
        }
        return self::$DBManager;
    }*/

    /**
     * @return self
     */
    public function init(): self{
        $this->table = 'null';
        $this->order_type = 'ASC';
        $this->order_key = 1;
        $this->ignorecase = false;
        $this->smart_parser = true;
        $this->encode_htmlspecialchars = true;
        $this->id = true;
        $this->uuid = false;
        $this->bin_uuid = false;
        $this->limit = null;
        $this->offset = null;
        $this->statement = null;
        $this->where = null;
        $this->execute_status = null;
        $this->table_structure = null;
        $this->returning = null;
        $this->returning_value = null;
        return $this;
    }

    /**
     * @return DBConfig
     */
    public function _getDbConfig(): DBConfig{
        return $this->db_config;
    }

    /**
     * @return string|null
     */
    #[Pure]
    public function getModelNamespace(): ?string{
        if($this->model_namespace === null)
            return self::getModelNamespaceDefault();
        return $this->model_namespace;
    }

    /**
     * @return PDO
     */
    public function getDbconn(): PDO{
        return $this->_dbconn;
    }

    /**
     * @return String
     */
    public function getTable(): string{
        return $this->table;
    }

    /**
     * @param String $table
     * @return self
     */
    public function construct(string $table): self{
        $this->init();//Clear data for new request
        $this->table = $table;
        return $this;
    }

    /**
     * @return self
     */
    public function setOrderASC(): self{
        $this->order_type = 'ASC';
        return $this;
    }

    /**
     * @return self
     */
    public function setOrderDESC(): self{
        $this->order_type = 'DESC';
        return $this;
    }

    /**
     * @param int|string $order_key
     * @return self
     */
    public function setOrderBy(int|string $order_key): self{
        $this->order_key = $order_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderType(): string{
        return $this->order_type;
    }

    /**
     * @return int|string
     */
    public function getOrderKey(): int|string{
        return $this->order_key;
    }

    /**
     * @return bool
     */
    public function isIgnoreCase(): bool{
        return $this->ignorecase;
    }

    /**
     * @param bool $ignorecase
     * @return self
     */
    public function setIgnoreCase(bool $ignorecase): self{
        $this->ignorecase = $ignorecase;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSmartParser(): bool{
        return $this->smart_parser;
    }

    /**
     * @param bool $smart_parser
     * @return self
     */
    public function setSmartParser(bool $smart_parser): self{
        $this->smart_parser = $smart_parser;
        return $this;
    }

    /**
     * @return bool
     */
    #[Deprecated]
    public function isEncodeHtmlspecialchars(): bool{
        return $this->encode_htmlspecialchars;
    }

    /**
     * @param bool $encode_htmlspecialchars
     * @return DBManager
     */
    #[Deprecated]
    public function setEncodeHtmlspecialchars(bool $encode_htmlspecialchars): self{
        $this->encode_htmlspecialchars = $encode_htmlspecialchars;
        return $this;
    }

    /**
     * @param bool $id
     * @return self
     */
    #[Deprecated]
    public function setId(bool $id): self{
        $this->id = $id;
        return $this;
    }

    /**
     * @return bool
     */
    #[Deprecated]
    public function hasId(): bool{
        return $this->id;
    }

    /**
     * @param bool $use
     * @return self
     */
    #[Deprecated]
    public function setUseUuid(bool $use = true): self{
        $this->uuid = $use;
        return $this;
    }

    /**
     * @return bool
     */
    #[Deprecated]
    public function isUsingUuid(): bool{
        return $this->uuid;
    }

    /**
     * @param bool $use
     * @return self
     */
    #[Deprecated]
    public function setUseBinUuid(bool $use = true): self{
        $this->bin_uuid = $use;
        return $this;
    }

    /**
     * @return bool
     */
    #[Deprecated]
    public function isUsingBinUuid(): bool{
        return $this->bin_uuid;
    }

    /**
     * @param DBTable $table_structure
     */
    public function setTableStructure(DBTable $table_structure): void{
        $this->table_structure = $table_structure;
    }

    /**
     * @return DBTable|null
     */
    public function getTableStructure(): ?DBTable{
        return $this->table_structure;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return self
     */
    public function setLimit(?int $limit, ?int $offset = null): self{
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int{
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getOffset(): ?int{
        return $this->offset;
    }

    /**
     * @return bool
     */
    #[Pure]
    public function hasLimit(): bool{
        return $this->getLimit() !== null;
    }

    /**
     * @return bool
     */
    #[Pure]
    public function hasOffset(): bool{
        return $this->getOffset() !== null;
    }

    /* Private */

    /**
     * @return PDOStatement|null
     */
    private function getStatement(): ?PDOStatement{
        return $this->statement;
    }

    /**
     * @param PDOStatement|null $statement
     * @return self
     */
    private function setStatement(?PDOStatement $statement): self{
        $this->statement = $statement;
        return $this;
    }

    /**
     * @return array|null
     */
    private function getWhere(): ?array{
        return $this->where;
    }

    /**
     * @param array|null $where
     * @return self
     */
    private function setWhere(?array $where): self{
        $this->where = $where;
        return $this;
    }

    /**
     * @return bool|null
     */
    private function getExecuteStatus(): ?bool{
        return $this->execute_status;
    }

    /**
     * @param bool $execute_status
     * @return self
     */
    private function setExecuteStatus(bool $execute_status): self{
        $this->execute_status = $execute_status;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReturning(): ?string{
        return $this->returning;
    }

    /**
     * @param string $returning
     * @return self
     */
    public function setReturning(string $returning): self{
        $this->returning = $returning;
        return $this;
    }

    private function provideModel(string $className): self{
        $this->model = $className;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getReturningValue(): mixed{
        return $this->returning_value;
    }

    private function escapeColumnName(string $columnName): string{
        return match ($this->_getDbConfig()->getDbType()){
            DBConfig::MYSQL => '`' . $columnName . '`',
            DBConfig::POSTGRESQL => '"' . $columnName . '"'
        };
    }

    /* Operators */

    /**
     * @return array
     * @throws SQLException
     */
    #[ArrayShape([
        'question' => "string",
        'bindings' => "array"
    ])]
    private function buildWhere(): array{
        $where = $this->getWhere();
        $ignorecase = $this->isIgnoreCase();
        $question = '';
        $bindings = array();
        if($where != null){
            $question = ' WHERE ';
            $currentOperator = DBManagerOperator::OPERATOR_AND;
            for($i = 0; $i < sizeof($where); $i++){
                $value = $where[$i];
                if($value instanceof DBManagerOperator){
                    $param = $value->getValue();
                    if(is_array($param)){
                        if(!Util::isAssocArray($param)){
                            if($value->getOperator() === DBManagerOperator::OPERATOR_IN || $value->getOperator() === DBManagerOperator::OPERATOR_NOT_IN){
                                if(sizeof($param) === 0){
                                    if($value->getOperator() === DBManagerOperator::OPERATOR_IN){
                                        $where_string = 'FALSE';
                                    } else if($value->getOperator() === DBManagerOperator::OPERATOR_NOT_IN){
                                        $where_string = 'TRUE';
                                    } else {
                                        //never called
                                        throw new SQLException('Operator "' . $value->getOperator() . '" not supported for arrays');
                                    }
                                } else {
                                    $sub_bindings = array();
                                    $j = 0;
                                    foreach($param as $sub_value){
                                        if($this->isSmartParser())
                                            $sub_value = (new DBSmartParser('__N/A__', $sub_value))->parse()->getValue();
                                        $sub_bindings[':w_' . $i . '_' . $j] = ($ignorecase && is_string($sub_value) ? strtolower($sub_value) : $sub_value);
                                        $j++;
                                    }
                                    $where_string = ($ignorecase ? 'LOWER(' : '') . $this->escapeColumnName($value->getKey()) . ($ignorecase ? ')' : '') . ' ' . $value->getOperator() . ' (' . join(', ', array_keys($sub_bindings)) . ')';
                                    $bindings = array_merge($bindings, $sub_bindings);
                                }
                            } else {
                                throw new SQLException('Operator "' . $value->getOperator() . '" not supported for arrays');
                            }
                        } else {
                            throw new SQLException('Assoc arrays are not supported');
                        }
                    }else{
                        if($value->getValue() !== null){
                            $column = $this->getTableStructure()?->getColumn($value->getKey());
                            $value_key = ':w_' . $i;
                            if($column !== null){
                                $value_key = $column->transformInsertValueKey($value_key);
                            }
                            if($this->isSmartParser())
                                $value->setValue((new DBSmartParser($value->getKey(), $value->getValue()))->parse()->getValue());
                            $where_string = ($ignorecase ? 'LOWER(' : '') . $this->escapeColumnName($value->getKey()) . ($ignorecase ? ')' : '') . ' ' . $value->getOperator() . ' ' . $value_key;
                            $bindings[':w_' . $i] = ($ignorecase && is_string($param) ? strtolower($param) : $param);
                        }else{
                            $where_string = $this->escapeColumnName($value->getKey()) . ' ' . $value->getOperator() . ' null';
                        }
                    }
                    if($i !== sizeof($where)-1){
                        if($value->getNextOperator() === DBManagerOperator::OPERATOR_OR){
                            $question .= ($currentOperator === DBManagerOperator::OPERATOR_OR ? '' : '(') . $where_string . ' ' . $value->getNextOperator() . ' ';
                        }else{
                            $question .= $where_string . ($currentOperator === DBManagerOperator::OPERATOR_OR ? ')' : '') . ' ' . $value->getNextOperator() . ' ';
                        }
                    }else{
                        $question .= $where_string . ($currentOperator === DBManagerOperator::OPERATOR_OR ? ')' : '');
                    }
                    $currentOperator = $value->getNextOperator();
                }
            }
        }
        return array(
            'question' => $question,
            'bindings' => $bindings
        );
    }

    /**
     * @param array $set
     * @return array
     */
    #[ArrayShape([
        'setter' => "string",
        'bindings' => "array"
    ])]
    private function buildSet(array $set): array{
        $bindings = array();
        $setter = array();
        $i = 0;
        foreach ($set as $key => $value){
            if($value !== null){
                $value_key = ':s_' . $i;
                $column = $this->getTableStructure()?->getColumn($key);
                if($column !== null){
                    $value_key = $column->transformInsertValueKey($value_key);
                }
                $setter[] = $this->escapeColumnName($key) . ' = ' . $value_key;
                if($this->isSmartParser())
                    $value = (new DBSmartParser($key, $value))->parse()->getValue();
                /*if($this->isEncodeHtmlspecialchars()){
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }*/
                $bindings[':s_' . $i] = $value;
                $i++;
            }else{
                $setter[] = $this->escapeColumnName($key) . ' = null';
            }
        }
        return array(
            'setter' => ' SET ' . join(', ', $setter),
            'bindings' => $bindings
        );
    }

    /**
     * @param array|string $columns
     * @return array
     * @throws SQLException
     */
    #[ArrayShape([
        'query' => "string",
        'bindings' => "array"
    ])]
    private function buildSelectQuery(string|array $columns = '*'): array{
        $where = $this->buildWhere();
        $bindings = $where['bindings'];
        if(!is_array($columns) && $columns !== '*'){
            $columns = [$columns];
        }
        if($this->getTableStructure() !== null){
            $parsed_columns = array();
            if($columns === '*'){
                $columns = $this->getTableStructure()->getColumns();
            }else{
                $columns = array_filter($this->getTableStructure()->getColumns(), fn($column) => in_array($column->getName(), $columns));
            }
            foreach($columns as $column){
                $transformer = $column->transformResponseValueKey($this->escapeColumnName($column->getName()));
                $parsed_columns[] = $transformer . ($transformer === $this->escapeColumnName($column->getName()) ? '' : ' AS ' . $this->escapeColumnName($column->getName()));
            }
            $selector = join(', ', $parsed_columns);
        }else{
            if($columns === '*'){
                $selector = '*';
            }else{
                $selector = join(', ', $columns);
            }
        }
        $where['question'] .= $this->buildSelectQuerySuffix();
        $query = 'SELECT ' . $selector . ' FROM ' . $this->escapeColumnName($this->getTable()) . $where['question'];
        return array(
            'query' => $query,
            'bindings' => $bindings
        );
    }

    private function buildSelectQuerySuffix(): string{
        $question = ' ORDER BY ' . $this->getOrderKey() . ' ' . $this->getOrderType();
        if($this->hasLimit()){
            $question .= ' LIMIT ' . $this->getLimit();
            if($this->hasOffset())
                $question .= ' OFFSET ' . $this->getOffset();
        }
        return $question;
    }

    /**
     * @param array $set
     * @return array
     * @throws SQLException
     */
    #[ArrayShape([
        'query' => "string",
        'bindings' => "array"
    ])]
    private function buildUpdateQuery(array $set): array{
        $where = $this->buildWhere();
        $set = $this->buildSet($set);
        $bindings = array_merge($where['bindings'], $set['bindings']);
        $query = 'UPDATE ' . $this->escapeColumnName($this->getTable()) . $set['setter'] . $where['question'];
        return array(
            'query' => $query,
            'bindings' => $bindings
        );
    }

    /**
     * @param array $keys
     * @param array $content
     * @return array
     */
    #[ArrayShape([
        'query' => "string",
        'bindings' => "array"
    ])]
    private function buildInsertQuery(array $keys, array $content): array{
        $insert_keys = array();
        $insert_values = array();
        $bindings = array();
        $i = 0;
        foreach ($keys as $key){
            $insert_keys[] = $this->escapeColumnName($key);
            $value_key = ':i_' . $i;
            $column = $this->getTableStructure()?->getColumn($key);

            if($column !== null){
                if($content[$key] !== null){
                    $insert_values[] = $column->transformInsertValueKey($value_key);
                    if($this->isSmartParser())
                        $content[$key] = (new DBSmartParser($key, $content[$key]))->parse()->getValue();
                    $bindings[$value_key] = $content[$key];
                }else{
                    $insert_values[] = 'null';
                    //$bindings[$value_key] = $column->generateDefault();
                }
            }else{
                if($content[$key] !== null){
                    if($this->isSmartParser())
                        $content[$key] = (new DBSmartParser($key, $content[$key]))->parse()->getValue();
                    $bindings[$value_key] = $content[$key];
                    $insert_values[] = $value_key;
                }else
                    $insert_values[] = 'null';
            }
            $i++;
        }
        if($this->getTableStructure() !== null){
            foreach(array_filter($this->getTableStructure()->getColumns(), fn($column) => !in_array($column->getName(), $keys) && !$column->isAutoIncrement()) as $column){
                $insert_keys[] = $this->escapeColumnName($column->getName());

                $default = $column->generateDefault();
                if($default === null){
                    $insert_values[] = 'null';
                }else{
                    $value_key = ':i_' . $i;
                    $bindings[$value_key] = $default;
                    $insert_values[] = $column->transformInsertValueKey($value_key);
                }

                $i++;
            }
        }
        $query = 'INSERT INTO ' . $this->getTable() . ' (' . join(', ', $insert_keys) . ') VALUES (' . join(', ', $insert_values) . ')';
        return array(
            'query' => $query,
            'bindings' => $bindings
        );
    }

    #[ArrayShape([
        'query' => "string",
        'bindings' => "array"
    ])]
    private function buildDeleteQuery(): array{
        $where = $this->buildWhere();
        $query = 'DELETE FROM ' . $this->escapeColumnName($this->getTable()) . $where['question'];
        return array(
            'query' => $query,
            'bindings' => $where['bindings']
        );
    }

    /**
     * @param array $where
     * @return self
     */
    public function where(array $where): self{
        $where_list = array();
        foreach($where as $key => $value){
            if($value instanceof DBManagerOperator){
                $where_list[] = $value;
            }else{
                if($value === null){
                    $where_list[] = new DBManagerOperator($key, null, DBManagerOperator::OPERATOR_IS);
                }else{
                    $where_list[] = new DBManagerOperator($key, $value, DBManagerOperator::OPERATOR_EQUAL);
                }
            }
        }
        $this->setWhere($where_list);
        return $this;
    }

    /**
     * @param string|array $columns
     * @return self
     * @throws SQLException
     */
    public function select(string|array $columns = '*'): self{
        $query = $this->buildSelectQuery($columns);
        return $this->runStatement($query['query'], $query['bindings']);
    }

    /**
     * @return int
     * @throws SQLException
     *
     * Can be used to only return the counted rows.
     * If the results of the query is needed, please use getRowCount()
     */
    public function countRows(): int{
        $where = $this->buildWhere();
        $bindings = $where['bindings'];
        $where['question'] .= $this->buildSelectQuerySuffix();
        $query = 'SELECT COUNT(*) AS ' . $this->escapeColumnName('row_count') . ' FROM ' . $this->escapeColumnName($this->getTable()) . $where['question'];

        $this->runStatement($query, $bindings);
        $callback = $this->fetch();
        return intval($callback['row_count']);
    }

    /**
     * @param array $set
     * @return self
     * @throws SQLException
     */
    public function update(array $set): self{
        $query = $this->buildUpdateQuery($set);
        return $this->runStatement($query['query'], $query['bindings']);
    }

    /**
     * @param string[] $keys
     * @param array<string, mixed> $content
     * @return self
     * @throws SQLException
     */
    private function insertDetailed(array $keys, array $content): self{
        $query = $this->buildInsertQuery($keys, $content);
        return $this->runStatement($query['query'], $query['bindings']);
    }

    /**
     * @param array<string, mixed> $content
     * @return self
     * @throws SQLException
     */
    public function insert(array $content): self{
        return $this->insertDetailed(array_keys($content), $content);
    }

    /**
     * @return self
     * @throws SQLException
     */
    public function delete(): self{
        $query = $this->buildDeleteQuery();
        return $this->runStatement($query['query'], $query['bindings']);
    }

    /**
     * @param string $query
     * @param array<string, mixed> $bindings
     * @return self
     * @throws SQLException
     */
    public function execute(string $query, array $bindings = array()): self{
        return $this->runStatement($query, $bindings);
    }

    /**
     * @param string $query
     * @param array $bindings
     * @return $this
     * @throws SQLException
     */
    private function runStatement(string $query, array $bindings): self{
        $error = null;
        $error_code = null;
        try{
            if($this->getReturning() !== null){
                $query .= ' RETURNING ' . $this->escapeColumnName($this->getReturning());
            }
            $statement = $this->getDbconn()->prepare($query);
        }catch(PDOException $e){
            $error = $e->getMessage();
            $error_code = $e->getCode();
            $statement = false;
        }
        if($statement !== false){
            $this->setStatement($statement);
            try{
                for($i = 0; $i < sizeof($bindings); $i++){
                    $key = array_keys($bindings)[$i];
                    $value = array_values($bindings)[$i];
                    $type = PDO::PARAM_STR;
                    if($this->isSmartParser())
                        $bindings[$key] = (new DBSmartParser('__N/A__', $value))->parse()->getValue();
                    if(is_int($value)){
                        $type = PDO::PARAM_INT;
                    }else if(is_null($value)){
                        $type = PDO::PARAM_NULL;
                    }
                    $statement->bindParam($key, array_values($bindings)[$i], $type);
                }
                $this->setExecuteStatus($this->getStatement()->execute());
                if(!$this->getExecuteStatus()){
                    throw new SQLException($this->getErrorMessage(), code: $this->getErrorCode(), info: $this->getErrorInfo());
                }
                if($this->getReturning() !== null && $this->getStatement()->rowCount() > 0){
                    $callback = $this->getStatement()->fetch();
                    $this->setExecuteStatus($callback !== false);
                    if($callback)
                        $this->returning_value = $callback[$this->getReturning()];
                }
                return $this;
            }catch(PDOException $e){
                $query_ = $query;
                foreach($bindings as $key => $value){
                    $query_ = str_replace($key, $value, $query_);
                }
                $code = $e->getCode();
                if(!is_int($code))
                    $code = null;
                throw new SQLException('Error executing (' . $e->getMessage() . ')', $query_, intval($code));
            }
        }else{
            $query_ = $query;
            foreach($bindings as $key => $value){
                $query_ = str_replace($key, $key === 'password' ? '<**Protected Property**>' : $value, $query_);
            }
            throw new SQLException('Error preparing statement' . ($error !== null ? ' (' . $error . ')' : ''), $query_, $error_code);
        }
    }

    /**
     * @return bool
     * @throws SQLException
     */
    public function exist(): bool{
        if($this->getStatement() === null){
            $this->select();
        }
        return $this->getRowCount() >= 1;
    }

    /**
     * @return bool
     * @throws SQLException
     */
    public function tableExist(): bool{
        return $this->execute('SELECT COUNT(*) AS ' . $this->escapeColumnName('exist') . ' FROM ' . $this->escapeColumnName('INFORMATION_SCHEMA') . '.' . $this->escapeColumnName('TABLES') . ' WHERE ' . $this->escapeColumnName('TABLE_SCHEMA') . ' = \'' . $this->_getDbConfig()->getDbName() . '\' AND ' . $this->escapeColumnName('TABLE_NAME') . ' = \'' . $this->getTable() . '\'')->fetch()['exist'] == 1;
    }

    /**
     * @param DBColumn $column
     * @param string|DBColumn|null $after
     * @param bool $first - When $first is true, $after is ignored
     * @return bool
     * @throws SQLException
     */
    public function addColumn(DBColumn $column, string|DBColumn|null $after = null, bool $first = false): bool{
        $callback = $this->execute('ALTER TABLE ' . $this->escapeColumnName($this->getTable()) . ' ADD ' . $column->getColumnString() .
            ($first ? ' FIRST' : ($after !== null ? ' AFTER ' . $this->escapeColumnName(($after instanceof DBColumn ? $after->getName() : $after)) : ''))
        )->getStatus();
        if($callback){
            foreach($column->getColumnExtras() as $extra){
                $this->execute('ALTER TABLE ' . $this->escapeColumnName($this->getTable()) . ' ADD ' . $extra);
            }
        }
        return $callback;
    }

    /**
     * @param DBTable $table
     * @return bool
     * @throws SQLException
     */
    public function createTable(DBTable $table): bool{
        $query = 'CREATE TABLE ' . $this->escapeColumnName($table->getName()) . '(';
        $columns = array();
        $extras = array();
        foreach($table->getColumns() as $column){
            $columns[] = $column->getColumnString();
            $extras = array_merge($extras, $column->getColumnExtras());
        }
        foreach($extras as $extra){
            $columns[] = $extra;
        }
        $query .= join(',', $columns);
        $query .= ')';
        return $this->execute($query)->getStatus();
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     * @throws SQLException
     */
    public function fetchObject(string $className): object{
        if($this->getStatement() === null){
            $this->select();
        }
        if($this->getStatement()->rowCount() > 0){
            $data = $this->getStatement()->fetch(PDO::FETCH_ASSOC);
            if($data !== false){
                return new $className($data);
            } else {
                throw new SQLException('Error fetching object');
            }
        }else{
            throw new EntityNotFound('Error fetching object. No result found');
        }
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T[]
     * @throws SQLException
     */
    public function fetchObjects(string $className): array{
        if($this->getStatement() === null){
            $this->select();
        }
        $data = $this->getStatement()->fetchAll(PDO::FETCH_ASSOC);
        if($data !== false){
            $callback = array();
            foreach($data as $obj_data){
                $callback[] = new $className($obj_data);
            }
            return $callback;
        }else{
            throw new SQLException('Error fetching');
        }
    }

    /**
     * @return array - single row
     * @throws SQLException
     */
    public function fetch(): array{
        if($this->getStatement() === null){
            $this->select();
        }
        if($this->getStatement()->rowCount() > 0){
            $callback = $this->getStatement()->fetch(PDO::FETCH_ASSOC);
            if($callback !== false){
                return $callback;
            }else{
                throw new SQLException('Error fetching: ' . $this->getErrorMessage());
            }
        }else{
            throw new EntityNotFound('Error fetching object. No result found');
        }
    }

    /**
     * @return array - array of rows
     * @throws SQLException
     */
    public function fetchAll(): array{
        if($this->getStatement() === null){
            $this->select();
        }
        $callback = $this->getStatement()->fetchAll(PDO::FETCH_ASSOC);
        if($callback !== false){
            return $callback;
        }else{
            throw new SQLException('Error fetching');
        }
    }

    /**
     * @return bool
     */
    #[Pure]
    public function getStatus(): bool{
        return $this->getExecuteStatus();
    }

    /**
     * @return string
     */
    public function getErrorCode(): string{
        return $this->getStatement()->errorCode();
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string{
        return $this->getErrorInfo()[2];
    }

    /**
     * @return array
     */
    #[ArrayShape([
        0 => "string",
        1 => "int",
        2 => "string",
    ])]
    public function getErrorInfo(): array{
        return $this->getStatement()->errorInfo();
    }

    /**
     * @return int
     */
    public function getLastInsertId(): int{
        return intval($this->getDbconn()->lastInsertId());
    }

    /**
     * @param int|string $order_by
     * @return array
     * @throws SQLException
     *
     * This only works for chronologically tables which are sorted asc. If the primary column isn't the first, please use $order_by to specify!
     */
    public function getLastInsertRow(int|string $order_by = 1): array{
        $this->setStatement(null);
        return $this->setOrderBy($order_by)->setOrderDESC()->setLimit(1)->where(array())->fetch();
    }

    /**
     * @return int
     * @throws SQLException
     *
     * Can be used after executing a normal Query.
     * If only the rowCount is needed, use countRows() for better performance
     */
    public function getRowCount(): int{
        if($this->getStatement() === null){
            $this->select();
        }
        return $this->getStatement()->rowCount();
    }

}
