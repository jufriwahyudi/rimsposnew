<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TenantNotificationService
{
    /**
     * Notify tenants who have pending items in the given sale.
     *
     * @param Sale|int $sale
     * @return array Array of tenant notification results
     */
    public function notifyTenantsForSale($sale): array
    {
        try {
            if (is_numeric($sale)) {
                $sale = Sale::with(['items.variant.product', 'store'])->find($sale);
            } elseif ($sale instanceof Sale) {
                $sale->loadMissing(['items.variant.product', 'store']);
            }

            if (!$sale || !$sale->items) {
                return [];
            }

            // Group items by tenant_id
            $tenantItemGroups = [];
            foreach ($sale->items as $item) {
                // Check if item belongs to a tenant
                $tenantId = $item->variant?->product?->tenant_id;
                if ($tenantId) {
                    $kdsStatus = $item->kds_status ?? 'pending';
                    // We notify for pending items
                    if (in_array($kdsStatus, ['pending', 'unpaid', 'paid'])) {
                        $tenantItemGroups[$tenantId][] = $item;
                    }
                }
            }

            if (empty($tenantItemGroups)) {
                return [];
            }

            $results = [];
            foreach ($tenantItemGroups as $tenantId => $items) {
                $tenant = Tenant::find($tenantId);
                if (!$tenant) {
                    continue;
                }

                // Find users belonging to this tenant with active FCM token
                $tenantUsers = User::where('tenant_id', $tenantId)
                    ->whereNotNull('fcm_token')
                    ->where('fcm_token', '!=', '')
                    ->get();

                if ($tenantUsers->isEmpty()) {
                    Log::info("Tenant #{$tenantId} ({$tenant->nama_tenant}) has no users with FCM tokens registered.");
                    continue;
                }

                $fcmTokens = $tenantUsers->pluck('fcm_token')->unique()->values()->all();
                $itemCount = count($items);
                $tableNumber = $sale->table_number ? 'Meja ' . $sale->table_number : 'Takeaway';
                $title = "🔔 Pesanan Baru Tenant: {$tenant->nama_tenant}";
                $body = "{$tableNumber} (#{$sale->invoice_number}) - {$itemCount} menu menunggu diproses.";

                $data = [
                    'type'         => 'NEW_TENANT_ORDER',
                    'tenant_id'    => (string) $tenantId,
                    'tenant_name'  => (string) $tenant->nama_tenant,
                    'sale_id'      => (string) $sale->id,
                    'invoice'      => (string) $sale->invoice_number,
                    'table_number' => (string) ($sale->table_number ?? 'Takeaway'),
                    'item_count'   => (string) $itemCount,
                    'created_at'   => now()->toISOString(),
                    'priority'     => 'high',
                ];

                $sentCount = FirebaseService::sendHighPriorityToMany(
                    $fcmTokens,
                    $title,
                    $body,
                    $data,
                    'tenant_orders_channel',
                    'alert_beep'
                );

                $results[$tenantId] = [
                    'tenant'     => $tenant->nama_tenant,
                    'sentCount'  => $sentCount,
                    'tokenCount' => count($fcmTokens),
                ];

                Log::info("Sent tenant order notification to {$tenant->nama_tenant} ({$sentCount}/" . count($fcmTokens) . " devices)");
            }

            return $results;
        } catch (\Throwable $e) {
            Log::error("Failed to notify tenants for sale: " . $e->getMessage(), [
                'exception' => $e
            ]);
            return [];
        }
    }
}
