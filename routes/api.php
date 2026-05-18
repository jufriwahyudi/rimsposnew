<?php

use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth:sanctum')->group(function () {
Route::get('/pos/product', [PosController::class, 'findProduct']);
// });
