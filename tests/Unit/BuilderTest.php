<?php

use Mehedi\WPQueryBuilder\Query\Grammar;
use Mehedi\WPQueryBuilderTests\FakeWPDB;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @test
     */
    function it_can_set_table_name() {
        $b = $this->builder();

        $this->assertInstanceOf(get_class($b), $b->from('posts'));

        $this->assertEquals('posts', $b->from);
    }

    /**
     * @test
     */
    function it_can_set_columns() {
        $b = $this->builder();

        $this->assertInstanceOf(get_class($b), $b->select('*'));
        $this->assertEquals(['*'], $b->columns);

        $this->assertInstanceOf(get_class($b), $b->select(['name']));
        $this->assertEquals(['name'], $b->columns);
    }

    /**
     * @test
     */
    function it_can_set_distinct() {
        $b = $this->builder();

        $this->assertNull($b->distinct);

        $this->assertInstanceOf(get_class($b), $b->distinct());
        $this->assertEquals(true, $b->distinct);
    }

    /**
     * @test
     */
    function it_can_set_table_alias() {
        $b = $this->builder();

        $b->from('post', 'p');

        $this->assertEquals('post as p', $b->from);
    }

    /**
     * @test
     */
    function it_can_set_aggregate_function() {
        $b = $this->builder();

        FakeWPDB::add('prepare', function ($sql) {

        });

        FakeWPDB::add('get_results', function ($sql) {

        });

        \Mehedi\WPQueryBuilder\Query\WPDB::set(new FakeWPDB());

        $b->aggregate('sum', 'total');
        $this->assertEquals(['sum', 'total'], $b->aggregate);

        $b->aggregate('sum', 'total + amount');
        $this->assertEquals(['sum', 'total + amount'], $b->aggregate);
    }

    function builder() {
        return new \Mehedi\WPQueryBuilder\Query\Builder(\Mehedi\WPQueryBuilder\Query\Grammar::getInstance());
    }
}