<?php

namespace Mehedi\WPQueryBuilder\Query;

use BadMethodCallException;

/**
 * @method static prepare($query, ...$args)
 * @method static get_results($query)
 * @method static query($query)
 * @method static get_row($query)
 */
class WPDB
{
    /**
     * $wpdb object instance
     *
     * @var $wpdb
     */
    protected static $wpdb;

    /**
     * Allowed method names
     *
     * @var string $passThrough
     */
    protected static $passThrough = '/prepare|get_(results|row)|query|/';

    /**
     * Set $wpdb object
     *
     * @param $wpdb
     * @return void
     */
    public static function set($wpdb)
    {
        self::$wpdb = $wpdb;
    }

    /**
     * Get $wpdb object instance
     *
     * @return object
     */
    public static function get()
    {
        return self::$wpdb;
    }

    /**
     * Get table prefix from $wpdb object
     *
     * @return string
     */
    public static function prefix()
    {
        return self::$wpdb->prefix;
    }

    /**
     * Handling dynamic method invoking from $wpdb object
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (preg_match(self::$passThrough, $name)) {
            return call_user_func([self::$wpdb, $name], ...$arguments);
        }

        throw new BadMethodCallException(
            sprintf('Call to undefined method %s::%s', self::class, $name)
        );
    }
}