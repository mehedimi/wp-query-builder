<?php

namespace Mehedi\WPQueryBuilder\Relations;

class WithMany extends WithOneOrMany
{
    /**
     * Loaded items with under its foreign key
     *
     * @return array<string, array<integer, object>>
     */
    protected function loadedItemsDictionary()
    {
        $items = [];
        $loadedItems = $this->getLoadedItems();

        foreach ($loadedItems as $loadedItem) {
            $items[$loadedItem->{$this->foreignKey}][] = $loadedItem;
        }

        return $items;
    }

    /**
     * Get mapped values from dictionary
     *
     * @param array<string|int, array<integer, object>> $loadedItems
     * @param object $item
     * @return array<integer, object>
     */
    protected function getItemFromDictionary($loadedItems, $item)
    {
        if (array_key_exists($item->{$this->localKey}, $loadedItems)) {
            return $loadedItems[$item->{$this->localKey}];
        }

        return [];
    }
}
