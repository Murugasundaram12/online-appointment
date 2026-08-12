<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    public function test_phpunit_uses_isolated_sqlite_memory_database(): void
    {
        $connectionName = DB::getDefaultConnection();
        $databaseName = DB::connection()->getDatabaseName();

        $this->assertEquals('sqlite', $connectionName);
        $this->assertEquals(':memory:', $databaseName);
        $this->assertNotEquals('management_system', $databaseName);
    }
}
