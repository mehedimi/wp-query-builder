<?php

namespace Mehedi\WPQueryBuilder;

use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Query\Grammar;

class DB
{
    /**
     * Set the table which the query is targeting.
     *
     * @param $table
     * @param $as
     * @return Builder
     */
    public static function table($table, $as = null)
    {
        return (new Builder(Grammar::getInstance()))
            ->from($table, $as);
    }
}