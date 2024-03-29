<?php

namespace Mehedi\WPQueryBuilderTests\Features;

use mysqli;

class TestMysqli
{
    protected static $connection;

    public static function get()
    {
        if (is_null(self::$connection)) {
            self::$connection = new mysqli(
                $_ENV['DB_HOST'],
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                $_ENV['DB_NAME'],
                $_ENV['DB_PORT']
            );
        }

        return self::$connection;
    }
}
