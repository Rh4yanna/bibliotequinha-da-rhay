<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', ['categories' => Category::withCount('books')->orderBy('name')->paginate(12)]);
    }

    public function create()
    {
        return view('categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request)
    {
        Category::create($request->validated());

        return to_route('categories.index')->with('success', 'Categoria cadastrada com sucesso.');
    }

    public function edit(Category $category)
    {
        return view('categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return to_route('categories.index')->with('success', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Category $category)
    {
        if ($category->books()->exists()) {
            return back()->withErrors(['category' => 'Esta categoria possui livros. Reclassifique ou exclua os livros antes de excluir a categoria.']);
        }
        $category->delete();

        return to_route('categories.index')->with('success', 'Categoria excluída com sucesso.');
    }
}
