<?php

namespace Mehedi\WPQueryBuilder\Query;

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
     * @param $table
     * @param $type
     * @param Grammar|null $grammar
     */
    public function __construct($table, $type, Grammar $grammar = null)
    {
        $this->table = $table;
        $this->type = $type;

        parent::__construct($this->connection, $grammar);
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param $first
     * @param $operator
     * @param $second
     * @return Join
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Add an "on" clause to the join.
     *
     * @param $first
     * @param $operator
     * @param $second
     * @param $boolean
     * @return Join
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        return $this->whereColumn($first, $operator, $second, $boolean);
    }
}