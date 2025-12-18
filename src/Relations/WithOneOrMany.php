<?php

namespace Mehedi\WPQueryBuilder\Relations;

use Mehedi\WPQueryBuilder\Query\Builder;

abstract class WithOneOrMany extends Relation
{
    /**
     * Foreign key of the parent table
     */
    protected string $foreignKey;

    /**
     * Local key of the parent table
     */
    protected string $localKey;

    /**
     * @param  string  $foreignKey
     */
    public function __construct(string $name, $foreignKey, string $localKey, Builder $builder)
    {
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        parent::__construct($name, $builder);
    }

    /**
     * Get loaded items
     *
     * @return array<int, object>
     */
    protected function getLoadedItems(): array
    {
        return $this->builder->whereIn($this->foreignKey, $this->extractKeyValues())->get();
    }

    /**
     * Extract foreign key values
     *
     * @return array<int, string>
     */
    protected function extractKeyValues(): array
    {
        return array_column($this->items, $this->localKey);
    }
}
