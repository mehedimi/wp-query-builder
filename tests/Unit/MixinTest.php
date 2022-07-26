<?php

namespace Mehedi\WPQueryBuilderTests\Unit;

use Mehedi\WPQueryBuilder\Plugins\JoinPostWithMeta;
use PHPUnit\Framework\TestCase;

$wpdb = (object)[
    'prefix' => 'wp_',
];

class MixinTest extends TestCase
{
    function getBuilder()
    {
        return new \Mehedi\WPQueryBuilder\Query\Builder();
    }

    /**
     * @test
     */
    function it_can_compile_join_post_with_meta_mixin()
    {
        $sql = $this->getBuilder()->plugin(new JoinPostWithMeta())->toSQL();

        $this->assertEquals('select * from wp_posts inner join wp_postmeta on wp_posts.ID = wp_postmeta.post_id', $sql);
    }
}