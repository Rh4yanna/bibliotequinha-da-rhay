<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(file_get_contents(database_path('sql/01_library_objects.sql')));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') { return; }
        DB::unprepared('DROP TRIGGER IF EXISTS books_protect_status ON books;
            DROP FUNCTION IF EXISTS trg_proteger_situacao_livro();
            DROP TRIGGER IF EXISTS loans_audit ON loans;
            DROP TRIGGER IF EXISTS loans_validate ON loans;
            DROP PROCEDURE IF EXISTS sp_registrar_devolucao(bigint);
            DROP PROCEDURE IF EXISTS sp_registrar_emprestimo(bigint,bigint,date);
            DROP FUNCTION IF EXISTS trg_auditar_emprestimo();
            DROP FUNCTION IF EXISTS trg_validar_emprestimo();
            DROP FUNCTION IF EXISTS fn_livro_disponivel(bigint);
            DROP FUNCTION IF EXISTS fn_quantidade_emprestimos_ativos(bigint);
            DROP INDEX IF EXISTS loans_one_active_copy_idx;
            DROP INDEX IF EXISTS loans_active_user_idx;
            DROP INDEX IF EXISTS books_title_order_idx;');
    }
};
