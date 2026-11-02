<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Contas de demonstração permitidas apenas em local/testing.');
        }
        foreach (['admin' => 'Administrador', 'bibliotecario' => 'Bibliotecário', 'consulta' => 'Consulta'] as $role => $name) {
            $user = User::firstOrNew(['email' => $role.'@biblioteca.test']);
            if ($user->exists) { continue; }
            $user->name = $name;
            $user->password = Hash::make('Biblioteca@123');
            $user->role = $role;
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
