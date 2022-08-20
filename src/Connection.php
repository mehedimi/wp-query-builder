<?php

namespace Mehedi\WPQueryBuilder;

use Closure;
use Mehedi\WPQueryBuilder\Exceptions\QueryException;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;

class Connection
{
    /**
     * Mysqli connection instance
     *
     * @var mysqli
     */
    protected $mysqli;

    /**
     * Query logs
     *
     * @var array
     */
    protected $queryLogs;

    /**
     * Indicates whether queries are being logged.
     *
     * @var bool
     */
    protected $loggingQueries = false;

    /**
     * Create a new connection instance using mysqli connection from $wpdb
     *
     * @param mysqli $mysqli
     */
    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param $query
     * @param $bindings
     * @param Closure $callback
     * @return mixed
     */
    protected function run($query, $bindings, Closure $callback)
    {
        $start = microtime(true);

        $result = call_user_func($callback, $query, $bindings);

        $this->logQuery($query, $bindings, $this->getElapsedTime($start));

        return $result;
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function select($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
            $statement = $this->mysqli->prepare($query);

            $this->bindValues($statement, $bindings);

            $statement->execute();

            return $this->getRowsFromResult($statement->get_result());
        });
    }

    /**
     * Get rows from mysqli_result
     *
     * @param mysqli_result $result
     * @return array
     */
    protected function getRowsFromResult(mysqli_result $result)
    {
        return array_map(function ($row) {
            return (object)$row;
        }, $result->fetch_all(MYSQLI_ASSOC));
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {

            try {
                $statement = $this->mysqli->prepare($query);
            } catch (mysqli_sql_exception $e) {
                throw new QueryException($e->getMessage());
            }

            if (false === $statement) {
                throw new QueryException($this->mysqli->error);
            }

            $this->bindValues($statement, $bindings);

            return $statement->execute();
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement, and then we'll use affected_rows property of mysqli_stmt.
            try {
                $statement = $this->mysqli->prepare($query);
            } catch (mysqli_sql_exception $e) {
                throw new QueryException($e->getMessage());
            }


            if (false === $statement) {
                throw new QueryException($this->mysqli->error);
            }

            $this->bindValues($statement, $bindings);

            $statement->execute();

            return $statement->affected_rows;
        });
    }

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param \mysqli_stmt $statement
     * @param array $bindings
     * @return void
     */
    protected function bindValues($statement, $bindings)
    {
        if (empty($bindings)) {
            return;
        }

        $types = array_reduce($bindings, function ($carry, $value) {
            if (is_int($value)) {
                return $carry . 'i';
            }
            if (is_float($value)) {
                return $carry . 'd';
            }
            return $carry . 's';
        }, '');

        $statement->bind_param($types, ...$bindings);
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param int $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return void
     */
    protected function logQuery($query, $bindings, $time)
    {
        if ($this->loggingQueries) {
            $this->queryLogs[] = compact('query', 'bindings', 'time');
        }
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
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLogs;
    }
}