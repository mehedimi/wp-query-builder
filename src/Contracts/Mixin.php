<?php

namespace Mehedi\WPQueryBuilder\Contracts;

use Mehedi\WPQueryBuilder\Query\Builder;

interface Mixin
{
    /**
     * Apply login into that method
     *
     * @param Builder $builder
     *
     * @return mixed
     */
    public function apply(Builder $builder);
}