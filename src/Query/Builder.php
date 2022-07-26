<?php

namespace Mehedi\WPQueryBuilder\Query;

use Closure;
use InvalidArgumentException;
use Mehedi\WPQueryBuilder\Contracts\Plugin;

class Builder
{
    /**
     * All the available clause operators.
     *
     * @var string[]
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>', '&~', 'is', 'is not',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];
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
     * The where constraints for the query.
     *
     * @var array
     */
    public $wheres = [];
    /**
     * The orderings for the query.
     *
     * @var array
     */
    public $orders;
    /**
     * The table joins for the query.
     *
     * @var array
     */
    public $joins;
    /**
     * The current query value bindings.
     *
     * @var array
     */
    public $bindings = [
        'where' => [],
    ];
    /**
     * The groupings for the query.
     *
     * @var array
     */
    public $groups;
    /**
     * Query grammar instance
     *
     * @var Grammar
     */
    protected $grammar;

    /**
     * Create a new query builder instance.
     *
     * @param Grammar|null $grammar
     */
    public function __construct(Grammar $grammar = null)
    {
        $this->grammar = $grammar ?: Grammar::getInstance();
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param $table
     * @return $this
     */
    public function from($table)
    {
        $this->from = $table;
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
     * Execute the query as a "select" statement.
     *
     * @return mixed
     */
    public function get()
    {
        $query = WPDB::prepare($this->toSQL(), ...$this->bindings['where']);

        return WPDB::get_results($query);
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
     * Alias to set the "offset" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = !is_null($value) ? (int)$value : null;

        return $this;
    }

    /**
     * Execute the query and get the first result.
     *
     * @return mixed
     */
    public function first()
    {
        $sql = $this->limit(1)->toSQL();

        $query = WPDB::prepare($sql, ...$this->bindings['where']);

        return WPDB::get_row($query, ...func_get_args());
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function limit($value)
    {
        $this->limit = !is_null($value) ? (int)$value : null;

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param Closure|string|array $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        list($value, $operator) = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Prepare the value and operator for a where clause.
     *
     * @param string $value
     * @param string $operator
     * @param bool $useDefault
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$value, $operator];
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * Prevents using Null values with invalid operators.
     *
     * @param string $operator
     * @param mixed $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        return is_null($value) && in_array($operator, $this->operators) &&
            !in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param $column
     * @param $operator
     * @param $value
     * @param $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'Basic';

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        list($value, $operator) = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        // If the value is "null", we will just assume the developer wants to add a
        // where null clause to the query. So, we will allow a short-cut here to
        // that method for convenience so the developer doesn't have to check.
        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator !== '=');
        }

        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');

        $this->addBinding($value);

        return $this;
    }

    /**
     * Add a "where null" clause to the query.
     *
     * @param string|array $columns
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';

        foreach ((array)$columns as $column) {
            $this->wheres[] = compact('type', 'column', 'boolean');
        }

        return $this;
    }

    /**
     * Add a binding to the query.
     *
     * @param mixed $value
     * @param string $type
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    protected function addBinding($value, $type = 'where')
    {
        if (!array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: $type.");
        }

        $this->bindings[$type][] = $value;

        return $this;
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        foreach ($values as $value) {
            $this->addBinding($value);
        }

        return $this;
    }

    /**
     * Add a "where not null" clause to the query.
     *
     * @param string|array $columns
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull($columns, $boolean = 'and')
    {
        return $this->whereNull($columns, $boolean, true);
    }

    /**
     * Add a where not between statement to the query.
     *
     * @param string $column
     * @param   $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotBetween($column, $values, $boolean = 'and')
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add a where between statement to the query.
     *
     * @param string $column
     * @param  $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereBetween($column, $values, $boolean = 'and', $not = false)
    {
        $type = 'Between';

        $values = array_values(array_slice($values, 0, 2));

        $this->wheres[] = compact('type', 'column', 'values', 'boolean', 'not');

        foreach ($values as $value) {
            $this->addBinding($value);
        }

        return $this;
    }

    /**
     * Insert new records into the database.
     *
     * @param array $values
     * @param $ignore
     * @return mixed
     */
    public function insert(array $values, $ignore = false)
    {
        if (!empty($values) && !is_array(reset($values))) {
            $values = [$values];
        }

        $payload = array_reduce($values, function ($values, $value) {
            return array_merge($values, array_values(array_filter($value)));
        }, []);

        return WPDB::query(
            WPDB::prepare($this->grammar->compileInsert($this, $values, $ignore), ...$payload)
        );
    }

    /**
     * Update records in the database.
     *
     * @param array $values
     * @return mixed
     */
    public function update(array $values)
    {
        $payload = array_merge(array_values(array_filter($values)), $this->bindings['where']);

        return WPDB::query(
            WPDB::prepare($this->grammar->compileUpdate($this, $values), ...$payload)
        );
    }

    /**
     * Delete records from the database.
     *
     * @return mixed
     */
    public function delete()
    {
        return WPDB::query(
            WPDB::prepare($this->grammar->compileDelete($this), ...$this->bindings['where'])
        );
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param $column
     * @param $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = compact('column', 'direction');

        return $this;
    }

    /**
     * Add a "where" clause comparing two columns to the query.
     *
     * @return $this
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        $type = 'Column';

        list($second, $operator) = $this->prepareValueAndOperator(
            $second, $operator, func_num_args() === 2
        );

        $this->wheres[] = compact('type', 'first', 'operator', 'second', 'boolean');

        return $this;
    }

    /**
     * Add a left join clause to the query
     *
     * @param $table
     * @param $first
     * @param $operator
     * @param $second
     * @return $this
     */
    public function leftJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a join clause to the query.
     *
     * @param $table
     * @param $first
     * @param $operator
     * @param $second
     * @param $type
     * @return $this
     */
    public function join($table, $first = null, $operator = null, $second = null, $type = 'inner')
    {
        $join = new Join($table, $type);

        if ($first instanceof Closure) {
            $first($join);
        } else {
            $join->on($first, $operator, $second);
        }

        $this->joins[] = $join;

        return $this;
    }

    /**
     * Add a right join clause to the query
     *
     * @param $table
     * @param $first
     * @param $operator
     * @param $second
     * @return $this
     */
    public function rightJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Get the current query value bindings in a flattened array.
     *
     * @return array
     */
    public function getBindings()
    {
        return array_reduce($this->bindings, function ($bindings, $binding) {
            return array_merge($bindings, array_values($binding));
        }, []);
    }

    /**
     * Apply a mixin to builder class
     *
     * @param Plugin $mixin
     * @return $this
     */
    public function plugin(Plugin $mixin)
    {
        $mixin->apply($this);

        return $this;
    }

    /**
     * Add a `group by` clause to query
     *
     * @param $column
     * @param $direction
     * @return Builder
     */
    public function groupBy($column, $direction = null)
    {
        $this->groups[] = [$column, $direction];

        return $this;
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param Closure $callback
     * @param string $boolean
     * @return $this
     */
    public function whereNested(Closure $callback, $boolean = 'and')
    {
        $callback($query = $this->newQuery());

        if (!empty($query->wheres)) {
            $type = 'Nested';

            $this->wheres[] = compact('type', 'query', 'boolean');

            foreach ($query->bindings['where'] as $binding) {
                $this->addBinding($binding);
            }
        }

        return $this;
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return Builder
     */
    public function newQuery()
    {
        return (new static($this->grammar));
    }
}