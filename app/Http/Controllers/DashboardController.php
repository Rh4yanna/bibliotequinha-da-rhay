<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'total' => Book::count(),
            'available' => Book::where('status', 'disponivel')->count(),
            'categoriesCount' => Category::count(),
            'borrowed' => Book::where('status', 'emprestado')->count(),
            'recent' => Book::with('category')->latest('id')->limit(5)->get(),
            'categories' => Category::withCount('books')->orderBy('name')->get(),
        ]);
    }
}
