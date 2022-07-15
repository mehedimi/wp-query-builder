<?php

namespace Mehedi\WPQueryBuilder\Query;

class WPDB
{
    protected static $wpdb;

    public static function set($wpdb)
    {
        self::$wpdb = $wpdb;
    }

    public static function get()
    {
        return self::$wpdb;
    }

    public static function prepare($query)
    {
        return self::$wpdb->prepare($query);
    }

    public static function get_results($query)
    {
        return self::$wpdb->get_results($query);
    }
}