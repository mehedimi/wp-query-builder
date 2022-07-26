<?php

namespace Mehedi\WPQueryBuilder;

use Mehedi\WPQueryBuilder\Contracts\Mixin;
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
     * @param Mixin $mixin
     * @return Builder
     */
    public static function mixin(Mixin $mixin)
    {
        return (new Builder())->mixin($mixin);
    }
}