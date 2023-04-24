<?php

namespace Mehedi\WPQueryBuilderTests\Units;

use Mehedi\WPQueryBuilder\Connection;
use Mehedi\WPQueryBuilder\Exceptions\QueryException;
use PHPUnit\Framework\TestCase;
use Mockery as m;
class TransactionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * @test
     */
    function it_should_throw_exception_method_not_found()
    {
        $co = new Connection(m::mock(\mysqli::class));

        $this->expectException(\BadMethodCallException::class);

        $co->notFound();
    }

    /**
     * @test
     */
    function it_can_begin_transaction()
    {
        $m = m::mock(\mysqli::class);

        $m->shouldReceive('begin_transaction')->andReturn(true);

        $co = new Connection($m);

        $this->assertTrue($co->beginTransaction());
    }

    /**
     * @test
     */
    function it_can_commit_a_transaction()
    {
        $m = m::mock(\mysqli::class);

        $m->shouldReceive('commit')->andReturn(true);

        $co = new Connection($m);

        $this->assertTrue($co->commit());
    }

    /**
     * @test
     */
    function it_can_rollback_a_transaction()
    {
        $m = m::mock(\mysqli::class);

        $m->shouldReceive('rollback')->andReturn(true);

        $co = new Connection($m);

        $this->assertTrue($co->rollback());
    }

    /**
     * @test
     */
    function it_will_commit_the_transaction_if_no_exception_was_thrown_in_callback()
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
    function it_will_rollback_the_transaction_if_exception_was_thrown_in_callback()
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