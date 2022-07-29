<?php

namespace Mehedi\WPQueryBuilder\Relations;

use Mehedi\WPQueryBuilder\Query\Builder;

abstract class WithOneOrMany extends Relation
{
    /**
     * Foreign key of the parent table
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * Local key of the parent table
     *
     * @var string
     */
    protected $localKey;

    /**
     * Name of the attribute
     *
     * @var string
     */
    protected $name;

    public function __construct($name, $foreignKey, $localKey, Builder $builder)
    {
        $this->name = $name;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        parent::__construct($builder);
    }

    /**
     * Get loaded items
     *
     * @return array
     */
    protected function getLoadedItems()
    {
        return $this->builder->whereIn($this->foreignKey, $this->extractKeyValues())->get();
    }

    /**
     * Extract foreign key values
     *
     * @return array
     */
    protected function extractKeyValues()
    {
        return array_column($this->items, $this->localKey);
    }

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
}