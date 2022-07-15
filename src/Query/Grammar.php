<?php

namespace Mehedi\WPQueryBuilder\Query;

use Mehedi\WPQueryBuilder\Concerns\Singleton;

class Grammar
{
    use Singleton;

    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
    ];

    public function boot()
    {
        global $wpdb;

        WPDB::set($wpdb);
    }

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

    protected function compileAggregate($aggregate)
    {
        return sprintf('%s(%s) as aggregate', $aggregate[0], $aggregate[1]);
    }

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

    protected function compileFrom($from)
    {
        global $wpdb;

        return 'from ' . $wpdb->prefix . $from;
    }

    public function columnize(array $columns)
    {
        return implode(', ', $columns);
    }
}