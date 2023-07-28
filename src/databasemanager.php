<?php
/**
 * @copyright (c) 2020, Marius Karstedt
 * @link https://marius-karstedt.de
 * All rights reserved.
 */

namespace utils;

use ArrayIterator;
use CachingIterator;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Pure;
use PDO;
use PDOException;
use PDOStatement;
use utils\databasemanager\DatabaseManagerOperator;
use utils\exception\SQLException;

#[Deprecated(
    reason: 'Use DBManager class'
)]
class databasemanager{

    /**
     * @var String $table
     */
    private string $table = 'null';
    /**
     * @var PDO $_dbconn
     */
    private PDO $_dbconn;
    /**
     * @var db_config $db_config
     */
    private db_config $db_config;
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
     * @var bool $id
     */
    private bool $id = true;
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

    const
        CALLBACK_ID = 1,
        CALLBACK_STATUS = 2;

    /**
     * databasemanager constructor.
     * @param db_config|null $db_config
     * @throws SQLException
     */
    public function __construct(?db_config $db_config = null){
        if($db_config == null)
            $this->db_config = db_config::$default;
        else
            $this->db_config = $db_config;
        try{
            $this->_dbconn = $this->db_config->getConnection();
        }catch(PDOException $e){
            throw new SQLException('Error connecting to Database (' . $e->getMessage() . ')');
        }
    }

    /**
     * @return self
     */
    public function init(): self{
        $this->table = 'null';
        $this->order_type = 'ASC';
        $this->order_key = 1;
        $this->ignorecase = false;
        $this->id = true;
        $this->limit = null;
        $this->offset = null;
        $this->statement = null;
        $this->where = null;
        $this->execute_status = null;
        return $this;
    }

    /**
     * @return PDO
     */
    public function getDbconn(): PDO{
        return $this->_dbconn;
    }

