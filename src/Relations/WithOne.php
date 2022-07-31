<?php

namespace Mehedi\WPQueryBuilder\Relations;

class WithOne extends WithOneOrMany
{
    /**
     * Loaded items with under its foreign key
     *
     * @return array
     */
    protected function loadedItemsDictionary()
    {
        $items = [];
        $loadedItems = $this->getLoadedItems();

        foreach ($loadedItems as $loadedItem) {
            $items[$loadedItem->{$this->foreignKey}] = $loadedItem;
        }

        return $items;
    }

    /**
     * Get mapped value from dictionary
     *
     * @param $loadedItems
     * @param $item
     * @return object|null
     */
    protected function getItemFromDictionary($loadedItems, $item)
    {
        if (array_key_exists($item->{$this->localKey}, $loadedItems)) {
            return $loadedItems[$item->{$this->localKey}];
        }

        return null;
    }
}