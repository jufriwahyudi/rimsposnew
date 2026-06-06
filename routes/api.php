<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

// ── Auth (public) ────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// ── Auth (protected) ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);
});

// ── POS (protected + subscription check) ─────────────────────────────────────
Route::middleware(['auth:sanctum', 'check.subscription'])->group(function () {
    Route::get('/pos/product',               [PosController::class, 'findProduct']);
    Route::post('/pos/voice-search',         [PosController::class, 'apiVoiceSearch']);
    Route::post('/pos/product/register-barcode', [PosController::class, 'apiRegisterBarcode']);
    Route::get('/pos/rekening',              [PosController::class, 'apiRekening']);
    Route::get('/pos/customers',             [PosController::class, 'apiCustomers']);
    Route::get('/pos/members',               [PosController::class, 'apiMembers']);
    Route::post('/pos/checkout',             [PosController::class, 'apiCheckout']);
    Route::get('/pos/sales',                 [PosController::class, 'apiSales']);
    Route::get('/pos/sales/active-bills',    [PosController::class, 'apiActiveBills']);
    Route::post('/pos/sales/{id}/change-table', [PosController::class, 'apiChangeTable']);
    Route::post('/pos/sales/merge-bills',    [PosController::class, 'apiMergeBills']);
    Route::get('/pos/sales/{id}',            [PosController::class, 'apiSaleDetail']);
    Route::get('/pos/sales/{id}/receipt',    [PosController::class, 'apiReceipt']);
    Route::post('/pos/sales/{id}/mark-kitchen-printed', [PosController::class, 'apiMarkKitchenPrinted']);
    Route::post('/pos/sales/{id}/void',      [PosController::class, 'apiVoid']);
    Route::post('/pos/sales/{id}/refund',    [PosController::class, 'apiRefund']);
    Route::post('/pos/sales/{id}/pay',       [PosController::class, 'apiPayDebt']);
    Route::post('/pos/sales/{id}/exchange',  [PosController::class, 'apiExchange']);

    // ── Self-Service (POS Control) ───────────────────────────────────────────
    Route::get('/pos/self-service/pending', [PosController::class, 'apiPendingSelfService']);
    Route::post('/pos/self-service/{id}/confirm', [PosController::class, 'apiConfirmSelfService']);
    Route::post('/pos/self-service/{id}/decline', [PosController::class, 'apiDeclineSelfService']);
});

// ── Self-Service Status (public fallback) ─────────────────────────────────────
Route::get('/order/status/{id}', [\App\Http\Controllers\CustomerSelfServiceController::class, 'statusApi']);
