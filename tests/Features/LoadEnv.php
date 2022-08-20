<?php

namespace Mehedi\WPQueryBuilderTests\Features;

use Dotenv\Dotenv;

class LoadEnv
{
    protected static $loaded;

    static function load()
    {
        if (self::$loaded !== true) {
            Dotenv::createImmutable(dirname(dirname(__DIR__)))->load();
            self::$loaded = true;
        }
    }
}