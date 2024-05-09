<?php

namespace Mehedi\WPQueryBuilder\Query;

use Closure;
use InvalidArgumentException;
use Mehedi\WPQueryBuilder\Connection;
use Mehedi\WPQueryBuilder\Contracts\Pluggable;
use Mehedi\WPQueryBuilder\Relations\Relation;
use Mehedi\WPQueryBuilder\Relations\WithMany;
use Mehedi\WPQueryBuilder\Relations\WithOne;

/**
 * @phpstan-consistent-constructor
 */
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
     * @var null|array<int, string>
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
     * @var string|array<int, string>
     */
    public $columns = '*';

    /**
     * The maximum number of records to return.
     *
     * @var int | null
     */
    public $limit;

    /**
     * The number of records to skip.
     *
     * @var int | null
     */
    public $offset;

    /**
     * The where constraints for the query.
     *
     * @var array<int, mixed>
     */
    public $wheres = [];

    /**
     * The orderings for the query.
     *
     * @var array<int, mixed>
     */
    public $orders;

    /**
     * The table joins for the query.
     *
     * @var array<int, mixed>|null
     */
    public $joins;

    /**
     * The current query value bindings.
     *
     * @var array<string, mixed>
     */
    public $bindings = [
        'join' => [],
        'where' => [],
    ];

    /**
     * The groupings for the query.
     *
     * @var array<int, mixed>|null
     */
    public $groups;

    /**
     * With queries
     *
     * @var array<int, mixed>|null
     */
    public $with;

    /**
     * Query grammar instance
     *
     * @var Grammar
     */
    public $grammar;

    /**
     * Connection instance
     *
     * @var Connection
     */
    public $connection;

    /**
     * Create a new query builder instance.
     */
    public function __construct(Connection $connection, Grammar $grammar = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?: Grammar::getInstance();
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param  string  $table
     * @return $this
     */
    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    /**
     * Use distinct query
     *
     * @param  bool  $column
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
     * @param  string  $column
     * @return float|int
     */
    public function sum($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param  string  $function
     * @param  string  $column
     * @return float|int
     */
    public function aggregate($function, $column)
    {
        $this->aggregate = [$function, $column];

        $data = $this->get();

        if (empty($data) || ! isset($data[0]->aggregate)) {
            return 0;
        }

        if (is_string($data[0]->aggregate)) {
            return strpos($data[0]->aggregate, '.') ? floatval($data[0]->aggregate) : intval($data[0]->aggregate);
        }

        return $data[0]->aggregate;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return array<int, object>
     */
    public function get()
    {
        $bindings = $this->getBindings();

        $results = $this->connection->select($this->toSQL(), $bindings);

        if (! empty($this->with)) {
            foreach ($this->with as $relation) {
                /** @var Relation $relation */
                $results = $relation->setItems($results)->load();
            }
        }

        return $results;
    }

    /**
     * Get the current query value bindings in a flattened array.
     *
     * @return array<int, mixed>
     */
    public function getBindings()
    {
        return array_reduce($this->bindings, function ($bindings, $binding) {
            return array_merge($bindings, array_values($binding));
        }, []);
    }

    /**
     * Select table columns
     *
     * @param  string|array<int, string>  $columns
     * @return $this
     */
    public function select($columns)
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
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
     * Retrieve the "count" result of the query.
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        return (int) $this->aggregate(__FUNCTION__, $columns);
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param  string  $column
     * @return float|int
     */
    public function avg($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param  string  $column
     * @return float|int
     */
    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param  string  $column
     * @return float|int
     */
    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param  int|null  $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = ! is_null($value) ? (int) $value : null;

        return $this;
    }

    /**
     * Execute the query and get the first result.
     *
     * @return object|null
     */
    public function first()
    {
        $this->limit(1);

        $items = $this->get();

        return reset($items) ?: null;
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int|null  $value
     * @return $this
     */
    public function limit($value)
    {
        $this->limit = ! is_null($value) ? (int) $value : null;

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Prepare the value and operator for a where clause.
     *
     * @param  string|numeric  $value
     * @param  string  $operator
     * @param  bool  $useDefault
     * @return array<int, mixed>
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
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        return is_null($value) && in_array($operator, $this->operators) &&
            ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string  $column
     * @param  string|null  $operator
     * @param  string|null|float|int  $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'Basic';

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, // @phpstan-ignore-line
            $operator, // @phpstan-ignore-line
            func_num_args() === 2
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
     * @param  string|array<int, string>  $columns
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';

        foreach ((array) $columns as $column) {
            $this->wheres[] = compact('type', 'column', 'boolean');
        }

        return $this;
    }

    /**
     * Add a binding to the query.
     *
     * @param  mixed  $value
     * @param  string  $type
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    protected function addBinding($value, $type = 'where')
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: $type.");
        }
        if (is_array($value)) {
            $this->bindings[$type] = array_merge($this->bindings[$type], $value);
        } else {
            $this->bindings[$type][] = $value;
        }

        return $this;
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @return $this
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
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
     * @param  string|array<int, string>  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function whereNotNull($columns, $boolean = 'and')
    {
        return $this->whereNull($columns, $boolean, true);
    }

    /**
     * Add a where not between statement to the query.
     *
     * @param  string  $column
     * @param  array<int|string, string>  $values
     * @param  string  $boolean
     * @return $this
     */
    public function whereNotBetween($column, array $values, $boolean = 'and')
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  string  $column
     * @param  array<string|int, int|float|string>  $values
     * @param  string  $boolean
     * @param  bool  $not
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
     * @param  array<int|string, mixed>  $values
     * @param  bool  $ignore
     * @return bool|int
     */
    public function insert(array $values, $ignore = false)
    {
        if (! empty($values) && ! is_array(reset($values))) {
            $values = [$values];
        }

        $payload = array_reduce($values, function ($values, $value) {
            return array_merge($values, array_values(array_filter($value)));
        }, []);

        $query = $this->grammar->compileInsert($this, $values, $ignore);

        if ($ignore) {
            return $this
                ->connection
                ->affectingStatement($query, $payload);
        }

        return $this
            ->connection
            ->insert($query, $payload);
    }

    /**
     * Update records in the database.
     *
     * @param  array<string, mixed>  $values
     * @return int
     */
    public function update(array $values)
    {
        $bindings = array_merge(array_values($values), $this->getBindings());

        return $this->connection->affectingStatement(
            $this->grammar->compileUpdate($this, $values),
            $bindings
        );
    }

    /**
     * Delete records from the database.
     *
     * @return int
     */
    public function delete()
    {
        return $this->connection->affectingStatement(
            $this->grammar->compileDelete($this),
            $this->getBindings()
        );
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string  $column
     * @param  string  $direction
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
     * @param  string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $boolean
     * @return $this
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        $type = 'Column';

        [$second, $operator] = $this->prepareValueAndOperator(
            $second, // @phpstan-ignore-line
            $operator, // @phpstan-ignore-line
            func_num_args() === 2
        );

        $this->wheres[] = compact('type', 'first', 'operator', 'second', 'boolean');

        return $this;
    }

    /**
     * Add a left join clause to the query
     *
     * @param  string  $table
     * @param  string|null  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return $this
     */
    public function leftJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a join clause to the query.
     *
     * @param  string  $table
     * @param  string|null|Closure  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @return $this
     */
    public function join($table, $first = null, $operator = null, $second = null, $type = 'inner')
    {
        $join = new Join($table, $type, $this->connection);

        if ($first instanceof Closure) {
            $first($join);
        } elseif (! is_null($first)) {
            $join->on($first, $operator, $second);
        }

        $this->addBinding($join->getBindings(), 'join');

        $this->joins[] = $join;

        return $this;
    }

    /**
     * Add a right join clause to the query
     *
     * @param  string  $table
     * @param  string|null|Closure  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return $this
     */
    public function rightJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Apply a mixin to builder class
     *
     * @return $this
     */
    public function plugin(Pluggable $pluggable)
    {
        $pluggable->apply($this);

        return $this;
    }

    /**
     * Add a `group by` clause to query
     *
     * @param  string  $column
     * @param  string|null  $direction
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
     * @param  string  $boolean
     * @return $this
     */
    public function whereNested(Closure $callback, $boolean = 'and')
    {
        $callback($query = $this->newQuery());

        if (! empty($query->wheres)) {
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
        return new static($this->connection, $this->grammar);
    }

    /**
     * Run a truncate statement on the table.
     *
     * @return bool
     */
    public function truncate()
    {
        return $this->connection->statement(
            $this->grammar->compileTruncate($this)
        );
    }

    /**
     * Add `withOne` relation
     *
     * @param  string  $name
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return Builder
     */
    public function withOne($name, callable $callback, $foreignKey, $localKey = 'ID')
    {
        call_user_func($callback, $relation = new WithOne($name, $foreignKey, $localKey, $this->newQuery()));

        $this->with[] = $relation;

        return $this;
    }

    /**
     * Add `withMany` relation
     *
     * @param  string  $name
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return $this
     */
    public function withMany($name, callable $callback, $foreignKey, $localKey = 'ID')
    {
        call_user_func($callback, $relation = new WithMany($name, $foreignKey, $localKey, $this->newQuery()));

        $this->with[] = $relation;

        return $this;
    }

    /**
     * Add relation to query
     *
     * @return $this
     */
    public function withRelation(Relation $relation, ?callable $callback = null)
    {
        if (! is_null($callback)) {
            call_user_func($callback, $relation);
        }

        $this->with[] = $relation;

        return $this;
    }
}
