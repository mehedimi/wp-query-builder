<?php
namespace Mehedi\WPQueryBuilderTests\Unit;

use Mehedi\WPQueryBuilder\Query\Grammar;
use Mehedi\WPQueryBuilderTests\FakeWPDB;
use PHPUnit\Framework\TestCase;

$wpdb = (object) [
    'prefix' => 'wp_',
    'prepare' => function () {

    }
];

use Mockery as m;

class GrammarTest extends TestCase
{
    /**
     * @test
     */
    function it_can_compile_select_columns() {
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
    function it_can_compile_from_table() {
        $sql = $this->getBuilder()->from('posts')->toSQL();
        $this->assertEquals('select * from wp_posts', $sql);
    }

    /**
     * @test
     */
    function it_can_compile_distinct_query() {
        $sql = $this->getBuilder()->distinct()->select('name')->from('posts')->toSQL();
        $this->assertEquals('select distinct name from wp_posts', $sql);
        $sql = $this->getBuilder()->distinct('name')->from('posts')->toSQL();
        $this->assertEquals('select distinct name from wp_posts', $sql);
    }

    /**
     * @test
     */
    function it_can_compile_table_alias() {
        $sql = $this->getBuilder()->from('posts', 'p')->toSQL();
        $this->assertEquals('select * from wp_posts as p', $sql);
    }

    /**
     * @test
     */
    function it_can_compile_aggregation() {

        FakeWPDB::add('prepare', function ($sql) {
            $this->assertEquals('select sum(total) as aggregate from wp_posts', $sql);
        });

        FakeWPDB::add('get_results', function ($sql) {

        });

        Grammar::getInstance();

        \Mehedi\WPQueryBuilder\Query\WPDB::set(new FakeWPDB());

        $this->getBuilder()
            ->from('posts')
            ->aggregate('sum', 'total');
    }

    public function getBuilder()
    {
        return new \Mehedi\WPQueryBuilder\Query\Builder(\Mehedi\WPQueryBuilder\Query\Grammar::getInstance());
    }
}