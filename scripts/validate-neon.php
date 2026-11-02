<?php

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->loadEnvironmentFrom('.env.neon');

try {
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db = Illuminate\Support\Facades\DB::connection();
    $config = $db->getConfig();
    if (! str_ends_with($config['host'] ?? '', '.neon.tech') || $config['database'] !== 'neondb') { throw new RuntimeException('Alvo inválido'); }
    echo "Laravel conectado ao Neon: OK\n";
    foreach (['users', 'books', 'categories', 'loans', 'loan_audits', 'migrations'] as $table) {
        echo $table.': '.$db->table($table)->count()." registros\n";
    }
    $objects = $db->selectOne("SELECT
        (SELECT count(*) FROM pg_proc WHERE pronamespace = 'public'::regnamespace AND proname IN ('fn_livro_disponivel','fn_quantidade_emprestimos_ativos') AND prokind = 'f') AS functions,
        (SELECT count(*) FROM pg_proc WHERE pronamespace = 'public'::regnamespace AND proname IN ('sp_registrar_emprestimo','sp_registrar_devolucao') AND prokind = 'p') AS procedures,
        (SELECT count(*) FROM pg_trigger WHERE NOT tgisinternal AND tgname IN ('loans_validate','loans_audit','books_protect_status') AND tgenabled = 'O') AS triggers,
        (SELECT count(*) FROM pg_indexes WHERE schemaname = 'public' AND indexname IN ('books_title_order_idx','loans_active_user_idx','loans_one_active_copy_idx')) AS indexes");
    if ((int) $objects->functions !== 2 || (int) $objects->procedures !== 2 || (int) $objects->triggers !== 3 || (int) $objects->indexes !== 3) { throw new RuntimeException('Objetos incompletos'); }
    echo "Objetos: 2 functions, 2 procedures, 3 triggers e 3 índices confirmados\n";
    $db->beginTransaction();
    try {
        // Usuário transitório evita modificar contas reais. Tudo é revertido.
        $user = new App\Models\User;
        $user->name = 'Validação transacional Neon';
        $user->email = 'neon-check-'.bin2hex(random_bytes(8)).'@example.test';
        $user->password = bin2hex(random_bytes(32));
        $user->role = 'consulta';
        $user->save();
        $book = App\Models\Book::where('status', 'disponivel')->whereDoesntHave('loans', fn ($query) => $query->whereNull('returned_at'))->firstOrFail();
        $service = app(App\Services\LoanService::class);
        $loan = $service->registrar($user->id, $book->id, now()->addDays(14)->toDateString());
        if ($service->quantidadeAtivos($user->id) !== 1 || $book->fresh()->status !== 'emprestado') { throw new RuntimeException('Retirada inválida'); }
        $service->devolver($loan);
        if ($service->quantidadeAtivos($user->id) !== 0 || $book->fresh()->status !== 'disponivel' || $db->table('loan_audits')->where('loan_id', $loan->id)->count() !== 2) { throw new RuntimeException('Devolução inválida'); }
        echo "Retirada, devolução, functions e auditoria via Laravel: OK\n";
    } finally { $db->rollBack(); }
    echo "Validação revertida por ROLLBACK: nenhum registro de teste persistido\n";
} catch (Throwable $exception) {
    echo "Validação Neon interrompida. Credenciais e mensagens privadas foram omitidas.\n";
    exit(1);
}
