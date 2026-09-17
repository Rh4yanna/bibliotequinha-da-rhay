<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    protected $fillable = ['title', 'author', 'code', 'publication_year', 'publisher', 'status', 'category_id', 'synopsis'];

    public const STATUSES = ['disponivel' => 'Disponível', 'emprestado' => 'Emprestado', 'indisponivel' => 'Indisponível'];

    protected function casts(): array
    {
        return ['publication_year' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
