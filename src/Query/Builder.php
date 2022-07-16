<?php

namespace Mehedi\WPQueryBuilder\Query;

class Builder
{
    /**
     * Query grammar instance
     *
     * @var Grammar
     */
    protected $grammar;

    /**
     * This contains aggregate column and function
     *
     * @var null|array
     */
    public $aggregate;

    /**
     * Indicate distinct query
     *
     * @var null|bool|string
     */
    public $distinct;

    /**
     * Query table name without prefix
     *
     * @var string
     */
    public $from;

    /**
     * Selected columns of a table
     *
     * @var string|array
     */
    public $columns = '*';

    /**
     * The maximum number of records to return.
     *
     * @var int
     */
    public $limit;

    /**
     * The number of records to skip.
     *
     * @var int
     */
    public $offset;

    /**
     * Create a new query builder instance.
     *
     * @param Grammar $grammar
     */
    public function __construct(Grammar $grammar)
    {
        $this->grammar = $grammar;
    }

    /**
     * Set query table name
     *
     * @param $table
     * @param $as
     * @return $this
     */
    public function from($table, $as = null)
    {
        $this->from = $as ? sprintf('%s as %s', $table, $as) : $table;
        return $this;
    }

    /**
     * Select table columns
     *
     * @param $columns
     * @return $this
     */
    public function select($columns)
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Use distinct query
     *
     * @param $column
     * @return $this
     */
    public function distinct($column = true)
    {
        $this->distinct = $column;
        return $this;
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param $function
     * @param $column
     * @return numeric
     */
    public function aggregate($function, $column)
    {
        $this->aggregate = [$function, $column];

        $data = $this->get();

        if (empty($data)) {
            return 0;
        }

        return $data[0]['aggregate'];
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param $column
     * @return numeric
     */
    public function sum($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param $column
     * @return numeric
     */
    public function avg($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param $column
     * @return numeric
     */
    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param $column
     * @return numeric
     */
    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function limit($value)
    {
        $this->limit = ! is_null($value) ? (int) $value : null;

        return $this;
    }

    public function offset($value)
    {
        $this->offset = ! is_null($value) ? (int) $value : null;

        return $this;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return mixed
     */
    public function get()
    {
        return WPDB::get_results(
            WPDB::prepare(
                $this->toSQL()
            )
        );
    }

    /**
     * Returns generated SQL query
     *
     * @return string
     */
    public function toSQL()
    {
        return $this->grammar->compileSelectComponents($this);
    }
}