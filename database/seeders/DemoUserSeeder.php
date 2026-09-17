<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin' => 'Administrador', 'bibliotecario' => 'Bibliotecário', 'consulta' => 'Consulta'] as $role => $name) {
            $user = User::firstOrNew(['email' => $role.'@biblioteca.test']);
            $user->name = $name;
            $user->password = Hash::make('Biblioteca@123');
            $user->role = $role;
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
