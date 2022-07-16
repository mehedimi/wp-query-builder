<?php

namespace Mehedi\WPQueryBuilder\Query;

use Mehedi\WPQueryBuilder\Concerns\Singleton;

class Grammar
{
    use Singleton;

    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'limit',
        'offset',
    ];

    /**
     * Booting grammar instance
     *
     * @return void
     */
    public function boot()
    {
        global $wpdb;

        WPDB::set($wpdb);
    }

    /**
     * Compile a select query into SQL.
     *
     * @param Builder $builder
     * @return string
     */
    public function compileSelectComponents(Builder $builder)
    {
        $sql = ['select' . ($builder->distinct ? ' distinct' : '')];

        foreach ($this->selectComponents as $component) {
            if (isset($builder->{$component})) {
                $sql[$component] = call_user_func(
                    [$this, 'compile' . ucfirst($component)],
                    $builder->{$component},
                    $builder
                );
            }
        }

        return implode(' ', array_filter($sql));
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param array $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return implode(', ', $columns);
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param $aggregate
     * @return string
     */
    protected function compileAggregate($aggregate)
    {
        return sprintf('%s(%s) as aggregate', $aggregate[0], $aggregate[1]);
    }

    /**
     * Compile the "select *" portion of the query.
     *
     * @param $columns
     * @param Builder $builder
     * @return bool|mixed|string|null
     */
    protected function compileColumns($columns, Builder $builder)
    {
        if (isset($builder->aggregate) && $columns === '*') {
            return null;
        }

        if (is_string($builder->distinct)) {
            return $builder->distinct;
        }

        if (!is_array($columns)) {
            return $columns;
        }

        return $this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     *
     * @param $from
     * @return string
     */
    protected function compileFrom($from)
    {
        global $wpdb;

        return 'from ' . $wpdb->prefix . $from;
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param $limit
     * @return string
     */
    protected function compileLimit($limit)
    {
        return 'limit ' . $limit;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param $offset
     * @return string
     */
    public function compileOffset($offset)
    {
        return 'offset ' . $offset;
    }
}