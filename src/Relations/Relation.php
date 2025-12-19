<?php

namespace Mehedi\WPQueryBuilder\Relations;

use Mehedi\WPQueryBuilder\Concerns\ForwardsCalls;
use Mehedi\WPQueryBuilder\Query\Builder;

/**
 * @method Builder from($table)
 */
abstract class Relation
{
    use ForwardsCalls;

    /**
     * The name of the relation
     */
    protected string $name;

    /**
     * The query builder instance
     */
    protected Builder $builder;

    /**
     * @var array<int, object>
     */
    protected array $items;

    /**
     * Constructor of Relation Class
     */
    public function __construct(string $name, ?Builder $builder = null)
    {
        $this->name = $name;
        $this->builder = $builder ?: new Builder($this->builder->connection);
    }

    /**
     * Load related items
     *
     * @return array<int, object>
     */
    public function load(): array
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
     * @return array<string, object>
     */
    abstract protected function loadedItemsDictionary(): array;

    /**
     * Get mapped value from the dictionary
     *
     * @param  array<string, object>  $loadedItems
     * @return object|null | array<int, object>
     */
    abstract protected function getItemFromDictionary(array $loadedItems, object $item);

    /**
     * Set items of record
     *
     * @param  array<int, object>  $items
     * @return $this
     */
    public function setItems(array $items): Relation
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Handle dynamic methods call of query builder
     *
     * @param  array<int, string>  $arguments
     * @return Builder
     */
    public function __call(string $name, array $arguments)
    {
        if ($name === 'get') {
            self::throwBadMethodCallException($name);
        }

        return self::forwardCallTo($this->builder, $name, $arguments);
    }

    /**
     * Get loaded items
     *
     * @return array<int, object>
     */
    abstract protected function getLoadedItems(): array;
}
