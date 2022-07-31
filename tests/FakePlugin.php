<?php

namespace Mehedi\WPQueryBuilderTests;

use Mehedi\WPQueryBuilder\Contracts\Pluggable;
use Mehedi\WPQueryBuilder\Query\Builder;

class FakePlugin implements Pluggable
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