<?php

namespace Mehedi\WPQueryBuilderTests\Unit;

use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Relations\WithMany;
use Mehedi\WPQueryBuilder\Relations\WithOne;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class WithManyTest extends TestCase
{
    function builder()
    {
        return new Builder();
    }

    /**
     * @test
     */
    function it_can_add_with_one_query()
    {
        $builder = $this->builder()
            ->from('posts')
            ->withMany('color', function (WithMany $builder) {
                $builder->from('postmeta')->where('name', 'color');
            }, 'post_id', 'ID');

        $this->assertCount(1, $builder->with);
        $this->assertInstanceOf(WithMany::class, $builder->with[0]);
    }

    /**
     * @test
     */
    function it_can_handle_with_one_query()
    {
        $posts = [
            (object) [
                'ID' => 1,
                'name' => 'some'
            ],
            (object) [
                'ID' => 2,
                'name' => 'some'
            ]
        ];

        $loadedItems = [
            (object)[
                'post_id' => 1,
                'value' => 'something'
            ]
        ];

        $expectation = [
            (object) [
                'ID' => 1,
                'name' => 'some',
                'meta' => [(object)[
                    'post_id' => 1,
                    'value' => 'something'
                ]]
            ],
            (object) [
                'ID' => 2,
                'name' => 'some',
                'meta' => []
            ]
        ];

        $builder = m::mock(Builder::class);
        $builder->shouldReceive('whereIn')
            ->with('post_id', [1, 2])->andReturn($builder);
        $builder->shouldReceive('get')->andReturn($loadedItems);
        $this->assertEquals($loadedItems, $builder->get());

        $relation = new WithMany('meta', 'post_id', 'ID', $builder);

        $this->assertEquals($expectation, $relation->setItems($posts)->load());
    }
}