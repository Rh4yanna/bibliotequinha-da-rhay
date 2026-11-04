<?php

// Apoio local da gravação; não é endpoint nem recurso adicional da aplicação.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->loadEnvironmentFrom('.env.gravacao');
try {
    if ($app->configurationIsCached()) { throw new RuntimeException; }
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db = Illuminate\Support\Facades\DB::connection();
    $config = $db->getConfig();
    if (($config['host'] ?? '') !== '127.0.0.1' || (int) ($config['port'] ?? 0) !== 55433
        || ! preg_match('/^bibliotequinha_gravacao_[0-9]{14}$/', $config['database'] ?? '')) { throw new RuntimeException; }
    $action = $argv[1] ?? 'destino';
    if ($action === 'destino') {
        echo "Destino confirmado: PostgreSQL LOCAL 127.0.0.1:55433, banco exclusivo de gravação.\n";
        echo 'Livros: '.$db->table('books')->count()."\n";
    } elseif ($action === 'preparar') {
        $category = App\Models\Category::where('code', 'LIT')->firstOrFail();
        App\Models\Book::firstOrCreate(['code' => 'VIDEO-EMPR-01'], ['title' => 'Livro para empréstimo gravado', 'author' => 'Autora Fictícia', 'publication_year' => 2020, 'category_id' => $category->id, 'status' => 'disponivel']);
        echo "Exemplar VIDEO-EMPR-01 preparado apenas no banco de gravação.\n";
    } elseif ($action === 'promover') {
        $user = App\Models\User::where('email', 'apresentacao@example.test')->firstOrFail();
        $user->role = 'admin'; $user->save();
        echo "Conta fictícia da apresentação promovida a admin, apenas localmente.\n";
    } elseif ($action === 'auditoria') {
        $loans = $db->table('loans')->join('books', 'books.id', '=', 'loans.book_id')->where('books.code', 'VIDEO-EMPR-01')
            ->select('loans.id', 'books.code', 'books.status', 'loans.returned_at')->orderBy('loans.id')->get();
        $audits = $db->table('loan_audits')->whereIn('loan_id', $loans->pluck('id'))->select('loan_id', 'operation')->orderBy('id')->get();
        echo json_encode(['emprestimos' => $loans, 'auditoria' => $audits], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
    } elseif ($action === 'api') {
        $user = App\Models\User::where('email', 'apresentacao@example.test')->where('role', 'admin')->firstOrFail();
        $token = bin2hex(random_bytes(32));
        $tokenId = $db->table('api_tokens')->insertGetId(['user_id' => $user->id, 'name' => 'Gravacao local', 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addMinutes(5), 'created_at' => now(), 'updated_at' => now()]);
        try {
            $client = Illuminate\Support\Facades\Http::withToken($token)->acceptJson()->timeout(15);
            $get = $client->get('http://127.0.0.1:8002/api/livros');
            echo 'GET /api/livros — HTTP '.$get->status()."\n";
            // O token só existe no banco local guardado acima. Se outro servidor
            // estiver nesta porta, não realizar a escrita após falha de autenticação.
            if ($get->status() !== 200) { throw new RuntimeException; }
            $json = $get->json();
            echo json_encode(['data' => array_slice($json['data'] ?? [], 0, 1), 'meta' => $json['meta'] ?? null], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
            $data = ['title' => 'Livro criado pela API no vídeo', 'author' => 'Autora Fictícia', 'code' => 'API-VIDEO-'.strtoupper(bin2hex(random_bytes(3))), 'publication_year' => 2020, 'category_id' => App\Models\Category::where('code', 'LIT')->firstOrFail()->id, 'status' => 'disponivel'];
            echo "POST /api/livros — corpo enviado:\n".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
            $post = $client->post('http://127.0.0.1:8002/api/livros', $data);
            echo 'Resposta HTTP '.$post->status()."\n".json_encode($post->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
            if ($get->status() !== 200 || $post->status() !== 201) { throw new RuntimeException; }
        } finally { $db->table('api_tokens')->where('id', $tokenId)->delete(); }
    } else { throw new RuntimeException; }
} catch (Throwable $exception) {
    echo "Operação interrompida: confira ambiente LOCAL de gravação, conta fictícia e servidor 8002. Credenciais omitidas.\n";
    exit(1);
}
