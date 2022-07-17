<?php

namespace Mehedi\WPQueryBuilderTests\Unit;

use Mehedi\WPQueryBuilder\Query\Builder;
use PHPUnit\Framework\TestCase;
use Mehedi\WPQueryBuilderTests\FakeWPDB;

class BuilderTest extends TestCase
{
    /**
     * @test
     */
    function it_can_set_table_name()
    {
        $b = $this->builder();

        $this->assertInstanceOf(get_class($b), $b->from('posts'));

        $this->assertEquals('posts', $b->from);
    }

    /**
     * @test
     */
    function it_can_set_columns()
    {
        $b = $this->builder();

        $this->assertInstanceOf(get_class($b), $b->select('*'));
        $this->assertEquals(['*'], $b->columns);

        $this->assertInstanceOf(get_class($b), $b->select(['name']));
        $this->assertEquals(['name'], $b->columns);
    }

    /**
     * @test
     */
    function it_can_set_distinct()
    {
        $b = $this->builder();

        $this->assertNull($b->distinct);

        $this->assertInstanceOf(get_class($b), $b->distinct());
        $this->assertEquals(true, $b->distinct);
    }

    /**
     * @test
     */
    function it_can_set_table_alias()
    {
        $b = $this->builder();

        $b->from('post', 'p');

        $this->assertEquals('post as p', $b->from);
    }

    /**
     * @test
     */
    function it_can_set_aggregate_function()
    {
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

    function builder()
    {
        return new \Mehedi\WPQueryBuilder\Query\Builder(\Mehedi\WPQueryBuilder\Query\Grammar::getInstance());
    }

    /**
     * @test
     */
    function it_can_set_limit()
    {
        $b = $this->builder();

        $this->assertNull($b->limit);
        $b->limit(null);
        $this->assertEquals(0, $b->limit);
        $this->assertInstanceOf(get_class($b), $b->limit('4'));
        $this->assertEquals(4, $b->limit);
    }

    /**
     * @test
     */
    function it_can_set_offset()
    {
        $b = $this->builder();

        $this->assertNull($b->offset);
        $b->offset(null);
        $this->assertEquals(0, $b->offset);
        $this->assertInstanceOf(get_class($b), $b->offset('4'));
        $this->assertEquals(4, $b->offset);
    }

    /**
     * @test
     */
    function it_can_set_basic_where_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->where('is_admin', '=', 1);
        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'Basic', 'column' => 'is_admin', 'operator' => '=', 'value' => 1, 'boolean' => 'and'],
            $b->wheres[0]
        );
        $this->assertCount(1, $b->bindings['where']);
        $this->assertEquals(1, $b->bindings['where'][0]);
    }

    /**
     * @test
     */
    function it_can_set_auto_equal_operator_on_basic_where_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->where('is_admin', 1);
        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'Basic', 'column' => 'is_admin', 'operator' => '=', 'value' => 1, 'boolean' => 'and'],
            $b->wheres[0]
        );
        $this->assertCount(1, $b->bindings['where']);
        $this->assertEquals(1, $b->bindings['where'][0]);
    }

    /**
     * @test
     */
    function it_can_set_basic_or_where_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->orWhere('is_admin', '=', 1);
        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'Basic', 'column' => 'is_admin', 'operator' => '=', 'value' => 1, 'boolean' => 'or'],
            $b->wheres[0]
        );
        $this->assertCount(1, $b->bindings['where']);
        $this->assertEquals(1, $b->bindings['where'][0]);
    }

    /**
     * @test
     */
    function it_can_set_where_in_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereIn('id', [2, '3', 8.3]);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'In', 'column' => 'id', 'values' => [2, '3', 8.3], 'boolean' => 'and'],
            $b->wheres[0]
        );
        $this->assertCount(3, $b->bindings['where']);
        $this->assertEquals([2, '3', 8.3], $b->bindings['where']);
    }

    /**
     * @test
     */
    function it_can_set_where_not_in_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNotIn('id', [2, '3', 8.3]);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'NotIn', 'column' => 'id', 'values' => [2, '3', 8.3], 'boolean' => 'and'],
            $b->wheres[0]
        );
        $this->assertCount(3, $b->bindings['where']);
        $this->assertEquals([2, '3', 8.3], $b->bindings['where']);
    }

    /**
     * @test
     */
    function it_can_set_where_null_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNull('name');

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'Null', 'column' => 'name', 'boolean' => 'and'],
            $b->wheres[0]
        );

        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNull('name', 'or');

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'Null', 'column' => 'name', 'boolean' => 'or'],
            $b->wheres[0]
        );
    }

    /**
     * @test
     */
    function it_can_set_where_not_null_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNotNull('name');

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'NotNull', 'column' => 'name', 'boolean' => 'and'],
            $b->wheres[0]
        );

        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNotNull('name', 'or');

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'NotNull', 'column' => 'name', 'boolean' => 'or'],
            $b->wheres[0]
        );
    }

    /**
     * @test
     */
    function it_can_set_where_null_clause_with_multiple_columns()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNull(['name', 'address']);

        $this->assertCount(2, $b->wheres);
        $this->assertEquals(
            ['type' => 'Null', 'column' => 'name', 'boolean' => 'and'],
            $b->wheres[0]
        );
        $this->assertEquals(
            ['type' => 'Null', 'column' => 'address', 'boolean' => 'and'],
            $b->wheres[1]
        );
    }

    /**
     * @test
     */
    function it_can_set_where_not_null_clause_with_multiple_columns()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNotNull(['name', 'address']);

        $this->assertCount(2, $b->wheres);
        $this->assertEquals(
            ['type' => 'NotNull', 'column' => 'name', 'boolean' => 'and'],
            $b->wheres[0]
        );
        $this->assertEquals(
            ['type' => 'NotNull', 'column' => 'address', 'boolean' => 'and'],
            $b->wheres[1]
        );
    }

    /**
     * @test
     */
    function it_can_where_null_data_using_where_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->where('name', null);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'Null', 'column' => 'name', 'boolean' => 'and'],
            $b->wheres[0]
        );
    }

    /**
     * @test
     */
    function it_can_where_between_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereBetween('amount', [1, 10, 4]);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'Between', 'column' => 'amount', 'values' => [1, 10], 'boolean' => 'and', 'not' => false],
            $b->wheres[0]
        );
    }

    /**
     * @test
     */
    function where_between_can_work_on_associative_range_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereBetween('amount', ['start' => 2, 'end' => 45]);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            ['type' => 'Between', 'column' => 'amount', 'values' => [2, 45], 'boolean' => 'and', 'not' => false],
            $b->wheres[0]
        );
    }

    /**
     * @test
     */
    function it_should_have_insert_method()
    {
        $this->assertEquals(true, method_exists($this->builder(), 'insert'));
    }

    /**
     * @test
     */
    function it_should_have_update_method()
    {
        $this->assertEquals(true, method_exists($this->builder(), 'update'));
    }

    /**
     * @test
     */
    function it_should_have_delete_method()
    {
        $this->assertEquals(true, method_exists($this->builder(), 'delete'));
    }

    /**
     * @test
     */
    function it_should_have_first_method()
    {
        $this->assertEquals(true, method_exists($this->builder(), 'first'));
    }

    /**
     * @test
     */
    function it_can_use_order_by_clause()
    {
        $builder = $this->builder()
            ->from('posts')
            ->orderBy('ID');

        $this->assertInstanceOf(Builder::class, $builder);
        $this->assertEquals(['column' => 'ID', 'direction' => 'asc'], $builder->orders[0]);
    }
}