<?php

namespace Mehedi\WPQueryBuilderTests\Features;

class UpdateTest extends QueryBuilderFeatureTest
{
    /**
     * @test
     */
    public function it_can_update_a_row()
    {
        $this->ifNeedSkip();

        $this->truncate('postmeta');

        $this->getBuilder()->from('postmeta')
            ->insert([
                'meta_key' => 'name',
                'meta_value' => 'value'
            ]);

        $result = $this->getBuilder('postmeta')
            ->where('meta_key', 'name')
            ->update([
                'meta_value' => 'value_update'
            ]);

        $this->assertSame(1, $result);

        $data = $this->getBuilder('postmeta')->first();

        $this->assertEquals('value_update', $data->meta_value);
    }

    /**
     * @test
     */
    public function it_can_update_one_row_from_2()
    {
        $this->ifNeedSkip();
        $this->truncate('postmeta');

        $this->getBuilder()->from('postmeta')
            ->insert([
                [
                    'meta_key' => 'name1',
                    'meta_value' => 'value1'
                ],
                [
                    'meta_key' => 'name2',
                    'meta_value' => 'value2'
                ]
            ]);

        $result = $this->getBuilder('postmeta')
            ->where('meta_key', 'name1')
            ->update([
                'meta_value' => 'value_update_1'
            ]);

        $this->assertSame(1, $result);
        $data = $this->getBuilder('postmeta')->get();
        $data = array_column($data, 'meta_value', 'meta_key');

        $this->assertEquals([
            'name1' => 'value_update_1',
            'name2' => 'value2',
        ], $data);
    }

    /**
     * @test
     */
    public function it_can_update_multiple_rows()
    {
        $this->ifNeedSkip();
        $this->truncate('postmeta');

        $this->getBuilder()->from('postmeta')
            ->insert([
                [
                    'meta_key' => 'name1',
                    'meta_value' => 'value1'
                ],
                [
                    'meta_key' => 'name2',
                    'meta_value' => 'value2'
                ]
            ]);

        $result = $this->getBuilder('postmeta')
            ->update([
                'meta_value' => 'value_updated'
            ]);

        $this->assertSame(2, $result);
        $result = $this->getBuilder('postmeta')->get();

        $this->assertCount(2, $result);

        foreach ($result as $item) {
            $this->assertEquals('value_updated', $item->meta_value);
        }
    }
}
