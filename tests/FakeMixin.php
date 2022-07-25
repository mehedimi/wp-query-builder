<?php

namespace Mehedi\WPQueryBuilderTests;

use Mehedi\WPQueryBuilder\Query\Builder;

class FakeMixin implements \Mehedi\WPQueryBuilder\Contracts\Mixin
{
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function apply(Builder $builder)
    {
        call_user_func($this->callback);
    }
}