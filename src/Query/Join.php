<?php

namespace Mehedi\WPQueryBuilder\Query;

use Mehedi\WPQueryBuilder\Connection;

class Join extends Builder
{
    /**
     * The type of join being performed.
     */
    public string $type;

    /**
     * The table the join clause is joining to.
     */
    public string $table;

    /**
     * Create a new join clause instance.
     *
     * @param  string  $table
     * @param  string  $type
     */
    public function __construct($table, $type, Connection $connection, ?Grammar $grammar = null)
    {
        $this->table = $table;
        $this->type = $type;

        parent::__construct($connection, $grammar);
    }

    /**
     * Add an "or on" clause to the join.
     */
    public function orOn(string $first, ?string $operator = null, ?string $second = null): Join
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Add an "on" clause to the join.
     */
    public function on(string $first, ?string $operator = null, ?string $second = null, string $boolean = 'and'): Join
    {
        return $this->whereColumn($first, $operator, $second, $boolean);
    }
}
