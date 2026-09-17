<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Book::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', Rule::in(array_keys(Book::STATUSES))],
        ]);
        $books = Book::with('category')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(fn ($q) => $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(author) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$term]));
            })
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('title')->paginate(10)->withQueryString();

        return view('books.index', ['books' => $books, 'categories' => Category::orderBy('name')->get()]);
    }

    public function create()
    {
        Gate::authorize('create', Book::class);

        return view('books.form', ['book' => new Book, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(BookRequest $request)
    {
        $book = Book::create($request->validated());

        return to_route('books.show', $book)->with('success', 'Livro cadastrado com sucesso.');
    }

    public function show(Book $book)
    {
        Gate::authorize('view', $book);

        return view('books.show', ['book' => $book->load('category')]);
    }

    public function edit(Book $book)
    {
        Gate::authorize('update', $book);

        return view('books.form', ['book' => $book, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(BookRequest $request, Book $book)
    {
        $book->update($request->validated());

        return to_route('books.show', $book)->with('success', 'Livro atualizado com sucesso.');
    }

    public function destroy(Book $book)
    {
        Gate::authorize('delete', $book);
        $book->delete();

        return to_route('books.index')->with('success', 'Livro excluído com sucesso.');
    }
}
