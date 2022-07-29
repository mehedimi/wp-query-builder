<?php

namespace Mehedi\WPQueryBuilder\Relations;

use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Concerns\ForwardsCalls;

/**
 * @method static Builder from($table)
 */
abstract class Relation
{
    use ForwardsCalls;

    /**
     * The query builder instance
     *
     * @var Builder
     */
    protected $builder;

    /**
     * @var array
     */
    protected $items;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Load the relationship
     *
     * @return array
     */
    abstract public function load();

    /**
     * Set items of record
     *
     * @param array $items
     * @return $this
     */
    public function setItems(array $items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Handle dynamic methods call of query builder
     *
     * @param $name
     * @param $arguments
     * @return Builder
     */
    public function __call($name, $arguments)
    {
        if ($name === 'get') {
            self::throwBadMethodCallException($name);
        }

        return $this->forwardCallTo($this->builder, $name, $arguments);
    }
}