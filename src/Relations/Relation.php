<?php

namespace Mehedi\WPQueryBuilder\Relations;

use Mehedi\WPQueryBuilder\Concerns\ForwardsCalls;
use Mehedi\WPQueryBuilder\DB;
use Mehedi\WPQueryBuilder\Query\Builder;

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
     * @var array<integer, object>
     */
    protected $items;

    /**
     * Constructor of Relation Class
     *
     * @param string $name
     * @param Builder|null $builder
     */
    public function __construct($name, Builder $builder = null)
    {
        $this->name = $name;
        $this->builder = $builder ?: new Builder(DB::getConnection());
    }

    /**
     * Load related items
     *
     * @return array<integer, object>
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
     * Loaded items with under its foreign key
     *
     * @return array<string|integer, object | array<integer, object>>
     */
    abstract protected function loadedItemsDictionary();

    /**
     * Get mapped value from dictionary
     *
     * @param array<string, object | array<integer, object>> $loadedItems
     * @param object $item
     * @return object|null | array<int, object>
     */
    abstract protected function getItemFromDictionary($loadedItems, $item);

    /**
     * Set items of record
     *
     * @param array<integer, object> $items
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
     * @param string $name
     * @param array<integer, string> $arguments
     * @return Builder
     */
    public function __call($name, $arguments)
    {
        if ($name === 'get') {
            self::throwBadMethodCallException($name);
        }

        return self::forwardCallTo($this->builder, $name, $arguments);
    }

    /**
     * Get loaded items
     *
     * @return array<integer, object>
     */
    abstract protected function getLoadedItems();
}
