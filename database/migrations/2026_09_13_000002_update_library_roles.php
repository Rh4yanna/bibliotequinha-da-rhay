<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        }
        Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('consulta')->change());
        DB::table('users')->where('role', 'secretaria')->update(['role' => 'bibliotecario']);
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'bibliotecario', 'consulta'))");
        } else {
            Schema::table('users', fn (Blueprint $table) => $table->enum('role', ['admin', 'bibliotecario', 'consulta'])->default('consulta')->change());
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        }
        Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('consulta')->change());
        DB::table('users')->where('role', 'bibliotecario')->update(['role' => 'secretaria']);
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'secretaria', 'consulta'))");
        } else {
            Schema::table('users', fn (Blueprint $table) => $table->enum('role', ['admin', 'secretaria', 'consulta'])->default('consulta')->change());
        }
    }
};
