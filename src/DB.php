<?php

namespace Mehedi\WPQueryBuilder;

use Mehedi\WPQueryBuilder\Concerns\ForwardsCalls;
use Mehedi\WPQueryBuilder\Contracts\Pluggable;
use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Query\Grammar;

/**
 * @method array select($query, $bindings = [])
 * @method bool statement($query, $bindings = [])
 * @method int affectingStatement($query, $bindings = [])
 * @method bool insert($query, $bindings = [])
 * @method void enableQueryLog()
 * @method void disableQueryLog()
 * @method array getQueryLog()
 *
 * @see Connection
 */
class DB
{
    use ForwardsCalls;

    /**
     * Single connection instance
     *
     * @var Connection
     */
    protected static $connection;

    /**
     * Set the table which the query is targeting.
     *
     * @param $table
     * @return Builder
     */
    public static function table($table)
    {
        return (new Builder(self::getConnection()))
            ->from($table);
    }

    /**
     * Apply a mixin to builder class
     *
     * @param Pluggable $plugin
     * @return Builder
     */
    public static function plugin(Pluggable $plugin)
    {
        return (new Builder(self::getConnection()))->plugin($plugin);
    }

    /**
     * Get the database connection from `$wpdb`
     *
     * @return Connection
     */
    protected static function getConnection()
    {
        if (is_null(self::$connection)) {
            global $wpdb;
            self::$connection = new Connection($wpdb->__get('dbh'));
            Grammar::getInstance()->setTablePrefix($wpdb->prefix);
        }

        return self::$connection;
    }

    /**
     * Handle dynamic method calling
     *
     * @param $name
     * @param $arguments
     * @return Builder
     */
    public static function __callStatic($name, $arguments)
    {
        return self::forwardCallTo(self::getConnection(), $name, $arguments);
    }
}