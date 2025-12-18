<?php

namespace Mehedi\WPQueryBuilder\Query;

class Grammar
{
    /**
     * Store single instance of current class
     */
    protected static ?Grammar $instance = null;

    /**
     * The grammar table prefix.
     */
    protected string $tablePrefix = '';

    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected array $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'orders',
        'limit',
        'offset',
    ];

    /**
     * Get a single instance
     */
    public static function getInstance(): Grammar
    {
        if (is_null(static::$instance)) {
            static::$instance = new self;
        }

        return static::$instance;
    }

    /**
     * Compile a select query into SQL.
     */
    public function compileSelectComponents(Builder $builder): string
    {
        $sql = ['select'.($builder->distinct ? ' distinct' : '')];

        foreach ($this->selectComponents as $component) {
            if (isset($builder->{$component})) {
                $sql[$component] = call_user_func(
                    [$this, 'compile'.ucfirst($component)], // @phpstan-ignore-line
                    $builder,
                    $builder->{$component}
                );
            }
        }

        return implode(' ', array_filter($sql));
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param  array<string|int, mixed>  $values
     */
    public function compileInsert(Builder $builder, array $values, bool $ignore): string
    {
        $table = $this->tableWithPrefix($builder->from);

        if (empty($values)) {
            return "insert into $table default values";
        }

        $columns = $this->columnize(array_keys(reset($values))); // @phpstan-ignore-line

        $placeholderValues = implode(', ', array_map(function ($value) {
            return '('.implode(', ', array_map([$this, 'getValuePlaceholder'], $value)).')';
        }, $values));

        return ($ignore ? 'insert ignore' : 'insert')." into $table($columns) values $placeholderValues";
    }

    /**
     * Get table name with prefix
     */
    protected function tableWithPrefix(string $table): string
    {
        return $this->getTablePrefix().$table;
    }

    /**
     * Get the grammar's table prefix.
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Set the grammar's table prefix.
     *
     * @return $this
     */
    public function setTablePrefix(string $prefix): Grammar
    {
        $this->tablePrefix = $prefix;

        return $this;
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param  array<int, string>  $columns
     */
    public function columnize(array $columns): string
    {
        return implode(', ', $columns);
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param  array<string, mixed>  $values
     */
    public function compileUpdate(Builder $builder, array $values): string
    {
        $columns = $this->compileUpdateColumns($values);
        $where = $this->compileWheres($builder);

        return trim("update {$this->tableWithPrefix($builder->from)} set $columns $where");
    }

    /**
     * Compile the columns for an update statement.
     *
     * @param  array<string, mixed>  $values
     */
    protected function compileUpdateColumns(array $values): string
    {
        return implode(', ', array_map(function ($key) use (&$values) {
            return "$key = {$this->getValuePlaceholder($values[$key])}";
        }, array_keys($values)));
    }

    /**
     * Get value placeholder based on value data type
     *
     * @param  mixed|null  $value
     */
    protected function getValuePlaceholder($value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        return '?';
    }

    /**
     * Compile the "where" portions of the query.
     */
    public function compileWheres(Builder $builder): string
    {
        if (empty($builder->wheres)) {
            return '';
        }

        return $this->concatenateWhereClauses(
            $builder,
            $this->compileWheresToArray($builder)
        );
    }

    /**
     * Format the where clause statements into one string.
     *
     * @param  array<int, string>  $whereSegment
     */
    protected function concatenateWhereClauses(Builder $builder, array $whereSegment): string
    {
        return ($builder instanceof Join ? 'on' : 'where').' '.$this->removeLeadingBoolean(
            implode(' ', $whereSegment)
        );
    }

    /**
     * Remove the leading boolean from a statement.
     */
    protected function removeLeadingBoolean(string $value): string
    {
        return preg_replace('/and |or /i', '', $value, 1); // @phpstan-ignore-line
    }

    /**
     * Get an array of all the where clauses for the query.
     *
     * @return string[]
     */
    protected function compileWheresToArray(Builder $builder): array
    {
        return array_map(function ($where) use ($builder) {
            return $where['boolean'].' '.call_user_func([$this, 'where'.$where['type']], $builder, $where); // @phpstan-ignore-line
        }, $builder->wheres);
    }

    /**
     * Compile a delete statement into SQL.
     */
    public function compileDelete(Builder $builder): string
    {
        $table = $this->tableWithPrefix($builder->from);
        $where = $this->compileWheres($builder);

        return trim("delete from $table $where");
    }

    /**
     * Compile a truncate table statement into SQL.
     */
    public function compileTruncate(Builder $builder): string
    {
        return 'truncate table '.$this->tableWithPrefix($builder->from);
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param  array<int, string>  $aggregate
     */
    protected function compileAggregate(Builder $builder, array $aggregate): string
    {
        return sprintf('%s(%s) as aggregate', $aggregate[0], $aggregate[1]);
    }

    /**
     * Compile the "select *" portion of the query.
     *
     * @param  array<int, string>|string  $columns
     * @return bool|string|null
     */
    protected function compileColumns(Builder $builder, $columns)
    {
        if (isset($builder->aggregate) && $columns === '*') {
            return null;
        }

        if (is_string($builder->distinct)) {
            return $builder->distinct;
        }

        return $this->withPrefixColumns($columns);
    }

    /**
     * Wrap with a table prefix
     *
     * @param  array<int, string>|string  $columns
     */
    protected function withPrefixColumns($columns): string
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        $columns = array_map(function ($column) {
            if (strpos($column, '.') === false) {
                return $column;
            }

            return $this->tableWithPrefix($column);
        }, $columns);

        return $this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     */
    protected function compileFrom(Builder $builder, string $from): string
    {
        return 'from '.$this->tableWithPrefix($from);
    }

    /**
     * Compile the "limit" portions of the query.
     */
    protected function compileLimit(Builder $builder, int $limit): string
    {
        return 'limit '.$limit;
    }

    /**
     * Compile the "offset" portions of the query.
     */
    protected function compileOffset(Builder $builder, int $offset): string
    {
        return 'offset '.$offset;
    }

    /**
     * Compile basic where clause
     *
     * @param  array<string, mixed>  $where
     */
    protected function whereBasic(Builder $builder, array $where): string
    {
        return "{$this->withPrefixColumns($where['column'])} {$where['operator']} ".$this->getValuePlaceholder($where['value']);
    }

    /**
     * Compile a "where in" clause.
     *
     * @param  array<string, mixed>  $where
     */
    protected function whereIn(Builder $builder, array $where): string
    {
        if (! empty($where['values'])) {
            return $this->withPrefixColumns($where['column']).' in ('.implode(', ', array_map([$this, 'getValuePlaceholder'], $where['values'])).')';
        }

        return '0 = 1';
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  array<string, mixed>  $where
     */
    protected function whereNotIn(Builder $builder, array $where): string
    {
        if (! empty($where['values'])) {
            return $this->withPrefixColumns($where['column']).' not in ('.implode(', ', array_map([$this, 'getValuePlaceholder'], $where['values'])).')';
        }

        return '1 = 1';
    }

    /**
     * Compile a "where is null" clause.
     *
     * @param  array<string, mixed>  $where
     */
    protected function whereNull(Builder $builder, array $where): string
    {
        return $this->withPrefixColumns($where['column']).' is null';
    }

    /**
     * Compile a "where is not null" clause.
     *
     * @param  array<string, mixed>  $where
     */
    protected function whereNotNull(Builder $builder, array $where): string
    {
        return $this->withPrefixColumns($where['column']).' is not null';
    }

    /**
     * Compile a "between" where clause.
     *
     * @param  array<string, mixed>  $where
     */
    protected function whereBetween(Builder $builder, array $where): string
    {
        $between = $where['not'] ? 'not between' : 'between';

        return $this->withPrefixColumns($where['column']).' '.$between.' '.implode(' and ', array_map([$this, 'getValuePlaceholder'], $where['values']));
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param  array<int, array<string, mixed>>  $orders
     */
    protected function compileOrders(Builder $builder, array $orders): string
    {
        return 'order by '.implode(', ', array_map(function ($order) {
            return "{$order['column']} {$order['direction']}";
        }, $orders));
    }

    /**
     * Compile a where clause comparing two columns.
     *
     * @param  array<string, mixed>  $where
     */
    protected function whereColumn(Builder $builder, array $where): string
    {
        return "{$this->withPrefixColumns($where['first'])} {$where['operator']} {$this->withPrefixColumns($where['second'])}";
    }

    /**
     * Compile the "join" portions of the query.
     *
     * @param  array<int, Join>  $joins
     */
    protected function compileJoins(Builder $builder, array $joins): string
    {
        return implode(' ', array_map(function (Join $join) use ($builder) {
            $nestedJoins = is_null($join->joins) ? '' : ' '.$this->compileJoins($builder, $join->joins);

            $tableAndNestedJoins = is_null($join->joins) ? $this->tableWithPrefix($join->table) : '('.$this->tableWithPrefix($join->table).$nestedJoins.')';

            return "$join->type join $tableAndNestedJoins {$this->compileWheres($join)}";
        }, $joins));
    }

    /**
     * Compile the "group by" portions of the query.
     *
     * @param  array<int, array<int, string>>  $groups
     */
    protected function compileGroups(Builder $builder, array $groups): string
    {
        $groups = array_map(function ($group) {
            return implode(' ', array_filter($group));
        }, $groups);

        return 'group by '.$this->columnize($groups);
    }

    /**
     * Compile a nested where clause.
     *
     * @param  array<string, mixed>  $where
     */
    protected function whereNested(Builder $builder, array $where): string
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query, we need to take the leading "where" of queries.
        $offset = $builder instanceof Join ? 3 : 6;

        return '('.substr($this->compileWheres($where['query']), $offset).')';
    }
}
