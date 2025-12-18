<?php

namespace Mehedi\WPQueryBuilderTests\Features;

use Dotenv\Exception\InvalidPathException;
use Mehedi\WPQueryBuilder\Connection;
use Mehedi\WPQueryBuilder\Query\Builder;
use Mehedi\WPQueryBuilder\Query\Grammar;
use PHPUnit\Framework\TestCase;

abstract class QueryBuilderFeatureTest extends TestCase
{
    public function truncate($table): bool
    {
        return $this->getBuilder()->from($table)->truncate();
    }

    public function getBuilder($table = null): Builder
    {
        $builder = new Builder($this->getConnection());

        if ($table) {
            return $builder->from($table);
        }

        return $builder;
    }

    public function getConnection(): Connection
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

        try {
            LoadEnv::load();
        } catch (InvalidPathException $e) {
            // ignore the file not found exception
        }
    }
}
