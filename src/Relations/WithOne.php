<?php

namespace Mehedi\WPQueryBuilder\Relations;

class WithOne extends WithOneOrMany
{
    /**
     * Loaded items with under its foreign key
     */
    protected function loadedItemsDictionary(): array
    {
        $items = [];
        $loadedItems = $this->getLoadedItems();

        foreach ($loadedItems as $loadedItem) {
            $items[$loadedItem->{$this->foreignKey}] = $loadedItem;
        }

        return $items;
    }

    /**
     * Get mapped value from the dictionary
     */
    protected function getItemFromDictionary(array $loadedItems, object $item)
    {
        if (isset($loadedItems[$item->{$this->localKey}])) {
            return $loadedItems[$item->{$this->localKey}];
        }

        return null;
    }
}
