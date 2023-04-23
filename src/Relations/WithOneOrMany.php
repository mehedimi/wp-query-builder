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
     * @param string $name
     * @param string $foreignKey
     * @param string $localKey
     * @param Builder $builder
     */
    public function __construct($name, $foreignKey, $localKey, Builder $builder)
    {
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        parent::__construct($name, $builder);
    }

    /**
     * Get loaded items
     *
     * @return array<integer, object>
     */
    protected function getLoadedItems()
    {
        return $this->builder->whereIn($this->foreignKey, $this->extractKeyValues())->get();
    }

    /**
     * Extract foreign key values
     *
     * @return array<int, string>
     */
    protected function extractKeyValues()
    {
        return array_column($this->items, $this->localKey);
    }
}
