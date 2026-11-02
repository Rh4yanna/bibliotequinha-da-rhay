<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Support\Facades\Gate;

class BookController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Book::class);
        return BookResource::collection(Book::with('category')->orderBy('title')->orderBy('id')->paginate(10));
    }

    public function show(Book $book)
    {
        Gate::authorize('view', $book);
        return new BookResource($book->load('category'));
    }

    public function store(BookRequest $request, BookService $service)
    {
        return (new BookResource($service->salvar($request->validated())->load('category')))->response()->setStatusCode(201);
    }

    public function update(BookRequest $request, Book $book, BookService $service)
    {
        return new BookResource($service->salvar($request->validated(), $book)->load('category'));
    }

    public function destroy(Book $book, BookService $service)
    {
        Gate::authorize('delete', $book);
        $service->excluir($book);
        return response()->noContent();
    }
}
