<?php

// A origem é exclusivamente a instância fictícia local de testes.
require dirname(__DIR__).'/vendor/autoload.php';
$root = dirname(__DIR__);
try {
    $env = Dotenv\Dotenv::parse(file_get_contents($root.'/.env.testing'));
    if (($env['DB_HOST'] ?? '') !== '127.0.0.1' || ($env['DB_PORT'] ?? '') !== '55433' || ($env['DB_DATABASE'] ?? '') !== 'bibliotequinha_test' || ! empty($env['DB_URL'])) {
        throw new RuntimeException('Alvo inesperado');
    }
    $pdo = new PDO('pgsql:host=127.0.0.1;port=55433;dbname=bibliotequinha_test;sslmode=disable', $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $users = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    if (count($users) !== 3 || array_diff(array_column($users, 'email'), ['admin@biblioteca.test', 'bibliotecario@biblioteca.test', 'consulta@biblioteca.test']) || (int) $pdo->query('SELECT count(*) FROM books')->fetchColumn() !== 16 || (int) $pdo->query('SELECT count(*) FROM categories')->fetchColumn() !== 4 || (int) $pdo->query('SELECT count(*) FROM loans')->fetchColumn() !== 0) {
        throw new RuntimeException('Dados diferentes do seed fictício');
    }
    $sql = "-- Dados exclusivamente fictícios para banco LOCAL vazio criado por 00_estrutura.sql.\n-- Nenhuma senha conhecida ou token: hashes aleatórios sem credenciais utilizáveis.\n-- Não executar no Neon ou em banco com dados existentes.\n";
    foreach (['users', 'categories', 'books', 'migrations'] as $table) {
        $rows = $table === 'users' ? $users : $pdo->query('SELECT * FROM "'.$table.'" ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if ($table === 'users') {
                $row['password'] = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
                $row['remember_token'] = null;
                $row['name'] = 'Pessoa fictícia — '.$row['role'];
            }
            $columns = implode(', ', array_map(fn ($column) => '"'.$column.'"', array_keys($row)));
            $values = implode(', ', array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($row)));
            $sql .= 'INSERT INTO "'.$table.'" ('.$columns.') VALUES ('.$values.");\n";
        }
        $sql .= "SELECT setval(pg_get_serial_sequence('".$table."', 'id'), (SELECT max(id) FROM \"".$table."\"), true);\n";
    }
    $sql .= <<<'SQL'

-- Histórico demonstrativo gerado pelas próprias procedures/triggers.
DO $$
DECLARE leitor bigint; exemplar bigint; emprestimo bigint;
BEGIN
    SELECT id INTO leitor FROM users WHERE email = 'consulta@biblioteca.test';
    SELECT id INTO exemplar FROM books WHERE code = 'LIV-0002';
    CALL sp_registrar_emprestimo(leitor, exemplar, CURRENT_DATE + 14);
    SELECT id INTO emprestimo FROM loans WHERE book_id = exemplar AND returned_at IS NULL;
    CALL sp_registrar_devolucao(emprestimo);
    CALL sp_registrar_emprestimo(leitor, exemplar, CURRENT_DATE + 14);
END;
$$;
SQL;
    file_put_contents($root.'/database/sql/03_dados_ficticios.sql', $sql."\n");
    echo "SQL fictício gerado: 3 usuários sem senha conhecida, 4 categorias, 16 livros e histórico.\n";
} catch (Throwable $exception) {
    echo "Exportação cancelada: origem ou conteúdo não corresponde ao ambiente fictício isolado.\n";
    exit(1);
}
