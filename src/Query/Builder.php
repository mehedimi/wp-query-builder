<?php

namespace Mehedi\WPQueryBuilder\Query;

class Builder
{
    protected $grammar;

    public $aggregate;

    public $distinct;

    public $from;

    public $columns = '*';

    public function __construct(Grammar $grammar)
    {
        $this->grammar = $grammar;
    }

    public function from($table, $as = null)
    {
        $this->from = $as ? sprintf('%s as %s', $table, $as) : $table;
        return $this;
    }

    public function select($columns)
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function distinct($column = true)
    {
        $this->distinct = $column;
        return $this;
    }

    public function aggregate($function, $column)
    {
        $this->aggregate = [$function, $column];

        $data = $this->get();

        if (empty($data)) {
            return 0;
        }

        return $data[0]['aggregate'];
    }

    public function sum($column)
    {
        $this->aggregate(__FUNCTION__, [$column]);
    }

    public function avg($column)
    {
        $this->aggregate(__FUNCTION__, [$column]);
    }

    public function min($column)
    {
        $this->aggregate(__FUNCTION__, [$column]);
    }

    public function max($column)
    {
        $this->aggregate(__FUNCTION__, [$column]);
    }

    public function get()
    {
        $statement = WPDB::prepare($this->toSQL());

        return WPDB::get_results($statement);
    }

    public function toSQL()
    {
        return $this->grammar->compileSelectComponents($this);
    }
}