<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\QrisTransaction;
use App\Models\Sale;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransService
{
    /**
     * Resolves credentials for a store strictly from the store_payment_gateways table.
     */
    public function getCredentials(?Store $store = null): array
    {
        $gatewayConfig = null;
        if ($store) {
            $gatewayConfig = \App\Models\StorePaymentGateway::where('store_id', $store->id)
                ->where('gateway', 'midtrans')
                ->where('is_active', true)
                ->first();
        }

        $serverKey = $gatewayConfig?->server_key;
        $clientKey = $gatewayConfig?->client_key;
        $merchantId = $gatewayConfig?->merchant_id;
        $isProduction = (bool) ($gatewayConfig?->is_production ?? true);

        $baseUrl = $isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';

        return [
            'server_key'     => $serverKey,
            'client_key'     => $clientKey,
            'merchant_id'    => $merchantId,
            'is_production'  => $isProduction,
            'base_url'       => $baseUrl,
            'gateway_config' => $gatewayConfig,
        ];
    }

    /**
     * Charge dynamic QRIS via Midtrans Core API.
     */
    public function chargeQris(Store $store, string $orderId, float $amount, ?int $saleId = null, array $customDetails = []): QrisTransaction
    {
        $creds = $this->getCredentials($store);

        if (empty($creds['server_key'])) {
            throw new \Exception('Midtrans Server Key belum dikonfigurasi untuk toko ini.');
        }

        $grossAmount = (int) round($amount);

        $payload = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'qris' => [
                'acquirer' => 'gopay',
            ],
        ];

        if (!empty($customDetails['customer_name'])) {
            $payload['customer_details'] = [
                'first_name' => $customDetails['customer_name'],
                'phone'      => $customDetails['customer_phone'] ?? null,
            ];
        }

        $response = Http::withBasicAuth($creds['server_key'], '')
            ->withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($creds['base_url'] . '/charge', $payload);

        if (!$response->successful()) {
            $errBody = $response->json();
            $msg = $errBody['status_message'] ?? $response->body();
            Log::error("Midtrans QRIS Charge Failed [{$orderId}]: {$msg}");
            throw new \Exception("Gagal membuat QRIS Dinamis Midtrans: {$msg}");
        }

        $data = $response->json();

        // Extract QR string & QR URL
        $qrString = $data['qr_string'] ?? null;
        $qrUrl = null;
        if (!empty($data['actions']) && is_array($data['actions'])) {
            foreach ($data['actions'] as $action) {
                if (($action['name'] ?? '') === 'generate-qr-code') {
                    $qrUrl = $action['url'] ?? null;
                    break;
                }
            }
        }

        return QrisTransaction::create([
            'store_id'           => $store->id,
            'sale_id'            => $saleId,
            'order_id'           => $orderId,
            'gross_amount'       => $grossAmount,
            'qr_string'          => $qrString,
            'qr_url'             => $qrUrl,
            'transaction_id'     => $data['transaction_id'] ?? null,
            'transaction_status' => $data['transaction_status'] ?? 'pending',
            'raw_response'       => $data,
        ]);
    }

    /**
     * Check payment status directly from Midtrans API and update local DB if settled.
     */
    public function checkStatus(string $orderId): ?QrisTransaction
    {
        $qrisTx = QrisTransaction::with(['store', 'sale'])->where('order_id', $orderId)->first();
        if (!$qrisTx) {
            return null;
        }

        $creds = $this->getCredentials($qrisTx->store);
        if (empty($creds['server_key'])) {
            return $qrisTx;
        }

        try {
            $response = Http::withBasicAuth($creds['server_key'], '')
                ->withHeaders(['Accept' => 'application/json'])
                ->get($creds['base_url'] . "/{$orderId}/status");

            if ($response->successful()) {
                $payload = $response->json();
                $this->processStatusUpdate($qrisTx, $payload);
            }
        } catch (\Throwable $e) {
            Log::warning("Midtrans checkStatus error [{$orderId}]: " . $e->getMessage());
        }

        return $qrisTx->fresh(['sale']);
    }

    /**
     * Handle incoming webhook notification from Midtrans.
     */
    public function handleNotification(array $payload): array
    {
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (!$orderId) {
            return ['success' => false, 'message' => 'order_id tidak ditemukan'];
        }

        $qrisTx = QrisTransaction::with(['store', 'sale'])->where('order_id', $orderId)->first();

        // Cari credentials berdasarkan store
        $store = $qrisTx?->store;
        $creds = $this->getCredentials($store);
        $serverKey = $creds['server_key'];

        // Validasi SHA-512 Signature
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if ($expectedSignature !== $signatureKey) {
            Log::warning("Midtrans Webhook: Invalid Signature for Order [{$orderId}]. Received: {$signatureKey}, Expected: {$expectedSignature}");
            return ['success' => false, 'message' => 'Signature tidak valid'];
        }

        if ($qrisTx) {
            $this->processStatusUpdate($qrisTx, $payload);
        } else {
            Log::info("Midtrans Webhook: QrisTransaction [{$orderId}] not found in DB.");
        }

        return ['success' => true, 'message' => 'Notification processed successfully'];
    }

    /**
     * Internal helper to update QrisTransaction and Sale upon status change.
     */
    protected function processStatusUpdate(QrisTransaction $qrisTx, array $payload): void
    {
        $transactionStatus = $payload['transaction_status'] ?? 'pending';
        $transactionId = $payload['transaction_id'] ?? $qrisTx->transaction_id;

        $updates = [
            'transaction_status' => $transactionStatus,
            'transaction_id'     => $transactionId,
            'raw_notification'   => $payload,
        ];

        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            $updates['settlement_time'] = !empty($payload['settlement_time'])
                ? $payload['settlement_time']
                : now();
        }

        $qrisTx->update($updates);

        // Jika lunas (settlement / capture), update Sale terkait jika ada
        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            $sale = $qrisTx->sale;
            if (!$sale && $qrisTx->sale_id) {
                $sale = Sale::find($qrisTx->sale_id);
            }

            if ($sale && $sale->payment_status !== 'lunas') {
                \DB::transaction(function () use ($sale, $payload, $qrisTx) {
                    $gross = (float) ($payload['gross_amount'] ?? $sale->grand_total);

                    // 1. Update status transaksi
                    $sale->update([
                        'status'         => 'paid',
                        'payment_status' => 'lunas',
                        'paid_amount'    => $gross,
                        'change_amount'  => 0,
                    ]);

                    // 2. Catat CashTransaction (pembayaran QRIS) jika belum ada
                    $exists = CashTransaction::where('ref_id', $sale->id)
                        ->where('transaction_type', 'sale')
                        ->where('payment_method', 'qris')
                        ->exists();

                    if (!$exists) {
                        CashTransaction::create([
                            'store_id'         => $sale->store_id,
                            'cash_register_id' => $sale->cash_register_id,
                            'transaction_type' => 'sale',
                            'payment_method'   => 'qris',
                            'direction'        => 'in',
                            'nominal'          => $gross,
                            'ref_type'         => 'Sale',
                            'ref_id'           => $sale->id,
                            'tanggal'          => now(),
                            'notes'            => 'Pembayaran QRIS Dinamis Midtrans (' . $qrisTx->order_id . ')',
                        ]);
                    }

                    // 3. Loyalty point reward
                    try {
                        app(LoyaltyPointService::class)->awardPointsForSale($sale);
                    } catch (\Throwable $e) {
                        Log::warning("Award loyalty points on QRIS paid failed: " . $e->getMessage());
                    }

                    // 4. Sinkronisasi status Firestore jika F&B self service
                    try {
                        if ($sale->store && $sale->store->business_type === 'fnb' && $sale->store->addon_self_service) {
                            app(FirestoreService::class)->syncOrder($sale);
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Firestore sync on QRIS paid failed: " . $e->getMessage());
                    }
                });

                Log::info("Sale #{$sale->id} ({$sale->invoice_number}) successfully marked as LUNAS via Midtrans QRIS [{$qrisTx->order_id}]");
            }
        }
    }
}
