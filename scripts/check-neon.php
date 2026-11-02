<?php

// Diagnóstico somente de leitura. Nunca imprime URL, senha ou mensagens do driver.
require dirname(__DIR__).'/vendor/autoload.php';

try {
    $settings = Dotenv\Dotenv::parse(file_get_contents(dirname(__DIR__).'/.env.neon'));
    $url = parse_url($settings['DB_URL'] ?? '');
    if (! $url || ! str_ends_with($url['host'] ?? '', '.neon.tech') || ($url['path'] ?? '') !== '/neondb') {
        throw new RuntimeException('config');
    }
    $endpoint = str_replace('-pooler', '', explode('.', $url['host'])[0]);
    if (! preg_match('/^ep-[a-z0-9-]+$/', $endpoint)) { throw new RuntimeException('config'); }
    $db = new PDO(
        'pgsql:host='.$url['host'].';port='.($url['port'] ?? 5432).';dbname=neondb;sslmode=require;connect_timeout=10;options=endpoint='.$endpoint,
        rawurldecode($url['user'] ?? ''), rawurldecode($url['pass'] ?? ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Conexão Neon: OK\n";
    echo 'Banco neondb: '.($db->query('SELECT current_database()')->fetchColumn() === 'neondb' ? 'OK' : 'INCORRETO')."\n";
    echo "SSL do cliente: obrigatório (sslmode=require)\n";
    // No pooler, pg_stat_ssl descreve o trecho interno e não o TLS do cliente.
    echo 'Tabelas de aplicação no schema public: '.$db->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'")->fetchColumn()."\n";
} catch (Throwable $exception) {
    echo "Não foi possível validar a conexão. Nenhuma credencial foi exibida.\n";
    $message = $exception->getMessage();
    echo match (true) {
        str_contains($message, 'password authentication failed') => "Motivo: autenticação recusada.\n",
        str_contains($message, 'could not translate host name') => "Motivo: resolução DNS indisponível.\n",
        str_contains($message, 'could not find driver') => "Motivo: driver PostgreSQL ausente.\n",
        str_contains($message, 'Endpoint ID'), str_contains($message, 'endpoint ID'), str_contains($message, 'SNI') => "Motivo: libpq antigo sem SNI; precisa identificar o endpoint Neon.\n",
        str_contains($message, 'timeout'), str_contains($message, 'timed out') => "Motivo: tempo limite de rede.\n",
        $exception instanceof Dotenv\Exception\InvalidFileException => "Motivo: sintaxe do arquivo de ambiente inválida.\n",
        default => "Motivo: conexão ou configuração recusada; requer verificação adicional.\n",
    };
    exit(1);
}
