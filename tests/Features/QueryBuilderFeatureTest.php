<?php

namespace Mehedi\WPQueryBuilderTests\Features;

use Mehedi\WPQueryBuilder\Connection;
use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Query\Grammar;
use PHPUnit\Framework\TestCase;

abstract class QueryBuilderFeatureTest extends TestCase
{
    public function truncate($table)
    {
        return $this->getBuilder()->from($table)->truncate();
    }

    public function getBuilder($table = null)
    {
        return (new Builder($this->getConnection()))->from($table);
    }

    public function getConnection()
    {
        TestMysqli::get();

        Grammar::getInstance()
            ->setTablePrefix($_ENV['TABLE_PREFIX']);

        return new Connection(TestMysqli::get());
    }

    public function ifNeedSkip()
    {
        if (empty($_ENV)) {
            $this->markTestSkipped('Need to configure database connection.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        LoadEnv::load();
    }
}
