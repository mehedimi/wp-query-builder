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
 * @method static bool beginTransaction($flags = 0, $name = null)
 * @method static bool commit($flags = 0, $name = null)
 * @method static bool rollback($flags = 0, $name = null)
 * @method static bool transaction(callable $callback, $flags = 0, $name = null)
 *
 * @see Connection
 */
class DB
{
    use ForwardsCalls;

    /**
     * Single connection instance
     */
    protected static Connection $connection;

    /**
     * Set the table which the query is targeting.
     */
    public static function table(string $table): Builder
    {
        return (new Builder(self::getConnection()))
            ->from($table);
    }

    /**
     * Get the database connection from `$wpdb`
     */
    public static function getConnection(): Connection
    {
        if (! isset(self::$connection)) {
            global $wpdb;
            self::$connection = new Connection($wpdb->__get('dbh'));
            Grammar::getInstance()->setTablePrefix($wpdb->prefix);
        }

        return self::$connection;
    }

    /**
     * Apply a mixin to builder class
     */
    public static function plugin(Pluggable $plugin): Builder
    {
        return (new Builder(self::getConnection()))->plugin($plugin);
    }

    /**
     * Handle dynamic method calling
     *
     * @param  array<int>  $arguments
     * @return Builder
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return self::forwardCallTo(self::getConnection(), $name, $arguments);
    }
}
