<?php

namespace Mehedi\WPQueryBuilderTests\Units;

use Mehedi\WPQueryBuilder\Connection;
use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Query\Grammar;
use Mehedi\WPQueryBuilder\Query\Join;
use Mockery as m;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use PHPUnit\Framework\TestCase;

class GrammarTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_compile_select_columns()
    {
        $sql = $this->getBuilder()->select('*')->toSQL();
        $this->assertEquals('select *', $sql);

        $sql = $this->getBuilder()->select('name', 'id')->toSQL();
        $this->assertEquals('select name, id', $sql);

        $sql = $this->getBuilder()->select(['name', 'id'])->toSQL();
        $this->assertEquals('select name, id', $sql);
    }

    public function getBuilder()
    {
        $mysqli = m::mock(mysqli::class);

        $g = Grammar::getInstance()->setTablePrefix('wp_');

        return new Builder(new Connection($mysqli), $g);
    }

    /**
     * @test
     */
    public function it_can_compile_from_table()
    {
        $sql = $this->getBuilder()->from('posts')->toSQL();
        $this->assertEquals('select * from wp_posts', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_distinct_query()
    {
        $sql = $this->getBuilder()->distinct()->select('name')->from('posts')->toSQL();
        $this->assertEquals('select distinct name from wp_posts', $sql);
        $sql = $this->getBuilder()->distinct('name')->from('posts')->toSQL();
        $this->assertEquals('select distinct name from wp_posts', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_table_alias()
    {
        $sql = $this->getBuilder()->from('posts')->toSQL();
        $this->assertEquals('select * from wp_posts', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_aggregation()
    {
        $m = m::mock(mysqli::class);
        $p = m::mock(mysqli_stmt::class);

        $mysqli_result = m::mock(mysqli_result::class);

        $mysqli_result->shouldReceive('fetch_all')->andReturn([]);

        $p->shouldReceive('bind_param');
        $p->shouldReceive('execute');

        $p->shouldReceive('get_result')->andReturn($mysqli_result);

        $m->shouldReceive('prepare')
            ->with('select sum(total) as aggregate from wp_posts')
            ->andReturn($p);

        $g = Grammar::getInstance()->setTablePrefix('wp_');

        $b = new Builder(new Connection($m), $g);

        $b->from('posts')
            ->aggregate('sum', 'total');

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_compile_limit()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->limit(4)
            ->toSQL();

        $this->assertEquals('select * from wp_posts limit 4', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_offset()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->offset(4)
            ->toSQL();

        $this->assertEquals('select * from wp_posts offset 4', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_offset_limit()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->offset(4)
            ->limit(5)
            ->toSQL();

        $this->assertEquals('select * from wp_posts limit 5 offset 4', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_a_basic_where_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->where('is_admin', 'yes')
            ->toSQL();
        $this->assertEquals("select * from wp_posts where is_admin = ?", $sql);

        $sql = $this->getBuilder()
            ->from('posts')
            ->where('is_admin', 1)
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin = ?", $sql);

        $sql = $this->getBuilder()
            ->from('posts')
            ->where('amount', '>', 1.5)
            ->toSQL();

        $this->assertEquals("select * from wp_posts where amount > ?", $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_multiple_basic_where_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->where('is_admin', 'yes')
            ->where('is_active', 1)
            ->where('amount', '>', 4.3, 'or')
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin = ? and is_active = ? or amount > ?", $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_basic_or_where_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->where('is_admin', 'yes')
            ->orWhere('is_editor', 'no')
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin = ? or is_editor = ?", $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_where_in_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->whereIn('is_admin', ['yes', 3, 8.4])
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin in (?, ?, ?)", $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_where_not_in_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->whereNotIn('is_admin', ['yes', 3, 8.4])
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin not in (?, ?, ?)", $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_where_null_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->whereNull('name')
            ->whereNull('l_name', 'or')
            ->toSQL();

        $this->assertEquals("select * from wp_posts where name is null or l_name is null", $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_where_not_null_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->whereNotNull('name')
            ->whereNotNull(['l_name', 'f_name'])
            ->toSQL();

        $this->assertEquals("select * from wp_posts where name is not null and l_name is not null and f_name is not null", $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_where_between_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->whereBetween('amount', [3, 9])
            ->whereBetween('item', [3, 9], 'or', true)
            ->toSQL();

        $this->assertEquals("select * from wp_posts where amount between ? and ? or item not between ? and ?", $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_insert()
    {
        $m = m::mock(mysqli::class);
        $p = m::mock(mysqli_stmt::class);
        $p->shouldReceive('bind_param');
        $p->shouldReceive('execute');
        $p->shouldReceive('fetch_object');
        $p->shouldReceive('get_result')->andReturn($p);


        $m->shouldReceive('prepare')
            ->with('insert into wp_posts default values')
            ->andReturn($p);

        $g = Grammar::getInstance()->setTablePrefix('wp_');

        $b = new Builder(new Connection($m), $g);

        $b
            ->from('posts')
            ->insert([]);

        $m->shouldReceive('prepare')
            ->with('insert into wp_posts(name, id, add) values (?, ?, null)')
            ->andReturn($p);

        $b->from('posts')
            ->insert([
                'name' => 'foo',
                'id' => 3,
                'add' => null
            ]);

        $m->shouldReceive('prepare')
            ->with('insert into wp_posts(name, id) values (?, ?), (?, ?)')
            ->andReturn($p);

        $b->from('posts')
            ->insert([
                [
                    'name' => 'foo',
                    'id' => 3
                ],
                [
                    'name' => 'bar',
                    'id' => 5
                ]
            ]);

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_able_to_fetch_only_first_record()
    {
        $m = m::mock(mysqli::class);
        $stmt = m::mock(mysqli_stmt::class);
        $result = m::mock(mysqli_result::class);

        $this->assertTrue(true);

        $stmt->shouldReceive('bind_param');
        $stmt->shouldReceive('execute');
        $result->shouldReceive('fetch_all')->andReturn([]);
        $stmt->shouldReceive('get_result')->andReturn($result);

        $m->shouldReceive('prepare')
            ->with('select * from wp_posts limit 1')
            ->andReturn($stmt);

        $g = Grammar::getInstance()->setTablePrefix('wp_');

        $b = new Builder(new Connection($m), $g);

        $b->from('posts')->first();
    }

    public function gen()
    {
        yield [];
    }

    /**
     * @test
     */
    public function it_can_compile_order_by_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->orderBy('date_created')
            ->toSQL();

        $this->assertEquals('select * from wp_posts order by date_created asc', $sql);

        $sql = $this->getBuilder()
            ->from('posts')
            ->orderBy('date_created')
            ->where('ID', '>', 100)
            ->toSQL();

        $this->assertEquals('select * from wp_posts where ID > ? order by date_created asc', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_where_column_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->whereColumn('ID', 'post_id')
            ->toSQL();

        $this->assertEquals('select * from wp_posts where ID = post_id', $sql);

        $sql = $this->getBuilder()
            ->from('posts')
            ->whereColumn('ID', '>', 'post_id')
            ->toSQL();

        $this->assertEquals('select * from wp_posts where ID > post_id', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_join_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->join('post_meta', 'posts.ID', '=', 'post_meta.post_id')
            ->toSQL();

        $this->assertEquals('select * from wp_posts inner join wp_post_meta on wp_posts.ID = wp_post_meta.post_id', $sql);

        $sql = $this->getBuilder()
            ->select('posts.*')
            ->from('posts')
            ->join('post_meta', function (Join $join) {
                $join->on('posts.ID', '=', 'post_meta.post_id');
            })
            ->toSQL();

        $this->assertEquals('select wp_posts.* from wp_posts inner join wp_post_meta on wp_posts.ID = wp_post_meta.post_id', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_group_by()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->groupBy('ID')
            ->where('ID', '>', 10)
            ->groupBy('post_id', 'asc')
            ->limit(100)
            ->toSQL();

        $this->assertEquals('select * from wp_posts where ID > ? group by ID, post_id asc limit 100', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_where_nested_query()
    {
        $sql = $this->getBuilder()->from('posts')->whereNested(function (Builder $builder) {
            $builder->where('type', 'type_one')->orWhere('type_b', 'type_c');
        })->where('is_admin', 1)->toSQL();

        $this->assertEquals('select * from wp_posts where (type = ? or type_b = ?) and is_admin = ?', $sql);
    }

    /**
     * @test
     */
    public function it_can_compile_truncate_sql()
    {
        Grammar::getInstance()->setTablePrefix('wp_');

        $b = new Builder(new Connection(m::mock(mysqli::class)), Grammar::getInstance());

        $b->from('posts');

        $this->assertEquals('truncate table wp_posts', Grammar::getInstance()->compileTruncate($b));
    }

    /**
     * @test
     */
    public function it_can_compile_insert_or_ignore()
    {
        Grammar::getInstance()->setTablePrefix('wp_');

        $b = new Builder(new Connection(m::mock(mysqli::class)), Grammar::getInstance());

        $b->from('posts');

        $this->assertEquals(
            'insert ignore into wp_posts(name) values (?)',
            Grammar::getInstance()->compileInsert($b, [['name' => 'h']], true)
        );
    }
}
