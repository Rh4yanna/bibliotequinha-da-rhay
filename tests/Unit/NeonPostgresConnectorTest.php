<?php

namespace Tests\Unit;

use App\Database\NeonPostgresConnector;
use PHPUnit\Framework\TestCase;

class NeonPostgresConnectorTest extends TestCase
{
    public function test_endpoint_option_is_scoped_to_neon_and_handles_pooler(): void
    {
        $method = new \ReflectionMethod(NeonPostgresConnector::class, 'getDsn');
        $connector = new NeonPostgresConnector;
        $local = $method->invoke($connector, ['host' => '127.0.0.1', 'database' => 'campus']);
        $this->assertStringNotContainsString('options=', $local);
        foreach (['ep-example-123.region.aws.neon.tech', 'ep-example-123-pooler.region.aws.neon.tech'] as $host) {
            $dsn = $method->invoke($connector, ['host' => $host, 'database' => 'neondb', 'sslmode' => 'require']);
            $this->assertStringContainsString(';options=endpoint=ep-example-123', $dsn);
            $this->assertStringContainsString(';sslmode=require', $dsn);
        }
    }
}
