<?php

namespace Mehedi\WPQueryBuilder\Query;

use Mehedi\WPQueryBuilder\Concerns\Singleton;

class Grammar
{
    use Singleton;

    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'orders',
        'limit',
        'offset',
    ];

    /**
     * Booted Grammar Class
     *
     * @return void
     */
    public function boot()
    {
        global $wpdb;

        WPDB::set($wpdb);
    }

    /**
     * Compile a select query into SQL.
     *
     * @param Builder $builder
     * @return string
     */
    public function compileSelectComponents(Builder $builder)
    {
        $sql = ['select' . ($builder->distinct ? ' distinct' : '')];

        foreach ($this->selectComponents as $component) {
            if (isset($builder->{$component})) {
                $sql[$component] = call_user_func(
                    [$this, 'compile' . ucfirst($component)],
                    $builder,
                    $builder->{$component}
                );
            }
        }

        return implode(' ', array_filter($sql));
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param array $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return implode(', ', $columns);
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return string
     */
    protected function compileAggregate(Builder $builder, $aggregate)
    {
        return sprintf('%s(%s) as aggregate', $aggregate[0], $aggregate[1]);
    }

    /**
     * Compile the "select *" portion of the query.
     *
     * @param Builder $builder
     * @param $columns
     *
     * @return bool|mixed|string|null
     */
    protected function compileColumns(Builder $builder, $columns)
    {
        if (isset($builder->aggregate) && $columns === '*') {
            return null;
        }

        if (is_string($builder->distinct)) {
            return $builder->distinct;
        }

        if (!is_array($columns)) {
            return $columns;
        }

        return $this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     *
     * @param Builder $builder
     * @param $from
     * @return string
     */
    protected function compileFrom(Builder $builder, $from)
    {
        return 'from ' . $this->tableWithPrefix($from);
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param Builder $builder
     * @param $limit
     * @return string
     */
    protected function compileLimit(Builder $builder, $limit)
    {
        return 'limit ' . $limit;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param Builder $builder
     * @param $offset
     * @return string
     */
    protected function compileOffset(Builder $builder, $offset)
    {
        return 'offset ' . $offset;
    }

    /**
     * Compile the "where" portions of the query.
     *
     * @param Builder $builder
     * @return string
     */
    public function compileWheres(Builder $builder)
    {
        if (empty($builder->wheres)) {
            return null;
        }

        return $this->concatenateWhereClauses(
            $builder,
            $this->compileWheresToArray($builder)
        );
    }

    /**
     * Get an array of all the where clauses for the query.
     *
     * @param Builder $builder
     * @return string[]
     */
    protected function compileWheresToArray(Builder $builder)
    {
        return array_map(function ($where) use ($builder) {
            return $where['boolean'] . ' ' . call_user_func([$this, 'where' . $where['type']], $builder, $where);
        }, $builder->wheres);
    }

    /**
     * Format the where clause statements into one string.
     *
     * @param Builder $builder
     * @param array $whereSegment
     * @return string
     */
    protected function concatenateWhereClauses($builder, $whereSegment)
    {
        return ($builder instanceof Join ? 'on' : 'where') . ' ' . $this->removeLeadingBoolean(
                implode(' ', $whereSegment)
            );
    }

    /**
     * Remove the leading boolean from a statement.
     *
     * @param string $value
     * @return string
     */
    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * Compile basic where clause
     *
     * @param Builder $builder
     * @param $where
     * @return string
     */
    protected function whereBasic(Builder $builder, $where)
    {
        return "{$where['column']} {$where['operator']} " . $this->getValuePlaceholder($where['value']);
    }

    /**
     * Get value placeholder based on value data type
     *
     * @param $value
     * @return string
     */
    protected function getValuePlaceholder($value)
    {
        switch (gettype($value)) {
            case 'double':
                return '%f';
            case 'integer':
                return '%d';
            case 'NULL':
                return 'null';
            default:
                return '%s';
        }
    }

    /**
     * Compile a "where in" clause.
     *
     * @param Builder $builder
     * @param $where
     * @return string
     */
    protected function whereIn(Builder $builder, $where)
    {
        if (!empty($where['values'])) {
            return $where['column'] . ' in (' . implode(', ', array_map([$this, 'getValuePlaceholder'], $where['values'])) . ')';
        }

        return '0 = 1';
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param Builder $builder
     * @param $where
     * @return string
     */
    protected function whereNotIn(Builder $builder, $where)
    {
        if (!empty($where['values'])) {
            return $where['column'] . ' not in (' . implode(', ', array_map([$this, 'getValuePlaceholder'], $where['values'])) . ')';
        }

        return '1 = 1';
    }

    /**
     * Compile a "where is null" clause.
     *
     * @param Builder $builder
     * @param $where
     * @return string
     */
    protected function whereNull(Builder $builder, $where)
    {
        return $where['column'] . ' is null';
    }

    /**
     * Compile a "where is not null" clause.
     *
     * @param Builder $builder
     * @param $where
     * @return string
     */
    protected function whereNotNull(Builder $builder, $where)
    {
        return $where['column'] . ' is not null';
    }

    /**
     * Compile a "between" where clause.
     *
     * @param Builder $builder
     * @param array $where
     * @return string
     */
    protected function whereBetween(Builder $builder, $where)
    {
        $between = $where['not'] ? 'not between' : 'between';

        return $where['column'] . ' ' . $between . ' ' . implode(' and ', array_map([$this, 'getValuePlaceholder'], $where['values']));
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param Builder $builder
     * @param array $values
     * @param $ignore
     * @return string
     */
    public function compileInsert(Builder $builder, array $values, $ignore)
    {
        $table = $this->tableWithPrefix($builder->from);

        if (empty($values)) {
            return "insert into $table default values";
        }

        $columns = $this->columnize(array_keys(reset($values)));

        $placeholderValues = implode(', ', array_map(function ($value) {
            return '(' . implode(', ', array_map([$this, 'getValuePlaceholder'], $value)) . ')';
        }, $values));

        return "insert into $table($columns) values $placeholderValues";
    }

    /**
     * Get table name with prefix
     *
     * @param $table
     * @return string
     */
    protected function tableWithPrefix($table)
    {
        return WPDB::prefix() . $table;
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param Builder $builder
     * @param array $values
     * @return string
     */
    public function compileUpdate(Builder $builder, array $values)
    {
        $columns = $this->compileUpdateColumns($values);
        $where = $this->compileWheres($builder);

        return trim("update {$this->tableWithPrefix($builder->from)} set $columns $where");
    }

    /**
     * Compile the columns for an update statement.
     *
     * @param $values
     * @return string
     */
    protected function compileUpdateColumns($values)
    {
        return implode(', ', array_map(function ($key) use (&$values) {
            return "$key = {$this->getValuePlaceholder($values[$key])}";
        }, array_keys($values)));
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param Builder $builder
     * @return string
     */
    public function compileDelete(Builder $builder)
    {
        $table = $this->tableWithPrefix($builder->from);
        $where = $this->compileWheres($builder);

        return trim("delete from $table $where");
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param Builder $builder
     * @param $orders
     * @return string
     */
    protected function compileOrders(Builder $builder, $orders)
    {
        return 'order by ' . implode(', ', array_map(function ($order) {
                return "{$order['column']} {$order['direction']}";
            }, $orders));
    }

    /**
     * Compile a where clause comparing two columns.
     *
     * @return string
     */
    protected function whereColumn(Builder $builder, $where)
    {
        return "{$where['first']} {$where['operator']} {$where['second']}";
    }

    /**
     * Compile the "join" portions of the query.
     *
     * @param Builder $builder
     * @param $joins
     * @return string
     */
    protected function compileJoins(Builder $builder, $joins)
    {
        return implode(' ', array_map(function (Join $join) use ($builder) {
            $nestedJoins = is_null($join->joins) ? '' : ' ' . $this->compileJoins($builder, $join->joins);

            $tableAndNestedJoins = is_null($join->joins) ? $join->table : '(' . $join->table . $nestedJoins . ')';

            return "$join->type join $tableAndNestedJoins {$this->compileWheres($join)}";
        }, $joins));
    }
}