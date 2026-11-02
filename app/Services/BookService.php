<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookService
{
    public function salvar(array $data, ?Book $book = null): Book
    {
        return DB::transaction(function () use ($data, $book) {
            if ($book) {
                $book = Book::whereKey($book->id)->lockForUpdate()->firstOrFail();
                if ($book->loans()->whereNull('returned_at')->exists() && ($data['status'] ?? $book->status) !== 'emprestado') {
                    throw ValidationException::withMessages(['status' => 'Registre a devolução antes de alterar a situação.']);
                }
                if (($data['status'] ?? $book->status) === 'emprestado' && $book->status !== 'emprestado') {
                    throw ValidationException::withMessages(['status' => 'Use Registrar empréstimo para emprestar o exemplar.']);
                }
                $book->update($data);
            } else {
                if (($data['status'] ?? null) === 'emprestado') {
                    throw ValidationException::withMessages(['status' => 'Cadastre o exemplar disponível e registre o empréstimo.']);
                }
                $book = Book::create($data);
            }

            return $book;
        });
    }

    public function excluir(Book $book): void
    {
        DB::transaction(function () use ($book) {
            $book = Book::whereKey($book->id)->lockForUpdate()->firstOrFail();
            if ($book->loans()->exists()) {
                throw ValidationException::withMessages(['book' => 'O exemplar possui histórico de empréstimos e deve ser preservado.']);
            }
            $book->delete();
        });
    }
}
