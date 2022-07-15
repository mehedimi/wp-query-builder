<?php

namespace Mehedi\WPQueryBuilder\Concerns;

trait Singleton
{
    protected static $instance;

    /**
     * Get single instance
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;

            if (method_exists(static::$instance, 'boot')) {
                call_user_func([self::$instance, 'boot']);
            }
        }

        return static::$instance;
    }
}