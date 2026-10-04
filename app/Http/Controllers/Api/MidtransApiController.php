<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrisTransaction;
use App\Models\Sale;
use App\Models\Store;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MidtransApiController extends Controller
{
    protected MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * POST /api/pos/qris/generate
     * Generate a dynamic QRIS for a sale or cart amount.
     */
    public function generateQris(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'amount'   => 'required|numeric|min:1',
            'sale_id'  => 'nullable|integer|exists:sales,id',
        ]);

        $storeId = $request->integer('store_id');
        $store = Store::findOrFail($storeId);

        // Akses user ke store jika terotentikasi
        if (auth()->check()) {
            $hasAccess = (session('store_id') == $storeId)
                || auth()->user()->stores()->where('stores.id', $storeId)->exists()
                || (auth()->user()->is_admin ?? false);
            if (!$hasAccess) {
                return response()->json(['message' => 'Akses ke toko ini ditolak.'], 403);
            }
        }

        $saleId = $request->input('sale_id');
        $sale = $saleId ? Sale::where('store_id', $storeId)->find($saleId) : null;

        // Cek apakah sudah ada QRIS pending untuk sale ini dengan nominal yang sama
        if ($sale) {
            $existingQris = QrisTransaction::where('sale_id', $sale->id)
                ->where('gross_amount', (int) round($request->amount))
                ->where('transaction_status', 'pending')
                ->latest()
                ->first();

            $isProduction = (bool) $store->paymentGateways()->where('gateway', 'midtrans')->value('is_production');

            // Jika QRIS dibuat kurang dari 15 menit yang lalu dan belum expired, gunakan yang ada
            if ($existingQris && $existingQris->created_at->diffInMinutes(now()) < 15 && $existingQris->qr_string) {
                return response()->json([
                    'success' => true,
                    'message' => 'Menggunakan tagihan QRIS yang aktif.',
                    'data'    => [
                        'order_id'       => $existingQris->order_id,
                        'gross_amount'   => (float) $existingQris->gross_amount,
                        'qr_string'      => $existingQris->qr_string,
                        'qr_url'         => $existingQris->qr_url,
                        'transaction_id' => $existingQris->transaction_id,
                        'status'         => $existingQris->transaction_status,
                        'is_production'  => $isProduction,
                    ],
                ]);
            }
        }

        $isProduction = (bool) $store->paymentGateways()->where('gateway', 'midtrans')->value('is_production');

        // Buat Order ID unik: RIMS-{STORE_CODE}-{SALE_ID/TIME}-{RANDOM}
        $code = strtoupper($store->code ?: 'POS');
        $idPart = $sale ? $sale->id : time();
        $orderId = "QR-{$code}-{$idPart}-" . strtoupper(Str::random(4));

        $customDetails = [
            'customer_name'  => $sale?->customer_name ?: $request->input('customer_name', 'Pelanggan POS'),
            'customer_phone' => $sale?->customer_phone ?: $request->input('customer_phone'),
        ];

        try {
            $qrisTx = $this->midtransService->chargeQris(
                $store,
                $orderId,
                (float) $request->amount,
                $sale?->id,
                $customDetails
            );

            return response()->json([
                'success' => true,
                'message' => 'QRIS Dinamis berhasil dibuat.',
                'data'    => [
                    'order_id'       => $qrisTx->order_id,
                    'gross_amount'   => (float) $qrisTx->gross_amount,
                    'qr_string'      => $qrisTx->qr_string,
                    'qr_url'         => $qrisTx->qr_url,
                    'transaction_id' => $qrisTx->transaction_id,
                    'status'         => $qrisTx->transaction_status,
                    'is_production'  => $isProduction,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat QRIS: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pos/qris/status/{orderId}
     * Check real-time payment status of an order.
     */
    public function checkStatus($orderId)
    {
        $qrisTx = $this->midtransService->checkStatus($orderId);

        if (!$qrisTx) {
            return response()->json(['message' => 'Transaksi QRIS tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'order_id'           => $qrisTx->order_id,
                'gross_amount'       => (float) $qrisTx->gross_amount,
                'transaction_status' => $qrisTx->transaction_status,
                'is_paid'            => $qrisTx->isPaid(),
                'settlement_time'    => $qrisTx->settlement_time?->format('Y-m-d H:i:s'),
                'sale_id'            => $qrisTx->sale_id,
                'sale_status'        => $qrisTx->sale?->status,
                'payment_status'     => $qrisTx->sale?->payment_status,
            ],
        ]);
    }

    /**
     * POST /api/midtrans/notification
     * Webhook destination for Midtrans HTTP POST notifications.
     */
    public function handleNotification(Request $request)
    {
        $payload = $request->all();

        $result = $this->midtransService->handleNotification($payload);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
