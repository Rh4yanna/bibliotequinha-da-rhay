<?php

// Não imprime saída de ferramentas externas: erros podem conter detalhes privados.
require dirname(__DIR__).'/vendor/autoload.php';
$root = dirname(__DIR__);
$bin = 'C:/Program Files/PostgreSQL/18/bin/';
$step = 'configuração';

function connection(array $settings, bool $neon): array
{
    if ($neon) {
        $url = parse_url($settings['DB_URL'] ?? '');
        if (! $url || ! preg_match('/^ep-[a-z0-9-]+\.[a-z0-9.-]+\.neon\.tech$/', $url['host'] ?? '') || ($url['path'] ?? '') !== '/neondb') {
            throw new RuntimeException('Alvo inválido');
        }
        return ['host' => str_replace('-pooler.', '.', $url['host']), 'port' => $url['port'] ?? 5432, 'database' => 'neondb', 'user' => rawurldecode($url['user']), 'password' => rawurldecode($url['pass']), 'sslmode' => 'require'];
    }
    if (($settings['DB_HOST'] ?? '') !== '127.0.0.1' || ($settings['DB_DATABASE'] ?? '') !== 'campus' || ($settings['DB_PORT'] ?? '') !== '55432' || ! empty($settings['DB_URL'])) {
        throw new RuntimeException('Origem inesperada');
    }
    return ['host' => '127.0.0.1', 'port' => 55432, 'database' => 'campus', 'user' => $settings['DB_USERNAME'], 'password' => $settings['DB_PASSWORD'], 'sslmode' => 'disable'];
}

function pdoFor(array $config): PDO
{
    $dsn = 'pgsql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'].';sslmode='.$config['sslmode'].';connect_timeout=15';
    if (str_ends_with($config['host'], '.neon.tech')) { $dsn .= ';options=endpoint='.explode('.', $config['host'])[0]; }
    return new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function client(string $tool, array $config, array $arguments): void
{
    global $bin;
    $environment = getenv();
    foreach (['PGPASSWORD' => $config['password'], 'PGSSLMODE' => $config['sslmode'], 'PGHOST' => $config['host'], 'PGPORT' => (string) $config['port'], 'PGUSER' => $config['user'], 'PGDATABASE' => $config['database'], 'PGCONNECT_TIMEOUT' => '15', 'PGCLIENTENCODING' => 'UTF8'] as $key => $value) { $environment[$key] = $value; }
    // Remove configurações PostgreSQL herdadas para não acessar alvo inesperado.
    unset($environment['PGSERVICE'], $environment['PGSERVICEFILE'], $environment['PGOPTIONS']);
    $output = tempnam(sys_get_temp_dir(), 'neon-');
    try {
        $process = proc_open(array_merge([$bin.$tool.'.exe', '-w'], $arguments), [0 => ['file', 'NUL', 'r'], 1 => ['file', $output, 'w'], 2 => ['file', $output, 'a']], $pipes, null, $environment, ['bypass_shell' => true]);
        if (! is_resource($process) || proc_close($process) !== 0) { throw new RuntimeException('Ferramenta externa falhou'); }
    } finally { if (is_file($output)) { unlink($output); } }
}

try {
    $local = connection(Dotenv\Dotenv::parse(file_get_contents($root.'/.env')), false);
    $neon = connection(Dotenv\Dotenv::parse(file_get_contents($root.'/.env.neon')), true);
    $source = pdoFor($local);
    $target = pdoFor($neon);
    $tables = $target->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'")->fetchColumn();
    if ((int) $tables !== 0) { throw new RuntimeException('Destino não está vazio; transferência interrompida sem alterações'); }
    $directory = $root.'/storage/app/backups';
    if (! is_dir($directory)) { mkdir($directory, 0700, true); }
    $stamp = date('Ymd-His');
    $step = 'backup local';
    client('pg_dump', $local, ['-Fc', '-f', $directory.'/pre-neon-local-'.$stamp.'.dump']);
    echo "Backup local completo: OK\n";
    $step = 'backup Neon anterior à transferência';
    client('pg_dump', $neon, ['-Fc', '-f', $directory.'/pre-neon-destino-'.$stamp.'.dump']);
    echo "Backup do destino Neon vazio: OK\n";
    $step = 'exportação dos dados necessários';
    $libraryTables = ['users', 'categories', 'books', 'loans', 'loan_audits', 'migrations'];
    $arguments = ['-Fc', '--no-owner', '--no-acl', '-f', $directory.'/bibliotequinha-neon-'.$stamp.'.dump'];
    $sourceTables = $source->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($sourceTables as $table) {
        if (! preg_match('/^[a-z_]+$/', $table)) { throw new RuntimeException('Tabela inesperada'); }
        if (! in_array($table, $libraryTables, true)) { $arguments[] = '--exclude-table-data=public.'.$table; }
    }
    client('pg_dump', $local, $arguments);
    $step = 'restauração aditiva no Neon';
    // Sem --clean, DROP ou sobrescrita. Um erro desfaz toda a transferência.
    client('pg_restore', $neon, ['--no-owner', '--no-acl', '--exit-on-error', '--single-transaction', '-d', 'neondb', $directory.'/bibliotequinha-neon-'.$stamp.'.dump']);
    echo "Estrutura, dados de biblioteca e objetos SQL transferidos: OK\n";
    $step = 'comparação dos dados';
    foreach ($libraryTables as $table) {
        $query = 'SELECT count(*) AS total, md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY id)::text, \'[]\')) AS signature FROM "'.$table.'" t';
        $before = $source->query($query)->fetch(PDO::FETCH_ASSOC);
        $after = $target->query($query)->fetch(PDO::FETCH_ASSOC);
        if ($before !== $after) { throw new RuntimeException('Dados divergentes'); }
        echo $table.': '.$after['total']." registros; assinatura igual à origem\n";
    }
    echo "Transferência validada. Banco local preservado.\n";
} catch (Throwable $exception) {
    echo 'Interrompido na etapa: '.$step.". Nenhuma credencial foi exibida.\n";
    exit(1);
}
