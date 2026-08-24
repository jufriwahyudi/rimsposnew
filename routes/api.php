<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RewardRedemptionController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

// ── Auth (public) ────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/pin-login', [AuthController::class, 'pinLogin']);
});

// ── Auth (protected) ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);
    Route::post('/set-pin', [AuthController::class, 'setPin']);
});

// ── POS (protected + subscription check) ─────────────────────────────────────
Route::middleware(['auth:sanctum', 'check.subscription'])->group(function () {
    // ── Cash Register / Shift Kasir ──────────────────────────────────────────
    Route::get('/pos/cash-register/status',         [CashRegisterController::class, 'status']);
    Route::post('/pos/cash-register/open',          [CashRegisterController::class, 'open']);
    Route::get('/pos/cash-register/summary',        [CashRegisterController::class, 'summary']);
    Route::get('/pos/cash-register/receipt-report', [CashRegisterController::class, 'printReport']);
    Route::post('/pos/cash-register/movement',      [CashRegisterController::class, 'cashMovement']);
    Route::post('/pos/cash-register/close',         [CashRegisterController::class, 'close']);
    Route::get('/pos/expense-categories',           [PosController::class, 'apiExpenseCategories']);
    Route::get('/pos/categories',                   [PosController::class, 'apiCategories']);

    Route::get('/pos/product',                   [PosController::class, 'findProduct']);
    Route::post('/pos/voice-search',         [PosController::class, 'apiVoiceSearch']);
    Route::post('/pos/product/register-barcode', [PosController::class, 'apiRegisterBarcode']);
    Route::get('/pos/rekening',              [PosController::class, 'apiRekening']);
    Route::get('/pos/customers',             [PosController::class, 'apiCustomers']);
    Route::get('/pos/staff',                 [PosController::class, 'apiStaff']);
    Route::get('/pos/service-orders',        [PosController::class, 'apiServiceOrders']);
    Route::get('/pos/members',               [PosController::class, 'apiMembers']);
    Route::post('/pos/members',              [PosController::class, 'apiStoreMember']);
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

    // ── Reward Redemptions ──────────────────────────────────────────────────
    Route::get('/pos/reward-items',              [RewardRedemptionController::class, 'apiRewardItems']);
    Route::get('/pos/members/{id}/rewards',      [RewardRedemptionController::class, 'apiMemberRewards']);
    Route::post('/pos/members/{id}/redeem',      [RewardRedemptionController::class, 'apiRedeem']);
    Route::get('/pos/members/{id}/vouchers',     [RewardRedemptionController::class, 'apiMemberVouchers']);

    // ── Self-Service (POS Control) ───────────────────────────────────────────
    Route::get('/pos/self-service/pending', [PosController::class, 'apiPendingSelfService']);
    Route::post('/pos/self-service/{id}/confirm', [PosController::class, 'apiConfirmSelfService']);
    Route::post('/pos/self-service/{id}/decline', [PosController::class, 'apiDeclineSelfService']);

    // ── Tenant Order KDS & Thermal Printing ──────────────────────────────────
    Route::prefix('tenant')->group(function () {
        Route::get('/orders',                       [\App\Http\Controllers\Api\TenantOrderApiController::class, 'orders']);
        Route::post('/orders/{id}/confirm',         [\App\Http\Controllers\Api\TenantOrderApiController::class, 'confirmOrder']);
        Route::post('/orders/{id}/ready',           [\App\Http\Controllers\Api\TenantOrderApiController::class, 'readyOrder']);
        Route::post('/items/{id}/status',           [\App\Http\Controllers\Api\TenantOrderApiController::class, 'updateItemStatus']);
        Route::get('/orders/{id}/receipt',          [\App\Http\Controllers\Api\TenantOrderApiController::class, 'receipt']);
        Route::get('/history',                      [\App\Http\Controllers\Api\TenantOrderApiController::class, 'history']);
    });
});

// ── Self-Service Status (public fallback) ─────────────────────────────────────
Route::get('/order/status/{id}', [\App\Http\Controllers\CustomerSelfServiceController::class, 'statusApi']);

// ── App Update Check (public) ────────────────────────────────────────────────
Route::get('/app-version/latest', [\App\Http\Controllers\AppVersionController::class, 'latestApi']);
