<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $book = $this->route('book');

        return $book
            ? $this->user()->can('update', $book)
            : $this->user()->can('create', Book::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => mb_strtoupper(trim((string) $this->code))]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'author' => ['required', 'string', 'min:3', 'max:150'],
            'code' => ['required', 'alpha_dash:ascii', 'max:20', Rule::unique('books')->ignore($this->route('book'))],
            'publication_year' => ['required', 'integer', 'between:1450,'.now()->year],
            'publisher' => ['nullable', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'status' => ['required', Rule::in(array_keys(Book::STATUSES))],
            'synopsis' => ['nullable', 'string', 'max:3000'],
        ];
        if ($this->isMethod('PATCH')) {
            foreach ($rules as &$fieldRules) { array_unshift($fieldRules, 'sometimes'); }
        }
        return $rules;
    }

    public function attributes(): array
    {
        return ['title' => 'título', 'author' => 'autor', 'code' => 'código do exemplar', 'publication_year' => 'ano de publicação', 'publisher' => 'editora', 'category_id' => 'categoria', 'status' => 'situação', 'synopsis' => 'sinopse'];
    }
}
