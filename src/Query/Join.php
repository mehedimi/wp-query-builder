<?php

namespace Mehedi\WPQueryBuilder\Query;

use Mehedi\WPQueryBuilder\Connection;

class Join extends Builder
{
    /**
     * The type of join being performed.
     *
     * @var string
     */
    public $type;

    /**
     * The table the join clause is joining to.
     *
     * @var string
     */
    public $table;

    /**
     * Create a new join clause instance.
     *
     * @param  string  $table
     * @param  string  $type
     */
    public function __construct($table, $type, Connection $connection, Grammar $grammar = null)
    {
        $this->table = $table;
        $this->type = $type;

        parent::__construct($connection, $grammar);
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param  string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return Join
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Add an "on" clause to the join.
     *
     * @param  string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $boolean
     * @return Join
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        return $this->whereColumn($first, $operator, $second, $boolean);
    }
}
