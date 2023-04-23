<?php

namespace Mehedi\WPQueryBuilder;

use Mehedi\WPQueryBuilder\Concerns\ForwardsCalls;
use Mehedi\WPQueryBuilder\Contracts\Pluggable;
use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Query\Grammar;

/**
 * @method static array select($query, $bindings = [])
 * @method static bool statement($query, $bindings = [])
 * @method static bool insert($query, $bindings = [])
 * @method static bool update($query, $bindings = [])
 * @method static bool delete($query, $bindings = [])
 * @method static void enableQueryLog()
 * @method static void disableQueryLog()
 * @method static array getQueryLog()
 *
 * @see Connection
 */
class DB
{
    use ForwardsCalls;

    /**
     * Single connection instance
     *
     * @var Connection|null
     */
    protected static $connection;

    /**
     * Set the table which the query is targeting.
     *
     * @param string $table
     * @return Builder
     */
    public static function table($table)
    {
        return (new Builder(self::getConnection()))
            ->from($table);
    }

    /**
     * Get the database connection from `$wpdb`
     *
     * @return Connection
     */
    public static function getConnection()
    {
        if (is_null(self::$connection)) {
            global $wpdb;
            self::$connection = new Connection($wpdb->__get('dbh'));
            Grammar::getInstance()->setTablePrefix($wpdb->prefix);
        }

        return self::$connection;
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
     * Handle dynamic method calling
     *
     * @param string $name
     * @param array<int> $arguments
     * @return Builder
     */
    public static function __callStatic($name, $arguments)
    {
        return self::forwardCallTo(self::getConnection(), $name, $arguments);
    }
}
