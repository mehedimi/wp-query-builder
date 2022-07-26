<?php

namespace Mehedi\WPQueryBuilder;

use Mehedi\WPQueryBuilder\Contracts\Plugin;
use Mehedi\WPQueryBuilder\Query\Builder;

class DB
{
    /**
     * Set the table which the query is targeting.
     *
     * @param $table
     * @return Builder
     */
    public static function table($table)
    {
        return (new Builder())
            ->from($table);
    }

    /**
     * Apply a mixin to builder class
     *
     * @param Plugin $plugin
     * @return Builder
     */
    public static function plugin(Plugin $plugin)
    {
        return (new Builder())->plugin($plugin);
    }
}