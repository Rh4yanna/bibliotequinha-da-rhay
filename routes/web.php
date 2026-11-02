<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('emprestimos', \App\Http\Controllers\LoanController::class)->only(['index', 'create', 'store', 'show', 'update'])->parameters(['emprestimos' => 'loan'])->names('loans');
    Route::resource('livros', BookController::class)->parameters(['livros' => 'book'])->names('books');
    Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
    Route::middleware('role:admin')->group(function () {
        Route::resource('categorias', CategoryController::class)->except(['index', 'show'])->parameters(['categorias' => 'category'])->names('categories');
    });
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
require __DIR__.'/auth.php';
