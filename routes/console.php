<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('api:token {email} {--revoke : Revogar todos os tokens desta conta}', function () {
    $user = \App\Models\User::where('email', $this->argument('email'))->firstOrFail();
    if ($this->option('revoke')) {
        \Illuminate\Support\Facades\DB::table('api_tokens')->where('user_id', $user->id)->delete();
        $this->info('Tokens revogados.');
        return;
    }
    $token = bin2hex(random_bytes(32));
    \Illuminate\Support\Facades\DB::table('api_tokens')->insert([
        'user_id' => $user->id, 'name' => 'CLI', 'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDays(7), 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->info('Token válido por 7 dias. Guarde em local seguro; não será exibido novamente.');
    $this->line($token);
})->purpose('Emitir ou revogar tokens de API sem expor senhas');
