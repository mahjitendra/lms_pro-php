<?php

/**
 * Database Connection and Query Builder
 * LMS Pro - Learning Management System
 */

class Database
{
    private $connection;
    private $config;
    private $queryLog = [];
    private $transactionLevel = 0;

    public function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect()
    {
        $connectionConfig = $this->config['connections'][$this->config['default']];
        
        try {
            $dsn = $this->buildDsn($connectionConfig);
            $this->connection = new PDO(
                $dsn,
                $connectionConfig['username'],
                $connectionConfig['password'],
                $connectionConfig['options']
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function buildDsn($config)
    {
        switch ($config['driver']) {
            case 'mysql':
                return "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            case 'pgsql':
                return "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            case 'sqlite':
                return "sqlite:{$config['database']}";
            default:
                throw new Exception("Unsupported database driver: {$config['driver']}");
        }
    }

    public function query($sql, $params = [])
    {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($params);
            
            $this->logQuery($sql, $params, microtime(true) - $startTime);
            
            return new DatabaseResult($stmt, $result);
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage() . " SQL: " . $sql);
        }
    }

    public function select($sql, $params = [])
    {
        $result = $this->query($sql, $params);
        return $result->fetchAll();
    }

    public function selectOne($sql, $params = [])
    {
        $result = $this->query($sql, $params);
        return $result->fetch();
    }

    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $result = $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $params = array_merge($data, $whereParams);
        $result = $this->query($sql, $params);
        
        return $result->rowCount();
    }

    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $result = $this->query($sql, $params);
        return $result->rowCount();
    }

    public function table($tableName)
    {
        return new QueryBuilder($this, $tableName);
    }

    public function beginTransaction()
    {
        if ($this->transactionLevel === 0) {
            $this->connection->beginTransaction();
        }
        $this->transactionLevel++;
        return $this;
    }

    public function commit()
    {
        if ($this->transactionLevel === 1) {
            $this->connection->commit();
        }
        $this->transactionLevel = max(0, $this->transactionLevel - 1);
        return $this;
    }

    public function rollback()
    {
        if ($this->transactionLevel === 1) {
            $this->connection->rollback();
        }
        $this->transactionLevel = max(0, $this->transactionLevel - 1);
        return $this;
    }

    public function transaction($callback)
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getQueryLog()
    {
        return $this->queryLog;
    }

    private function logQuery($sql, $params, $time)
    {
        $this->queryLog[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => $time,
            'timestamp' => microtime(true)
        ];
    }

    public function raw($expression)
    {
        return new RawExpression($expression);
    }

    public function escape($value)
    {
        return $this->connection->quote($value);
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function __destruct()
    {
        $this->connection = null;
    }
}

class DatabaseResult
{
    private $statement;
    private $result;

    public function __construct($statement, $result)
    {
        $this->statement = $statement;
        $this->result = $result;
    }

    public function fetch($fetchStyle = PDO::FETCH_ASSOC)
    {
        return $this->statement->fetch($fetchStyle);
    }

    public function fetchAll($fetchStyle = PDO::FETCH_ASSOC)
    {
        return $this->statement->fetchAll($fetchStyle);
    }

    public function fetchColumn($columnNumber = 0)
    {
        return $this->statement->fetchColumn($columnNumber);
    }

    public function rowCount()
    {
        return $this->statement->rowCount();
    }

    public function columnCount()
    {
        return $this->statement->columnCount();
    }
}

class QueryBuilder
{
    private $database;
    private $table;
    private $select = ['*'];
    private $joins = [];
    private $where = [];
    private $groupBy = [];
    private $having = [];
    private $orderBy = [];
    private $limit = null;
    private $offset = null;
    private $params = [];

    public function __construct($database, $table)
    {
        $this->database = $database;
        $this->table = $table;
    }

