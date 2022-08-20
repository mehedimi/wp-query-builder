<?php

namespace Mehedi\WPQueryBuilderTests\Features;

use Mehedi\WPQueryBuilder\Connection;
use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Query\Grammar;
use PHPUnit\Framework\TestCase;

abstract class QueryBuilderFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LoadEnv::load();
    }

    public function getConnection()
    {
        TestMysqli::get();

        Grammar::getInstance()
            ->setTablePrefix($_ENV['TABLE_PREFIX']);

        return new Connection(TestMysqli::get());
    }

    public function getBuilder($table = null)
    {
        return (new Builder($this->getConnection()))->from($table);
    }

    function truncate($table)
    {
        return $this->getBuilder()->from($table)->truncate();
    }

    function ifNeedSkip()
    {
        if (empty($_ENV)) {
            $this->markTestSkipped('Need to configure database connection.');
        }
    }
}