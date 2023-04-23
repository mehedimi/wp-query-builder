<?php

namespace Mehedi\WPQueryBuilderTests\Units;

use Mehedi\WPQueryBuilder\Connection;
use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilderTests\FakePlugin;
use Mockery as m;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_set_table_name()
    {
        $b = $this->builder();

        $this->assertInstanceOf(get_class($b), $b->from('posts'));

        $this->assertEquals('posts', $b->from);
    }

    public function builder($mysqli = null)
    {
        $mysqli = $mysqli ?: m::mock(mysqli::class);

        return new Builder(new Connection($mysqli));
    }

    /**
     * @test
     */
    public function it_can_set_columns()
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
    public function it_can_set_distinct()
    {
        $b = $this->builder();

        $this->assertNull($b->distinct);

        $this->assertInstanceOf(get_class($b), $b->distinct());
        $this->assertEquals(true, $b->distinct);
    }

    /**
     * @test
     */
    public function it_can_set_aggregate_function()
    {
        $mysqli_stmt = m::mock(mysqli_stmt::class);
        $mysqli_result = m::mock(mysqli_result::class);

        $mysqli_stmt->shouldReceive('bind_param');
        $mysqli_stmt->shouldReceive('execute');

        $mysqli_result->shouldReceive('fetch_all')->andReturn([['aggregate' => 0]]);

        $mysqli_stmt->shouldReceive('get_result')->andReturn($mysqli_result);

        $mysqli = m::mock(mysqli::class);
        $mysqli->shouldReceive('prepare')->andReturn($mysqli_stmt);

        $b = $this->builder($mysqli);

        $output = $b->aggregate('sum', 'total');
        $this->assertEquals(['sum', 'total'], $b->aggregate);
        $this->assertEquals(0, $output);

        $b->aggregate('sum', 'total + amount');
        $this->assertEquals(['sum', 'total + amount'], $b->aggregate);

        $this->assertSame(0, $this->builder($mysqli)->avg('posts'));
    }

    /**
     * @test
     */
    public function it_can_set_limit()
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
    public function it_can_set_offset()
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
    public function it_can_set_basic_where_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->where('is_admin', '=', 1);
        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'Basic', 'column' => 'is_admin', 'operator' => '=', 'value' => 1, 'boolean' => 'and']],
            $b->wheres
        );
        $this->assertCount(1, $b->bindings['where']);
        $this->assertEquals(1, $b->bindings['where'][0]);
    }

    /**
     * @test
     */
    public function it_can_set_auto_equal_operator_on_basic_where_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->where('is_admin', 1);
        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'Basic', 'column' => 'is_admin', 'operator' => '=', 'value' => 1, 'boolean' => 'and']],
            $b->wheres
        );
        $this->assertCount(1, $b->bindings['where']);
        $this->assertEquals(1, $b->bindings['where'][0]);
    }

    /**
     * @test
     */
    public function it_can_set_basic_or_where_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->orWhere('is_admin', '=', 1);
        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'Basic', 'column' => 'is_admin', 'operator' => '=', 'value' => 1, 'boolean' => 'or']],
            $b->wheres
        );
        $this->assertCount(1, $b->bindings['where']);
        $this->assertEquals(1, $b->bindings['where'][0]);
    }

    /**
     * @test
     */
    public function it_can_set_where_in_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereIn('id', [2, '3', 8.3]);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'In', 'column' => 'id', 'values' => [2, '3', 8.3], 'boolean' => 'and']],
            $b->wheres
        );
        $this->assertCount(3, $b->bindings['where']);
        $this->assertEquals([2, '3', 8.3], $b->bindings['where']);
    }

    /**
     * @test
     */
    public function it_can_set_where_not_in_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNotIn('id', [2, '3', 8.3]);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'NotIn', 'column' => 'id', 'values' => [2, '3', 8.3], 'boolean' => 'and']],
            $b->wheres
        );
        $this->assertCount(3, $b->bindings['where']);
        $this->assertEquals([2, '3', 8.3], $b->bindings['where']);
    }

    /**
     * @test
     */
    public function it_can_set_where_null_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNull('name');

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'Null', 'column' => 'name', 'boolean' => 'and']],
            $b->wheres
        );

        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNull('name', 'or');

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'Null', 'column' => 'name', 'boolean' => 'or']],
            $b->wheres
        );
    }

    /**
     * @test
     */
    public function it_can_set_where_not_null_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNotNull('name');

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'NotNull', 'column' => 'name', 'boolean' => 'and']],
            $b->wheres
        );

        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNotNull('name', 'or');

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'NotNull', 'column' => 'name', 'boolean' => 'or']],
            $b->wheres
        );
    }

    /**
     * @test
     */
    public function it_can_set_where_null_clause_with_multiple_columns()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNull(['name', 'address']);

        $this->assertCount(2, $b->wheres);
        $this->assertEquals(
            [
                ['type' => 'Null', 'column' => 'name', 'boolean' => 'and'],
                ['type' => 'Null', 'column' => 'address', 'boolean' => 'and']
            ],
            $b->wheres
        );
    }

    /**
     * @test
     */
    public function it_can_set_where_not_null_clause_with_multiple_columns()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereNotNull(['name', 'address']);

        $this->assertCount(2, $b->wheres);
        $this->assertEquals(
            [
                ['type' => 'NotNull', 'column' => 'name', 'boolean' => 'and'],
                ['type' => 'NotNull', 'column' => 'address', 'boolean' => 'and']
            ],
            $b->wheres
        );
    }

    /**
     * @test
     */
    public function it_can_where_null_data_using_where_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->where('name', null);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'Null', 'column' => 'name', 'boolean' => 'and']],
            $b->wheres
        );
    }

    /**
     * @test
     */
    public function it_can_where_between_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereBetween('amount', [1, 10, 4]);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'Between', 'column' => 'amount', 'values' => [1, 10], 'boolean' => 'and', 'not' => false]],
            $b->wheres
        );
    }

    /**
     * @test
     */
    public function where_between_can_work_on_associative_range_clause()
    {
        $b = $this->builder();

        $this->assertIsArray($b->wheres);
        $this->assertEmpty($b->wheres);

        $b->whereBetween('amount', ['start' => 2, 'end' => 45]);

        $this->assertCount(1, $b->wheres);
        $this->assertEquals(
            [['type' => 'Between', 'column' => 'amount', 'values' => [2, 45], 'boolean' => 'and', 'not' => false]],
            $b->wheres
        );
    }

    /**
     * @test
     */
    public function it_should_have_insert_method()
    {
        $this->assertEquals(true, method_exists($this->builder(), 'insert'));
    }

    /**
     * @test
     */
    public function it_should_have_update_method()
    {
        $this->assertEquals(true, method_exists($this->builder(), 'update'));
    }

    /**
     * @test
     */
    public function it_should_have_delete_method()
    {
        $this->assertEquals(true, method_exists($this->builder(), 'delete'));
    }

    /**
     * @test
     */
    public function it_should_have_first_method()
    {
        $this->assertEquals(true, method_exists($this->builder(), 'first'));
    }

    /**
     * @test
     */
    public function it_can_use_order_by_clause()
    {
        $builder = $this->builder()
            ->from('posts')
            ->orderBy('ID');

        $this->assertInstanceOf(Builder::class, $builder);
        $this->assertEquals(['column' => 'ID', 'direction' => 'asc'], $builder->orders[0]);
    }

    /**
     * @test
     */
    public function it_can_set_where_columns()
    {
        $builder = $this->builder()
            ->from('posts')
            ->whereColumn('ID', 'post_id');

        $this->assertCount(1, $builder->wheres);
        $this->assertInstanceOf(Builder::class, $builder);
    }

    /**
     * @test
     */
    public function it_can_add_join_clause()
    {
        $builder = $this->builder()
            ->from('posts')
            ->join('post_meta', 'posts.ID', '=', 'post_mata.post_id');

        $this->assertCount(1, $builder->joins);
    }

    /**
     * @test
     */
    public function it_can_add_mixin()
    {
        $i = false;

        $callback = function () use (&$i) {
            $i = true;
        };

        $builder = $this->builder()
            ->from('posts')
            ->plugin(new FakePlugin($callback));

        $this->assertTrue($i);
        $this->assertInstanceOf(Builder::class, $builder);
    }

    /**
     * @test
     */
    public function it_can_add_grouping_columns()
    {
        $builder = $this->builder()->groupBy('ID')->groupBy('post_id', 'asc');

        $this->assertInstanceOf(Builder::class, $builder);
        $this->assertCount(2, $builder->groups);
        $this->assertEquals([['ID', null], ['post_id', 'asc']], $builder->groups);
    }

    /**
     * @test
     */
    public function it_can_add_nested_where()
    {
        $builder = $this->builder()->whereNested(function (Builder $builder) {
            $builder->where('ID', 1);
        });

        $this->assertInstanceOf(Builder::class, $builder);
        $this->assertCount(1, $builder->wheres);
        $this->assertInstanceOf(Builder::class, $builder->wheres[0]['query']);
        $this->assertEquals('Nested', $builder->wheres[0]['type']);
        $this->assertEquals('and', $builder->wheres[0]['boolean']);
    }
}
