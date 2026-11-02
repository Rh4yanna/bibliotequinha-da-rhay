<?php

namespace App\Services;

use App\Models\Loan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanService
{
    public function quantidadeAtivos(int $userId): int
    {
        $this->exigirPostgres();
        return (int) DB::selectOne('SELECT fn_quantidade_emprestimos_ativos(?) AS total', [$userId])->total;
    }

    public function registrar(int $userId, int $bookId, string $dueAt): Loan
    {
        $this->exigirPostgres();
        return $this->executar(function () use ($userId, $bookId, $dueAt) {
            DB::statement('CALL sp_registrar_emprestimo(?, ?, CAST(? AS date))', [$userId, $bookId, $dueAt]);
            return Loan::where('user_id', $userId)->where('book_id', $bookId)->whereNull('returned_at')->firstOrFail();
        });
    }

    public function devolver(Loan $loan): void
    {
        $this->exigirPostgres();
        $this->executar(fn () => DB::statement('CALL sp_registrar_devolucao(?)', [$loan->id]));
    }

    private function exigirPostgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw ValidationException::withMessages(['loan' => 'Empréstimos exigem PostgreSQL. Configure a conexão e execute as migrations.']);
        }
    }

    private function executar(callable $operation): mixed
    {
        try {
            return DB::transaction($operation, 3);
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23514', '23505', '23503'], true)) {
                throw ValidationException::withMessages(['loan' => 'Operação recusada: confira disponibilidade, limite de 3 empréstimos, datas e se já houve devolução.']);
            }
            throw $exception;
        }
    }
}
