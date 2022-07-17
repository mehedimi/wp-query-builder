<?php

namespace Mehedi\WPQueryBuilderTests\Unit;

use Mehedi\WPQueryBuilder\Query\Grammar;
use Mehedi\WPQueryBuilder\Query\WPDB;
use Mehedi\WPQueryBuilderTests\FakeWPDB;
use PHPUnit\Framework\TestCase;

$wpdb = (object)[
    'prefix' => 'wp_',
    'prepare' => function () {

    }
];

class GrammarTest extends TestCase
{
    function getBuilder()
    {
        return new \Mehedi\WPQueryBuilder\Query\Builder(\Mehedi\WPQueryBuilder\Query\Grammar::getInstance());
    }

    function initFakeDB()
    {
        Grammar::getInstance();

        WPDB::set(new FakeWPDB());
    }

    /**
     * @test
     */
    function it_can_compile_select_columns()
    {
        $sql = $this->getBuilder()->select('*')->toSQL();
        $this->assertEquals('select *', $sql);

        $sql = $this->getBuilder()->select('name', 'id')->toSQL();
        $this->assertEquals('select name, id', $sql);

        $sql = $this->getBuilder()->select(['name', 'id'])->toSQL();
        $this->assertEquals('select name, id', $sql);
    }

    /**
     * @test
     */
    function it_can_compile_from_table()
    {
        $sql = $this->getBuilder()->from('posts')->toSQL();
        $this->assertEquals('select * from wp_posts', $sql);
    }

    /**
     * @test
     */
    function it_can_compile_distinct_query()
    {
        $sql = $this->getBuilder()->distinct()->select('name')->from('posts')->toSQL();
        $this->assertEquals('select distinct name from wp_posts', $sql);
        $sql = $this->getBuilder()->distinct('name')->from('posts')->toSQL();
        $this->assertEquals('select distinct name from wp_posts', $sql);
    }

    /**
     * @test
     */
    function it_can_compile_table_alias()
    {
        $sql = $this->getBuilder()->from('posts', 'p')->toSQL();
        $this->assertEquals('select * from wp_posts as p', $sql);
    }

    /**
     * @test
     */
    function it_can_compile_aggregation()
    {
        $this->initFakeDB();

        FakeWPDB::add('get_results', function ($sql) {
        });

        FakeWPDB::add('prepare', function ($sql) {
            $this->assertEquals('select sum(total) as aggregate from wp_posts', $sql);
        });

        $this->getBuilder()
            ->from('posts')
            ->aggregate('sum', 'total');

        FakeWPDB::add('prepare', function ($sql, ...$args) {
            $this->assertEquals('select sum(total) as aggregate from wp_posts where amount > %d and deleted_at is null', $sql);
            $this->assertEquals([100], $args);
        });

        $this->getBuilder()
            ->from('posts')
            ->where('amount', '>', 100)
            ->where('deleted_at', null)
            ->aggregate('sum', 'total');
    }

    /**
     * @test
     */
    function it_can_compile_limit()
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
    function it_can_compile_offset()
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
    function it_can_compile_offset_limit()
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
    function it_can_compile_a_basic_where_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->where('is_admin', 'yes')
            ->toSQL();
        $this->assertEquals("select * from wp_posts where is_admin = %s", $sql);

        $sql = $this->getBuilder()
            ->from('posts')
            ->where('is_admin', 1)
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin = %d", $sql);

        $sql = $this->getBuilder()
            ->from('posts')
            ->where('amount', '>', 1.5)
            ->toSQL();

