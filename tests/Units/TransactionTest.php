<?php

namespace Mehedi\WPQueryBuilderTests\Units;

use Mehedi\WPQueryBuilder\Connection;
use Mehedi\WPQueryBuilder\Exceptions\QueryException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * @test
     */
    public function it_should_throw_exception_method_not_found()
    {
        $co = new Connection(m::mock(\mysqli::class));

        $this->expectException(\BadMethodCallException::class);

        $co->notFound();
    }

    /**
     * @test
     */
    public function it_can_begin_transaction()
    {
        $m = m::mock(\mysqli::class);

        $m->shouldReceive('begin_transaction')->andReturn(true);

        $co = new Connection($m);

        $this->assertTrue($co->beginTransaction());
    }

    /**
     * @test
     */
    public function it_can_commit_a_transaction()
    {
        $m = m::mock(\mysqli::class);

        $m->shouldReceive('commit')->andReturn(true);

        $co = new Connection($m);

        $this->assertTrue($co->commit());
    }

    /**
     * @test
     */
    public function it_can_rollback_a_transaction()
    {
        $m = m::mock(\mysqli::class);

        $m->shouldReceive('rollback')->andReturn(true);

        $co = new Connection($m);

        $this->assertTrue($co->rollback());
    }

    /**
     * @test
     */
    public function it_will_commit_the_transaction_if_no_exception_was_thrown_in_callback()
    {
        $m = m::mock(\mysqli::class);

        $m->shouldReceive('commit')->once();

        $co = new Connection($m);

        $result = $co->transaction(function () {
            return 3;
        });

        $this->assertEquals(3, $result);
    }

    /**
     * @test
     */
    public function it_will_rollback_the_transaction_if_exception_was_thrown_in_callback()
    {
        $m = m::mock(\mysqli::class);

        $m->shouldReceive('rollback')->once();

        $co = new Connection($m);

        $result = $co->transaction(function () {
            throw new QueryException('Failed');
        });

        $this->assertEquals(false, $result);
    }
}
