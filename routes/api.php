<?php

use App\Http\Controllers\Api\BookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.token', 'throttle:60,1'])->group(function () {
    Route::apiResource('livros', BookController::class)->parameters(['livros' => 'book']);
});