        $this->assertEquals("select * from wp_posts where amount > %f", $sql);
    }

    /**
     * @test
     */
    function it_can_compile_multiple_basic_where_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->where('is_admin', 'yes')
            ->where('is_active', 1)
            ->where('amount', '>', 4.3, 'or')
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin = %s and is_active = %d or amount > %f", $sql);
    }

    /**
     * @test
     */
    function it_can_compile_basic_or_where_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->where('is_admin', 'yes')
            ->orWhere('is_editor', 'no')
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin = %s or is_editor = %s", $sql);
    }

    /**
     * @test
     */
    function it_can_compile_where_in_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->whereIn('is_admin', ['yes', 3, 8.4])
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin in (%s, %d, %f)", $sql);
    }

    /**
     * @test
     */
    function it_can_compile_where_not_in_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->whereNotIn('is_admin', ['yes', 3, 8.4])
            ->toSQL();

        $this->assertEquals("select * from wp_posts where is_admin not in (%s, %d, %f)", $sql);
    }

    /**
     * @test
     */
    function it_can_compile_where_null_clause()
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
    function it_can_compile_where_not_null_clause()
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
    function it_can_compile_where_between_clause()
    {
        $sql = $this->getBuilder()
            ->from('posts')
            ->whereBetween('amount', [3, 9])
            ->whereBetween('item', [3, 9], 'or', true)
            ->toSQL();

        $this->assertEquals("select * from wp_posts where amount between %d and %d or item not between %d and %d", $sql);
    }

    /**
     * @test
     */
    function it_can_compile_insert()
    {
        $this->initFakeDB();

        FakeWPDB::add('query', function ($sql) {
        });

        FakeWPDB::add('prepare', function ($sql, ...$args) {
            $this->assertEquals('insert into wp_posts default values', $sql);
            $this->assertEmpty($args);
        });

        $this->getBuilder()
            ->from('posts')
            ->insert([]);

        FakeWPDB::add('prepare', function ($sql, ...$args) {
            $this->assertEquals('insert into wp_posts(name, id, add) values (%s, %d, null)', $sql);
            $this->assertEquals(['foo', 3], $args);
        });

        $this->getBuilder()
            ->from('posts')
            ->insert([
                'name' => 'foo',
                'id' => 3,
                'add' => null
            ]);

        // Multiple row insert
        FakeWPDB::add('prepare', function ($sql, ...$args) {
            $this->assertEquals('insert into wp_posts(name, id) values (%s, %d), (%s, %d)', $sql);
            $this->assertEquals(['foo', 3, 'bar', 5], $args);
        });

        $this->getBuilder()
            ->from('posts')
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
    }

    /**
     * @test
     */
    function it_can_compile_update()
    {
        $this->initFakeDB();

        FakeWPDB::add('query', function ($sql) {
        });

        FakeWPDB::add('prepare', function ($sql, $title) {
            $this->assertEquals('update wp_posts set title = %s', $sql);
            $this->assertEquals('Title', $title);
        });

        $this->getBuilder()
            ->from('posts')
            ->update([
                'title' => 'Title',
            ]);

        FakeWPDB::add('prepare', function ($sql, ...$args) {
            $this->assertEquals('update wp_posts set title = %s where ID = %d', $sql);
            $this->assertEquals(['Title', 3], $args);
        });

        $this->getBuilder()
            ->from('posts')
            ->where('ID', 3)
            ->update([
                'title' => 'Title',
            ]);

        FakeWPDB::add('prepare', function ($sql, ...$args) {
            $this->assertEquals('update wp_posts set title = %s, content = null where ID = %d', $sql);
            $this->assertEquals(['Title', 3], $args);
        });

        $this->getBuilder()
            ->from('posts')
            ->where('ID', 3)
            ->update([
                'title' => 'Title',
                'content' => null
            ]);

        FakeWPDB::add('prepare', function ($sql, ...$args) {
            $this->assertEquals('update wp_posts set title = %s, content = null where ID = %d and deleted_at is null', $sql);
            $this->assertEquals(['Title', 3], $args);
        });

        $this->getBuilder()
            ->from('posts')
            ->where('ID', 3)
            ->where('deleted_at', null)
            ->update([
                'title' => 'Title',
                'content' => null
            ]);
    }

    /**
     * @test
     */
    function it_can_compile_delete()
    {
        $this->initFakeDB();

        FakeWPDB::add('query', function ($sql) {
        });

        FakeWPDB::add('prepare', function ($sql) {
            $this->assertEquals('delete from wp_posts', $sql);
        });

        $this->getBuilder()
            ->from('posts')
            ->delete();

        FakeWPDB::add('prepare', function ($sql, $id) {
            $this->assertEquals('delete from wp_posts where ID = %d', $sql);
            $this->assertEquals(3, $id);
        });

        $this->getBuilder()
            ->from('posts')
            ->where('ID', 3)
            ->delete();

        FakeWPDB::add('prepare', function ($sql, ...$args) {
            $this->assertEquals('delete from wp_posts where ID = %d and deleted_at is not null', $sql);
            $this->assertEquals([3], $args);
        });

        $this->getBuilder()
            ->from('posts')
            ->where('ID', 3)
            ->whereNotNull('deleted_at')
            ->delete();
    }

    /**
     * @test
     */
    function it_can_able_to_fetch_only_first_record()
    {
        $this->initFakeDB();

        FakeWPDB::add('get_row', function ($sql) {
        });

        FakeWPDB::add('prepare', function ($sql) {
            $this->assertEquals('select * from wp_posts limit 1', $sql);
        });

        $this->getBuilder()
            ->from('posts')
            ->first();

        FakeWPDB::add('prepare', function ($sql, ...$args) {
            $this->assertEquals('select * from wp_posts where ID = %d limit 1', $sql);
            $this->assertEquals([2], $args);
        });

        $this->getBuilder()
            ->from('posts')
            ->where('ID', 2)
            ->first();
    }

    /**
     * @test
     */
    function it_can_compile_order_by_clause()
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

        $this->assertEquals('select * from wp_posts where ID > %d order by date_created asc', $sql);
    }
}