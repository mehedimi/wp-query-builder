<?php

namespace Mehedi\WPQueryBuilder\Relations;

class WithMany extends WithOneOrMany
{
    /**
     * Loaded items with under its foreign key
     *
     * @return array<string, array<int, object>>
     */
    protected function loadedItemsDictionary(): array
    {
        $items = [];
        $loadedItems = $this->getLoadedItems();

        foreach ($loadedItems as $loadedItem) {
            $items[$loadedItem->{$this->foreignKey}][] = $loadedItem;
        }

        return $items;
    }

    /**
     * Get mapped values from a dictionary
     */
    protected function getItemFromDictionary(array $loadedItems, object $item)
    {
        if (array_key_exists($item->{$this->localKey}, $loadedItems)) {
            return $loadedItems[$item->{$this->localKey}];
        }

        return [];
    }
}
