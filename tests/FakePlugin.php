<?php

namespace Mehedi\WPQueryBuilderTests;

use Mehedi\WPQueryBuilder\Query\Builder;

class FakePlugin implements \Mehedi\WPQueryBuilder\Contracts\Plugin
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