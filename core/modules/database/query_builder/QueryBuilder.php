<?php

namespace Simp\Core\modules\database\query_builder;

use Exception;
use Simp\Core\modules\database\Database;

class QueryBuilder
{
    protected array $query_components;
    protected array $binding_params;
    protected bool $cache_output;
    protected array $selected_fields;
    protected array $conditions;
    protected array $other_components;

    public function __construct() {
        $this->query_components = [];
        $this->binding_params = [];
        $this->cache_output = false;
        $this->selected_fields = [];
        $this->conditions = [];
        $this->other_components = [];
    }

    /**
     * @throws Exception
     */
    protected function actionException(int $type = 0)
    {
        if ($type === 1) {
            throw new Exception("Join statement can not happen you need initial table first");
        }
        elseif ($type === 2) {
            throw new Exception("Add resulting fields can not happen you need initial table first");
        }
        elseif ($type === 3) {
            throw new Exception("Add condition fields can not happen you need initial table first");
        }
        else {
            throw new Exception("This method has already been called or similar action function already called");
        }
    }

    /**
     * @throws Exception
     */
    public function select(string $table, ?string $alias = null): QueryBuilder
    {
        if (!empty($this->query_components)) {
            $this->actionException();
        }

        if ($alias) {
            $table = "`$table`" . ' AS ' . $alias;
        }
        $this->query_components[] = "SELECT {fields} FROM $table";
        return $this;
    }

    /**
     * @throws Exception
     */
    public function insert(string $table): QueryBuilder
    {
        if (!empty($this->query_components)) {
            $this->actionException();
        }
        $this->query_components[] = "INSERT INTO `{$table}` ";
        return $this;
    }

    /**
     * @throws Exception
     */
    public function update(string $table): QueryBuilder
    {
        if (!empty($this->query_components)) {
            $this->actionException();
        }
        $this->query_components[] = "UPDATE `{$table}` SET ";
        return $this;
    }

    /**
     * @throws Exception
     */
    public function delete(string $table): QueryBuilder
    {
        if (!empty($this->query_components)) {
            $this->actionException();
        }
        $this->query_components[] = "DELETE FROM {$table} ";
        return $this;
    }

    /**
     * @throws Exception
     */
    public function leftJoin(string $table, string $on, ?string $alias = null): QueryBuilder
    {
        if (empty($this->query_components)) {
            $this->actionException(1);
        }
        if ($alias) {
            $table = "`$table`" . ' AS ' . $alias;
        }
        $this->query_components[] = "LEFT JOIN {$table} ON {$on}";
        return $this;
    }

    /**
     * @throws Exception
     */
    public function rightJoin(string $table, string $on, ?string $alias = null): QueryBuilder
    {
        if (empty($this->query_components)) {
            $this->actionException(1);
        }
        if ($alias) {
            $table = "`$table`" . ' AS ' . $alias;
        }
        $this->query_components[] = "RIGHT JOIN {$table} ON {$on}";
        return $this;
    }

    /**
     * @throws Exception
     */
    public function outerJoin(string $table, string $on, ?string $alias = null): QueryBuilder
    {
        if (empty($this->query_components)) {
            $this->actionException(1);
        }
        if ($alias) {
            $table = "`$table`" . ' AS ' . $alias;
        }
        $this->query_components[] = "OUTER JOIN {$table} ON {$on}";
        return $this;
    }

    /**
     * @throws Exception
     */
    public function addField(string $field_name, ?string $alias = null, ?string $table_alias = null): QueryBuilder
    {
        if (empty($this->query_components)) {
            $this->actionException(2);
        }
        if ($table_alias) {
            $field_name = "{$table_alias}.{$field_name}";
        }
        if ($alias) {
            $field_name = "{$field_name} AS {$alias}";
        }
        $this->selected_fields[] = $field_name;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function addCondition(string $field_name, mixed $value, string $operator = '=', string $conjunction = "AND", ?string $table_alias = null): QueryBuilder
    {
        $placeholder = ":{$field_name}";
        if (empty($this->query_components)) {
            $this->actionException(3);
        }
        if ($table_alias) {
            $field_name = "{$table_alias}.{$field_name}";
        }

        $condition_line = "{$field_name} {$operator} {$placeholder} $conjunction";
        $this->conditions[] = $condition_line;
        $this->binding_params["{$placeholder}"] = $value;
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): QueryBuilder
    {
        $limit_line = "LIMIT {$limit}";
        if ($offset) {
            $limit_line =  "LIMIT {$limit} OFFSET {$offset}";
        }
        $this->other_components[] = $limit_line;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function orderBy(string $field_name, ?string $table_alias = null): QueryBuilder
    {
        if ($table_alias) {
            $field_name = "{$table_alias}.{$field_name}";
        }
        $this->other_components[] = "ORDER BY {$field_name}";
        return $this;
    }

    public function groupBy(string $field_name, ?string $table_alias = null): QueryBuilder
    {
        if ($table_alias) {
            $field_name = "{$table_alias}.{$field_name}";
        }
        $this->other_components[] = "GROUP BY {$field_name}";
        return $this;
    }

    public function statement()
    {
        $query_line = implode(" ", $this->query_components);
        if (str_starts_with($query_line, 'SELECT')) {
            $field = implode(", ", $this->selected_fields);
            $query_line = str_replace("{fields}", $field, $query_line);
            $query_line .= " WHERE ".implode(" AND ", $this->conditions);
            if (str_ends_with($query_line, 'AND')) {
                $query_line = substr($query_line, 0, -3);
            }
            elseif (str_ends_with($query_line, 'OR')) {
                $query_line = substr($query_line, 0, -2);
            }
            $query_line .= " ".implode(" ", $this->other_components);
            $statement = Database::database()->con()->prepare($query_line);

            if (!empty($this->binding_params)) {
                foreach ($this->binding_params as $key => $value) {
                    $statement->bindParam($key, $value);
                }
            }
            return $statement;
        }
        else {

        }
        dump($query_line);
    }

}