    /**
     * @return db_config
     */
    public function getDbConfig(): db_config{
        return $this->db_config;
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
     * @param bool $id
     * @return self
     */
    public function setId(bool $id): self{
        $this->id = $id;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasId(): bool{
        return $this->id;
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
            $currentOperator = DatabaseManagerOperator::OPERATOR_AND;
            for($i = 0; $i < sizeof($where); $i++){
                $value = $where[$i];
                if($value instanceof DatabaseManagerOperator){
                    $param = $value->getValue();
                    if(is_array($param)){
                        if(!Util::isAssocArray($param)){
                            if($value->getOperator() === DatabaseManagerOperator::OPERATOR_IN || $value->getOperator() === DatabaseManagerOperator::OPERATOR_NOT_IN){
                                if(sizeof($param) === 0){
                                    if($value->getOperator() === DatabaseManagerOperator::OPERATOR_IN){
                                        $where_string = 'FALSE';
                                    } else if($value->getOperator() === DatabaseManagerOperator::OPERATOR_NOT_IN){
                                        $where_string = 'TRUE';
                                    } else {
                                        //never called
                                        throw new SQLException('Operator "' . $value->getOperator() . '" not supported for arrays');
                                    }
                                } else {
                                    $sub_bindings = array();
                                    $j = 0;
                                    foreach($param as $sub_value){
                                        $sub_bindings[':w_' . $i . '_' . $j] = ($ignorecase && is_string($sub_value) ? strtolower($sub_value) : $sub_value);
                                        $j++;
                                    }
                                    $where_string = ($ignorecase ? 'LOWER(' : '') . '`' . $value->getKey() . '`' . ($ignorecase ? ')' : '') . ' ' . $value->getOperator() . ' (' . join(', ', array_keys($sub_bindings)) . ')';
                                    $bindings = array_merge($bindings, $sub_bindings);
                                }
                            } else {
                                throw new SQLException('Operator "' . $value->getOperator() . '" not supported for arrays');
                            }
                        } else {
                            throw new SQLException('Assoc arrays are not supported');
                        }
                    }else{
                        $where_string = ($ignorecase ? 'LOWER(' : '') . '`' . $value->getKey() . '`' . ($ignorecase ? ')' : '') . ' ' . $value->getOperator() . ' :w_' . $i;
                        $bindings[':w_' . $i] = ($ignorecase && is_string($param) ? strtolower($param) : $param);
                    }
                    if($i !== sizeof($where)-1){
                        if($value->getNextOperator() === DatabaseManagerOperator::OPERATOR_OR){
                            $question .= ($currentOperator === DatabaseManagerOperator::OPERATOR_OR ? '' : '(') . $where_string . ' ' . $value->getNextOperator() . ' ';
                        }else{
                            $question .= $where_string . ($currentOperator === DatabaseManagerOperator::OPERATOR_OR ? ')' : '') . ' ' . $value->getNextOperator() . ' ';
                        }
                    }else{
                        $question .= $where_string . ($currentOperator === DatabaseManagerOperator::OPERATOR_OR ? ')' : '');
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
        $setter = ' SET ';
        $i = 0;
        $set = new CachingIterator(new ArrayIterator($set));
        foreach ($set as $key => $value){
            if($value !== null){
                $setter .= '`' . $key . '` = :s_' . $i;
                $bindings[':s_' . $i] = $value;
                $i++;
            }else{
                $setter .= '`' . $key . '` = null';
            }
            if($set->hasNext()){
                $setter .= ', ';
            }
        }
        return array(
            'setter' => $setter,
            'bindings' => $bindings
        );
    }

    /**
     * @param array|string $columns
     * @return array
     */
    #[ArrayShape([
        'query' => "string",
        'bindings' => "array"
    ])]
    private function buildSelectQuery(string|array $columns = '*'): array{
        $where = $this->buildWhere();
        $bindings = $where['bindings'];
        if(is_array($columns)){
            $selector = join(', ', $columns);
        }else if(is_string($columns)){
            $selector = $columns;
        }else{
            $selector = '*';
        }
        $where['question'] .= ' ORDER BY ' . $this->getOrderKey() . ' ' . $this->getOrderType();
        if($this->hasLimit()){
            $where['question'] .= ' LIMIT ' . $this->getLimit();
            if($this->hasOffset())
                $where['question'] .= ' OFFSET ' . $this->getOffset();
        }
        $query = 'SELECT ' . $selector . ' FROM `' . $this->getTable() . '`' . $where['question'];
        return array(
            'query' => $query,
            'bindings' => $bindings
        );
    }

    /**
     * @param array $set
     * @return array
     */
    #[ArrayShape([
        'query' => "string",
        'bindings' => "array"
    ])]
    private function buildUpdateQuery(array $set): array{
        $where = $this->buildWhere();
        $set = $this->buildSet($set);
        $bindings = array_merge($where['bindings'], $set['bindings']);
        $query = 'UPDATE `' . $this->getTable() . '`' . $set['setter'] . $where['question'];
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
        $key_string = '';
        $value_string = '';
        if($this->hasId()){
            $key_string .= '`id`, ';
            $value_string .= '0, ';
        }
        $keys = new CachingIterator(new ArrayIterator($keys));
        $bindings = array();
        $i = 0;
        foreach ($keys as $key){
            if($key == 'id' && $this->hasId()) continue;
            $key_string .= '`' . $key . '`';
            if($content[$key] !== null){
                $value_string .= ':i_' . $i;
                $bindings[':i_' . $i] = $content[$key];
                $i++;
            }else{
                $value_string .= 'null';
            }
            if($keys->hasNext()){
                $key_string .= ', ';
                $value_string .= ', ';
            }
        }
        $query = 'INSERT INTO ' . $this->getTable() . ' (' . $key_string . ') VALUES (' . $value_string . ')';
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
        $query = 'DELETE FROM `' . $this->getTable() . '`' . $where['question'];
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
            if($value instanceof DatabaseManagerOperator){
                $where_list[] = $value;
            }else{
                if($value === null){
                    $where_list[] = new DatabaseManagerOperator($key, null, DatabaseManagerOperator::OPERATOR_IS);
                }else{
                    $where_list[] = new DatabaseManagerOperator($key, $value, DatabaseManagerOperator::OPERATOR_EQUAL);
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
    #[Deprecated(
        reason: 'use select() instead',
        replacement: '%class%->select(%parameter0%)'
    )]
    public function select_(string|array $columns = '*'): self{
        return $this->select($columns);
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
     * @param array $set
     * @return self
     * @throws SQLException
     */
    #[Deprecated(
        reason: 'use update() instead',
        replacement: '%class%->update(%parameter0%)'
    )]
    public function update_(array $set): self{
        return $this->update($set);
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
    #[Deprecated(
        reason: 'use insertDetailed() instead',
        replacement: '%class%->insertDetailed(%parameter0%, %parameter1%)'
    )]
    private function insertDetailed_(array $keys, array $content): self{
        return $this->insertDetailed($keys, $content);
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
    #[Deprecated(
        reason: 'use insert() instead',
        replacement: '%class%->insert(%parameter0%)'
    )]
    public function insert_(array $content): self{
        return $this->insert($content);
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
    #[Deprecated(
        reason: 'use delete() instead',
        replacement: '%class%->delete()'
    )]
    public function delete_(): self{
        return $this->delete();
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
     * @param array $bindings
     * @return self
     * @throws SQLException
     */
    #[Deprecated(
        reason: 'use execute() instead',
        replacement: '%class%->execute(%parameter0%, %parameter1%)'
    )]
    public function execute_(string $query, array $bindings = array()): self{
        return $this->execute($query, $bindings);
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
        try{
            $statement = $this->getDbconn()->prepare($query);
        }catch(PDOException $e){
            $error = $e->getMessage();
            $statement = false;
        }
        if($statement !== false){
            $this->setStatement($statement);
            try{
                $this->setExecuteStatus($this->getStatement()->execute($bindings));
                return $this;
            }catch(PDOException $e){
                $query_ = $query;
                foreach($bindings as $key => $value){
                    $query_ = str_replace($key, $value, $query_);
                }
                throw new SQLException('Error executing (' . $e->getMessage() . ')', $query_);
            }
        }else{
            $query_ = $query;
            foreach($bindings as $key => $value){
                $query_ = str_replace($key, $value, $query_);
            }
            throw new SQLException('Error preparing statement' . ($error !== null ? ' (' . $error . ')' : ''), $query_);
        }
    }

    /**
     * @return bool
     * @throws SQLException
     */
    #[Deprecated(
        reason: 'use exist() instead',
        replacement: '%class%->exist()'
    )]
    public function exist_(): bool{
        return $this->exist();
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
    #[Deprecated(
        reason: 'use tableExist() instead',
        replacement: '%class%->tableExist()'
    )]
    public function tableExist_(): bool{
        return $this->tableExist();
    }

    /**
     * @return bool
     * @throws SQLException
     */
    public function tableExist(): bool{
        return $this->execute('SELECT COUNT(*) AS `exist` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA` = \'' . $this->getDbConfig()->getDbName() . '\' AND `TABLE_NAME` = \'' . $this->getTable() . '\'')->fetch()['exist'] == 1;
    }

    /**
     * @deprecated
     * @param array<string, mixed> $content
     * @return self
     * @throws SQLException
     */
    public function updateWithId_(array $content): self{
        $id = $content['id'];
        unset($content['id']);
        return $this
            ->where(array('id' => $id))
            ->update($content);
    }

    /**
     * @param string $className
     * @return object
     * @throws SQLException
     */
    public function fetchObject(string $className): object{
        if($this->getStatement() === null){
            $this->select();
        }
        if($this->getStatement()->rowCount() >= 0){
            $callback = $this->getStatement()->fetchObject($className);
            if($callback !== false){
                return $callback;
            } else {
                throw new SQLException('Error fetching object');
            }
        }else{
            throw new SQLException('Error fetching object. No result found');
        }
    }

    /**
     * @param string $className
     * @return array
     * @throws SQLException
     */
    public function fetchObjects(string $className): array{
        if($this->getStatement() === null){
            $this->select();
        }
        $callback = $this->getStatement()->fetchAll(PDO::FETCH_CLASS, $className);
        if($callback !== false){
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
        $callback = $this->getStatement()->fetch();
        if($callback !== false){
            return $callback;
        }else{
            throw new SQLException('Error fetching');
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
        $callback = $this->getStatement()->fetchAll();
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
     * @return int
     * @throws SQLException
     */
    public function getRowCount(): int{
        if($this->getStatement() === null){
            $this->select();
        }
        return $this->getStatement()->rowCount();
    }

}
