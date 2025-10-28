<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});

Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users');