    public function select($columns = ['*'])
    {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function join($table, $first, $operator = null, $second = null, $type = 'INNER')
    {
        if ($operator === null) {
            // Assume $first is the complete join condition
            $condition = $first;
        } else {
            $condition = "{$first} {$operator} {$second}";
        }
        
        $this->joins[] = "{$type} JOIN {$table} ON {$condition}";
        return $this;
    }

    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    public function where($column, $operator = null, $value = null)
    {
        if ($operator === null) {
            // Assume $column is raw where condition
            $this->where[] = ['raw' => $column];
        } else if ($value === null) {
            // Assume $operator is the value and operator is '='
            $value = $operator;
            $operator = '=';
            $paramKey = $this->getParamKey($column);
            $this->where[] = ['condition' => "{$column} {$operator} :{$paramKey}"];
            $this->params[$paramKey] = $value;
        } else {
            $paramKey = $this->getParamKey($column);
            $this->where[] = ['condition' => "{$column} {$operator} :{$paramKey}"];
            $this->params[$paramKey] = $value;
        }
        return $this;
    }

    public function whereIn($column, $values)
    {
        $placeholders = [];
        foreach ($values as $i => $value) {
            $paramKey = $this->getParamKey($column . '_' . $i);
            $placeholders[] = ":{$paramKey}";
            $this->params[$paramKey] = $value;
        }
        $this->where[] = ['condition' => "{$column} IN (" . implode(', ', $placeholders) . ")"];
        return $this;
    }

    public function whereNull($column)
    {
        $this->where[] = ['condition' => "{$column} IS NULL"];
        return $this;
    }

    public function whereNotNull($column)
    {
        $this->where[] = ['condition' => "{$column} IS NOT NULL"];
        return $this;
    }

    public function whereBetween($column, $min, $max)
    {
        $minKey = $this->getParamKey($column . '_min');
        $maxKey = $this->getParamKey($column . '_max');
        $this->where[] = ['condition' => "{$column} BETWEEN :{$minKey} AND :{$maxKey}"];
        $this->params[$minKey] = $min;
        $this->params[$maxKey] = $max;
        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        if (empty($this->where)) {
            return $this->where($column, $operator, $value);
        }
        
        if ($operator === null) {
            $this->where[] = ['raw' => "OR {$column}", 'type' => 'or'];
        } else if ($value === null) {
            $value = $operator;
            $operator = '=';
            $paramKey = $this->getParamKey($column);
            $this->where[] = ['condition' => "OR {$column} {$operator} :{$paramKey}", 'type' => 'or'];
            $this->params[$paramKey] = $value;
        } else {
            $paramKey = $this->getParamKey($column);
            $this->where[] = ['condition' => "OR {$column} {$operator} :{$paramKey}", 'type' => 'or'];
            $this->params[$paramKey] = $value;
        }
        return $this;
    }

    public function groupBy($columns)
    {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function having($column, $operator, $value)
    {
        $paramKey = $this->getParamKey('having_' . $column);
        $this->having[] = "{$column} {$operator} :{$paramKey}";
        $this->params[$paramKey] = $value;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $this->limit = $limit;
        if ($offset !== null) {
            $this->offset = $offset;
        }
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function get()
    {
        $sql = $this->buildSelectSql();
        return $this->database->select($sql, $this->params);
    }

    public function first()
    {
        $sql = $this->buildSelectSql();
        return $this->database->selectOne($sql, $this->params);
    }

    public function count($column = '*')
    {
        $originalSelect = $this->select;
        $this->select = ["COUNT({$column}) as count"];
        $sql = $this->buildSelectSql();
        $this->select = $originalSelect;
        
        $result = $this->database->selectOne($sql, $this->params);
        return $result ? (int)$result['count'] : 0;
    }

    public function exists()
    {
        return $this->count() > 0;
    }

    public function insert($data)
    {
        return $this->database->insert($this->table, $data);
    }

    public function update($data)
    {
        $setParts = [];
        $updateParams = [];
        
        foreach ($data as $column => $value) {
            $paramKey = $this->getParamKey('update_' . $column);
            $setParts[] = "{$column} = :{$paramKey}";
            $updateParams[$paramKey] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }
        
        $allParams = array_merge($updateParams, $this->params);
        $result = $this->database->query($sql, $allParams);
        return $result->rowCount();
    }

    public function delete()
    {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }
        
        $result = $this->database->query($sql, $this->params);
        return $result->rowCount();
    }

    private function buildSelectSql()
    {
        $sql = "SELECT " . implode(', ', $this->select) . " FROM {$this->table}";
        
        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }
        
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }
        
        if (!empty($this->having)) {
            $sql .= " HAVING " . implode(' AND ', $this->having);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }
        
        return $sql;
    }

    private function buildWhereClause()
    {
        $conditions = [];
        foreach ($this->where as $where) {
            if (isset($where['raw'])) {
                $conditions[] = $where['raw'];
            } else {
                $conditions[] = $where['condition'];
            }
        }
        return implode(' AND ', $conditions);
    }

    private function getParamKey($base)
    {
        $key = preg_replace('/[^a-zA-Z0-9_]/', '_', $base);
        $counter = 1;
        $originalKey = $key;
        
        while (isset($this->params[$key])) {
            $key = $originalKey . '_' . $counter++;
        }
        
        return $key;
    }
}

class RawExpression
{
    private $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function __toString()
    {
        return $this->expression;
    }
}