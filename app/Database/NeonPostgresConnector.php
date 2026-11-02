<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonPostgresConnector extends PostgresConnector
{
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);
        // libpq 11 do XAMPP não envia SNI. A opção identifica o endpoint no Neon.
        if (preg_match('/^(ep-[a-z0-9-]+)\.[a-z0-9.-]+\.neon\.tech$/', $config['host'] ?? '', $matches)) {
            $endpoint = preg_replace('/-pooler$/', '', $matches[1]);
            $dsn .= ';options=endpoint='.$endpoint;
        }

        return $dsn;
    }
}
