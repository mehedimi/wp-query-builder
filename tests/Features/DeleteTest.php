<?php

namespace Mehedi\WPQueryBuilderTests\Features;

class DeleteTest extends QueryBuilderFeatureTest
{
    /**
     * @test
     */
    public function it_can_delete_a_single_item()
    {
        $this->ifNeedSkip();
        $this->truncate('postmeta');

        $this->getBuilder('postmeta')
            ->insert([
                [
                    'meta_key' => 'name1',
                    'meta_value' => 'value1',
                ],
                [
                    'meta_key' => 'name2',
                    'meta_value' => 'value2',
                ],
            ]);

        $count = $this->getBuilder('postmeta')->where('meta_key', 'name1')->delete();
        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function it_can_delete_many_rows()
    {
        $this->ifNeedSkip();
        $this->truncate('postmeta');

        $this->getBuilder('postmeta')
            ->insert([
                [
                    'meta_key' => 'name1',
                    'meta_value' => 'value1',
                ],
                [
                    'meta_key' => 'name2',
                    'meta_value' => 'value2',
                ],
            ]);

        $count = $this->getBuilder('postmeta')->delete();
        $this->assertSame(2, $count);
    }
}
