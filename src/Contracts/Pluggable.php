<?php

namespace Mehedi\WPQueryBuilder\Contracts;

use Mehedi\WPQueryBuilder\Query\Builder;

interface Pluggable
{
    /**
     * Apply that plugin
     *
     * @return mixed
     */
    public function apply(Builder $builder);
}
