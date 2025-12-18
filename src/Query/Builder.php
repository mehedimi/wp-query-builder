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
    public array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>', '&~', 'is', 'is not',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * This contains an aggregate column and function
     *
     * @var null|array<int, string>
     */
    public ?array $aggregate = null;

    /**
     * Indicate distinct query
     *
     * @var null|bool|string
     */
    public $distinct;

    /**
     * Query table name without a prefix
     */
    public string $from;

    /**
     * Selected columns of a table
     *
     * @var string|array<int, string>
     */
    public $columns = '*';

    /**
     * The maximum number of records to return.
     */
    public ?int $limit = null;

    /**
     * The number of records to skip.
     */
    public ?int $offset = null;

    /**
     * The where constraints for the query.
     *
     * @var array<int, mixed>
     */
    public array $wheres = [];

    /**
     * The orderings for the query.
     *
     * @var array<int, mixed>
     */
    public array $orders;

    /**
     * The table joins for the query.
     *
     * @var array<int, mixed>|null
     */
    public ?array $joins = null;

    /**
     * The current query value bindings.
     *
     * @var array<string, mixed>
     */
    public array $bindings = [
        'join' => [],
        'where' => [],
    ];

    /**
     * The groupings for the query.
     *
     * @var array<int, mixed>|null
     */
    public ?array $groups = null;

    /**
     * With queries
     *
     * @var array<int, mixed>|null
     */
    public ?array $with = null;

    /**
     * Query grammar instance
     */
    public Grammar $grammar;

    /**
     * Connection instance
     */
    public Connection $connection;

    /**
     * Create a new query builder instance.
     */
    public function __construct(Connection $connection, ?Grammar $grammar = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?: Grammar::getInstance();
    }

    /**
     * Set the table which the query is targeting.
     *
     * @return $this
     */
    public function from(string $table): Builder
    {
        $this->from = $table;

        return $this;
    }

    /**
     * Use distinct query
     *
     * @param  string|bool  $column
     * @return $this
     */
    public function distinct($column = true): Builder
    {
        $this->distinct = $column;

        return $this;
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @return float|int
     */
    public function sum(string $column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @return float|int
     */
    public function aggregate(string $function, string $column)
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
    public function get(): array
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
    public function getBindings(): array
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
    public function select($columns): Builder
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * Returns generated SQL query
     */
    public function toSQL(): string
    {
        return $this->grammar->compileSelectComponents($this);
    }

    /**
     * Retrieve the "count" result of the query.
     */
    public function count(string $columns = '*'): int
    {
        return (int) $this->aggregate(__FUNCTION__, $columns);
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @return float|int
     */
    public function avg(string $column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @return float|int
     */
    public function min(string $column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @return float|int
     */
    public function max(string $column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @return $this
     */
    public function skip(int $value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "offset" value of the query.
     *
     * @return $this
     */
    public function offset(?int $value)
    {
        $this->offset = ! is_null($value) ? (int) $value : null;

        return $this;
    }

    /**
     * Execute the query and get the first result.
     */
    public function first(): ?object
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
    public function limit($value): Builder
    {
        $this->limit = ! is_null($value) ? (int) $value : null;

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhere(string $column, $operator = null, $value = null): Builder
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
     * @param  string|numeric|null  $value
     * @param  string|numeric|null  $operator
     * @return array<int, mixed>
     */
    public function prepareValueAndOperator($value, $operator, bool $useDefault = false): array
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
     * @param  string|numeric|null  $operator
     * @param  mixed  $value
     */
    protected function invalidOperatorAndValue($operator, $value): bool
    {
        return is_null($value) && in_array($operator, $this->operators) &&
            ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|null|numeric  $operator
     * @param  string|null|float|int  $value
     * @return $this
     */
    public function where(string $column, $operator = null, $value = null, string $boolean = 'and'): Builder
    {
        $type = 'Basic';

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value,
            $operator,
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
     * @return $this
     */
    public function whereNull($columns, string $boolean = 'and', bool $not = false): Builder
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
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    protected function addBinding($value, string $type = 'where'): Builder
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
     * @param  mixed  $values
     * @return $this
     */
    public function whereNotIn(string $column, $values, string $boolean = 'and'): Builder
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param  mixed  $values
     * @return $this
     */
    public function whereIn(string $column, $values, string $boolean = 'and', bool $not = false): Builder
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
     * @return $this
     */
    public function whereNotNull($columns, string $boolean = 'and'): Builder
    {
        return $this->whereNull($columns, $boolean, true);
    }

    /**
     * Add a where-not between statement to the query.
     *
     * @param  array<int|string, string>  $values
     * @return $this
     */
    public function whereNotBetween(string $column, array $values, string $boolean = 'and'): Builder
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  array<string|int, int|float|string>  $values
     * @return $this
     */
    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): Builder
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
     * @return int|string
     */
    public function insert(array $values, bool $ignore = false)
    {
        if (! empty($values) && ! is_array(reset($values))) {
            $values = [$values];
        }

        $payload = array_reduce($values, function ($values, $value) {
            return array_merge($values, array_values($value));
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
     * Insert a new record and returns its ID
     *
     * @param  array<string, mixed>  $values
     * @return int|string
     */
    public function insertGetId(array $values)
    {
        return $this
            ->connection
            ->affectingStatement(
                $this->grammar->compileInsert($this, [$values], false), array_values($values),
                ReturnType::INSERT_ID
            );
    }

    /**
     * Update records in the database.
     *
     * @param  array<string, mixed>  $values
     * @return int|string
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
     * @return int|string
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
     * @return $this
     */
    public function whereColumn(string $first, ?string $operator = null, ?string $second = null, string $boolean = 'and'): Builder
    {
        $type = 'Column';

        [$second, $operator] = $this->prepareValueAndOperator(
            $second,
            $operator,
            func_num_args() === 2
        );

        $this->wheres[] = compact('type', 'first', 'operator', 'second', 'boolean');

        return $this;
    }

    /**
     * Add a left join clause to the query
     *
     * @return $this
     */
    public function leftJoin(string $table, ?string $first = null, ?string $operator = null, ?string $second = null): Builder
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a join clause to the query.
     *
     * @param  string|null|Closure  $first
     * @return $this
     */
    public function join(string $table, $first = null, ?string $operator = null, ?string $second = null, string $type = 'inner'): Builder
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
     * @param  string|null|Closure  $first
     * @return $this
     */
    public function rightJoin(string $table, $first = null, ?string $operator = null, ?string $second = null): Builder
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Apply a mixin to builder class
     *
     * @return $this
     */
    public function plugin(Pluggable $pluggable): Builder
    {
        $pluggable->apply($this);

        return $this;
    }

    /**
     * Add a `group by` clause to query
     */
    public function groupBy(string $column, ?string $direction = null): Builder
    {
        $this->groups[] = [$column, $direction];

        return $this;
    }

    /**
     * Add a nested where statement to the query.
     *
     * @return $this
     */
    public function whereNested(Closure $callback, string $boolean = 'and'): Builder
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
     */
    public function newQuery(): Builder
    {
        return new static($this->connection, $this->grammar);
    }

    /**
     * Run a truncate statement on the table.
     */
    public function truncate(): bool
    {
        return $this->connection->statement(
            $this->grammar->compileTruncate($this)
        );
    }

    /**
     * Add `withOne` relation
     */
    public function withOne(string $name, callable $callback, string $foreignKey, string $localKey = 'ID'): Builder
    {
        call_user_func($callback, $relation = new WithOne($name, $foreignKey, $localKey, $this->newQuery()));

        $this->with[] = $relation;

        return $this;
    }

    /**
     * Add `withMany` relation
     *
     * @return $this
     */
    public function withMany(string $name, callable $callback, string $foreignKey, string $localKey = 'ID'): Builder
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
    public function withRelation(Relation $relation, ?callable $callback = null): Builder
    {
        if (! is_null($callback)) {
            call_user_func($callback, $relation);
        }

        $this->with[] = $relation;

        return $this;
    }
}
