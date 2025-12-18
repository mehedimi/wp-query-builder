<?php

namespace Mehedi\WPQueryBuilder;

use Closure;
use Exception;
use Mehedi\WPQueryBuilder\Concerns\ForwardsCalls;
use Mehedi\WPQueryBuilder\Exceptions\QueryException;
use Mehedi\WPQueryBuilder\Query\ReturnType;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use mysqli_stmt;

/**
 * @method bool beginTransaction($flags = 0, $name = null)
 * @method bool commit($flags = 0, $name = null)
 * @method bool rollback($flags = 0, $name = null)
 */
class Connection
{
    use ForwardsCalls;

    /**
     * Mysqli connection instance
     */
    protected mysqli $mysqli;

    /**
     * Query logs
     *
     * @var array<int, array<string, array<int, int|string>|float|int|string>>|null
     */
    protected ?array $queryLogs;

    /**
     * Indicates whether queries are being logged.
     */
    protected bool $loggingQueries = false;

    /**
     * Create a new connection instance using mysqli connection from $wpdb
     */
    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  array<int, mixed>  $bindings
     * @return array<int, object>
     */
    public function select(string $query, array $bindings = []): array
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
            try {
                $statement = $this->mysqli->prepare($query);
            } catch (mysqli_sql_exception $e) {
                throw new QueryException($e->getMessage(), $e->getCode());
            }

            if ($statement === false) {
                throw new QueryException($this->mysqli->error);
            }

            $this->bindValues($statement, $bindings);

            $statement->execute();

            $result = $statement->get_result();

            if ($result === false) {
                throw new QueryException($statement->error);
            }

            return $this->getRowsFromResult($result);
        });
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param  array<int, mixed>  $bindings
     * @return mixed
     */
    protected function run(string $query, array $bindings, Closure $callback)
    {
        $start = microtime(true);

        $result = call_user_func($callback, $query, $bindings);

        $this->logQuery($query, $bindings, $this->getElapsedTime($start));

        return $result;
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  array<int, string|int>  $bindings
     * @return void
     */
    protected function logQuery(string $query, array $bindings, float $time)
    {
        if ($this->loggingQueries) {
            $this->queryLogs[] = compact('query', 'bindings', 'time');
        }
    }

    /**
     * Get the elapsed time since a given starting point.
     */
    protected function getElapsedTime(float $start): float
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  array<int, mixed>  $bindings
     * @return void
     */
    protected function bindValues(mysqli_stmt $statement, array $bindings)
    {
        if (empty($bindings)) {
            return;
        }

        $types = array_reduce($bindings, function ($carry, $value) {
            if (is_int($value)) {
                return $carry.'i';
            }
            if (is_float($value)) {
                return $carry.'d';
            }

            return $carry.'s';
        }, '');

        $statement->bind_param($types, ...$bindings);
    }

    /**
     * Get rows from mysqli_result
     *
     * @return array<int, object>
     */
    protected function getRowsFromResult(mysqli_result $result): array
    {
        return array_map(function ($row) {
            return (object) $row;
        }, $result->fetch_all(MYSQLI_ASSOC));
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  array<int, mixed>  $bindings
     * @return int|string
     */
    public function insert(string $query, array $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  array<int, mixed>  $bindings
     */
    public function statement(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            try {
                $statement = $this->mysqli->prepare($query);
            } catch (mysqli_sql_exception $e) {
                throw new QueryException($e->getMessage());
            }

            if ($statement === false) {
                throw new QueryException($this->mysqli->error);
            }

            $this->bindValues($statement, $bindings);

            return $statement->execute();
        });
    }

    /**
     * Run the update query with affected rows
     *
     * @param  array<int, mixed>  $bindings
     * @return int|string
     */
    public function update(string $query, array $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  array<int, mixed>  $bindings
     * @return int|string
     */
    public function affectingStatement(string $query, array $bindings = [], int $returnType = ReturnType::AFFECTED_ROW)
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($returnType) {
            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement, and then we'll use the affected_rows property of mysqli_stmt.
            try {
                $statement = $this->mysqli->prepare($query);
            } catch (mysqli_sql_exception $e) {
                throw new QueryException($e->getMessage());
            }

            if ($statement === false) {
                throw new QueryException($this->mysqli->error);
            }

            $this->bindValues($statement, $bindings);

            $bool = $statement->execute();

            if ($returnType === ReturnType::AFFECTED_ROW) {
                return $statement->affected_rows;
            }

            if ($returnType === ReturnType::INSERT_ID) {
                return $statement->insert_id;
            }

            return $bool;
        });
    }

    /**
     * Run delete query with affected rows
     *
     * @param  array<int, mixed>  $bindings
     * @return int|string
     */
    public function delete(string $query, array $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Enable the query log on the connection.
     *
     * @return void
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
     *
     * @return void
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
    }

    /**
     * Get the connection query logs.
     *
     * @return array<int, array<string, array<int, int|string>|float|int|string>>|null
     */
    public function getQueryLog(): ?array
    {
        return $this->queryLogs;
    }

    /**
     * Execute a callback within a transaction
     *
     * @return false|mixed
     */
    public function transaction(callable $callback, int $flags = 0, ?string $name = null)
    {
        try {
            $result = call_user_func($callback);
            $this->commit($flags, $name);

            return $result;
        } catch (Exception $e) {
            $this->rollback($flags, $name);

            return false;
        }
    }

    /**
     * @param  string  $name
     * @param  array<int, string>  $arguments
     * @return bool
     */
    public function __call($name, $arguments)
    {
        if (! preg_match('/^(beginTransaction|commit|rollback)$/', $name)) {
            self::throwBadMethodCallException($name);
        }

        // @phpstan-ignore-next-line
        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));

        // @phpstan-ignore-next-line
        return call_user_func_array([$this->mysqli, $name], $arguments);
    }
}
