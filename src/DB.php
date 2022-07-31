<?php

namespace Mehedi\WPQueryBuilder;

use Mehedi\WPQueryBuilder\Contracts\Pluggable;
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
     * @param Pluggable $plugin
     * @return Builder
     */
    public static function plugin(Pluggable $plugin)
    {
        return (new Builder())->plugin($plugin);
    }
}