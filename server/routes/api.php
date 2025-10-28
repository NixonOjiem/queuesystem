<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

// ----------------------------------------------------------------------
// PUBLIC ROUTES (No token required)
// ----------------------------------------------------------------------

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/users', [UserController::class, 'index'])->name('users')->middleware('throttle:3,1');

// ----------------------------------------------------------------------
// PROTECTED ROUTES (Token required - this is the middleware you asked for!)
// ----------------------------------------------------------------------

Route::middleware('auth:sanctum')->group(function () {
    // Example protected route to get the user's details
    // Route::get('/users', [UserController::class, 'index'])->name('users');

    // Logout route (requires a valid token to delete itself)
    Route::post('/logout', [AuthController::class, 'logout']);
});
