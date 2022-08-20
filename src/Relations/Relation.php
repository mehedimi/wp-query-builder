<?php

namespace Mehedi\WPQueryBuilder\Relations;

use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Concerns\ForwardsCalls;

/**
 * @method Builder from($table)
 */
abstract class Relation
{
    use ForwardsCalls;

    /**
     * The name of relation
     *
     * @var string
     */
    protected $name;

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

    /**
     * Constructor of Relation Class
     *
     * @param $name
     * @param Builder $builder
     */
    public function __construct($name, Builder $builder = null)
    {
        $this->name = $name;
        $this->builder = $builder ?: new Builder();
    }

    /**
     * Get loaded items
     *
     * @return array[]
     */
    abstract protected function getLoadedItems();

    /**
     * Loaded items with under its foreign key
     *
     * @return array[]
     */
    abstract protected function loadedItemsDictionary();

    /**
     * Get mapped value from dictionary
     *
     * @return mixed
     */
    abstract protected function getItemFromDictionary($loadedItems, $item);


    /**
     * Load related items
     *
     * @return array
     */
    public function load()
    {
        $loadedItems = $this->loadedItemsDictionary();

        return array_map(function ($item) use (&$loadedItems) {
            $item->{$this->name} = $this->getItemFromDictionary($loadedItems, $item);
            return $item;
        }, $this->items);
    }

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

        return self::forwardCallTo($this->builder, $name, $arguments);
    }
}