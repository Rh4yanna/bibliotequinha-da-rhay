<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = config('database.default');
        $database = config("database.connections.$connection.database");
        if ($connection !== 'sqlite' || $database !== ':memory:') {
            if ($connection !== 'pgsql' || $database !== 'bibliotequinha_test'
                || config('database.connections.pgsql.host') !== '127.0.0.1'
                || config('database.connections.pgsql.url')) {
                throw new \RuntimeException('Testes permitidos apenas em SQLite :memory: ou PostgreSQL local bibliotequinha_test sem DB_URL.');
            }
        }
        return $app;
    }
}
