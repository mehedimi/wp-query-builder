<?php

namespace Mehedi\WPQueryBuilder\Mixins;

use Mehedi\WPQueryBuilder\Contracts\Mixin;
use Mehedi\WPQueryBuilder\Query\Builder;

class JoinPostWithMeta implements Mixin
{
    protected $type;

    public function __construct($type = 'inner')
    {
        $this->type = $type;
    }

    /**
     * Joining posts table with post_meta
     *
     * @param Builder $builder
     *
     * @return void
     */
    public function apply(Builder $builder)
    {
        $builder->from('posts')
            ->join('postmeta', 'posts.ID', '=', 'postmeta.post_id', $this->type);
    }
}