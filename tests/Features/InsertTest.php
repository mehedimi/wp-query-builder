<?php

namespace Mehedi\WPQueryBuilderTests\Features;

use Faker\Factory;
use Mehedi\WPQueryBuilder\Exceptions\QueryException;

class InsertTest extends QueryBuilderFeatureTest
{
    /**
     * @test
     */
    public function it_can_insert_single_row()
    {
        $this->ifNeedSkip();

        $this->truncate('postmeta');

        $name = Factory::create()->name();

        $result = $this->getBuilder()->from('postmeta')->insert([
            'meta_key' => 'name',
            'meta_value' => $name,
        ]);

        $this->assertTrue($result);

        $data = $this->getBuilder()->from('postmeta')->get();

        $this->assertSame(1, count($data));
        $this->assertEquals([
            'meta_key' => 'name',
            'meta_value' => $name,
        ], [
            'meta_key' => $data[0]->meta_key,
            'meta_value' => $data[0]->meta_value,
        ]);
    }

    /**
     * @test
     */
    public function it_can_insert_multiple_rows()
    {
        $this->ifNeedSkip();

        $this->truncate('postmeta');

        $name1 = Factory::create()->name();
        $name2 = Factory::create()->name();

        $result = $this->getBuilder()->from('postmeta')->insert([
            [
                'meta_key' => 'name1',
                'meta_value' => $name1,
            ],
            [
                'meta_key' => 'name2',
                'meta_value' => $name2,
            ],
        ]);

        $this->assertTrue($result);

        $data = $this->getBuilder()->from('postmeta')->get();

        $this->assertSame(2, count($data));

        $this->assertEquals([
            [
                'meta_key' => 'name1',
                'meta_value' => $name1,
            ],
            [
                'meta_key' => 'name2',
                'meta_value' => $name2,
            ],
        ], [
            [
                'meta_key' => $data[0]->meta_key,
                'meta_value' => $data[0]->meta_value,
            ],
            [
                'meta_key' => $data[1]->meta_key,
                'meta_value' => $data[1]->meta_value,
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_can_handle_insert_or_ignore()
    {
        $this->ifNeedSkip();

        $this->truncate('postmeta');

        $name1 = Factory::create()->name();
        $name2 = Factory::create()->name();

        $result = $this->getBuilder()->from('postmeta')->insert([
            [
                'meta_id' => 1,
                'meta_key' => 'name1',
                'meta_value' => $name1,
            ],
            [
                'meta_id' => 2,
                'meta_key' => 'name2',
                'meta_value' => $name2,
            ],
        ]);

        $this->assertTrue($result);

        $result = $this->getBuilder()->from('postmeta')->insert([
            [
                'meta_id' => 1,
                'meta_key' => 'name1',
                'meta_value' => $name1,
            ],
            [
                'meta_id' => 2,
                'meta_key' => 'name2',
                'meta_value' => $name2,
            ],
            [
                'meta_id' => 3,
                'meta_key' => 'name3',
                'meta_value' => $name2,
            ],
        ], true);

        $this->assertSame(1, $result);
    }

    /**
     * @test
     */
    public function it_can_throw_an_exception()
    {
        $this->ifNeedSkip();
        $this->expectException(QueryException::class);
        $this->getBuilder()->from('postmeta')->insert(['invalid' => 'data']);
    }
}
