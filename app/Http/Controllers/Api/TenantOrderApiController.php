<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemFnBDetail;
use App\Models\Tenant;
use App\Services\FirestoreService;
use App\Services\Printer\EscPosReceiptService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantOrderApiController extends Controller
{
    /**
     * Resolve tenant ID for the current authenticated user.
     */
    protected function getTenantId($user)
    {
        if ($user->tenant_id) {
            return $user->tenant_id;
        }

        // If user has a tenant relation or role STELLING
        if ($user->tenant) {
            return $user->tenant->id;
        }

        return null;
    }

    /**
     * GET /api/tenant/orders
     *
     * List active orders containing items belonging to the authenticated tenant.
     */
    public function orders(Request $request)
    {
        $user = $request->user();
        $tenantId = $this->getTenantId($user);

        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan tenant manapun.',
                'data'    => [],
            ], 403);
        }

        $storeId = $request->input('store_id');
        if (!$storeId) {
            $firstStore = $user->stores()->first();
            $storeId = $firstStore?->id;
        }

        // Fetch sales with hold or paid status that have items for this tenant
        $query = Sale::with([
            'items' => function ($q) {
                $q->with(['variant.product', 'fnbDetail']);
            },
            'store'
        ]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // Filter sales from today or active hold sales
        $query->where(function ($q) {
            $q->where('status', 'hold')
              ->orWhereDate('sale_date', Carbon::today());
        });

        $sales = $query->orderBy('created_at', 'desc')->get();

        $data = $sales->map(function ($sale) use ($tenantId) {
            // Filter only items belonging to this tenant
            $filteredItems = $sale->items->filter(function ($item) use ($tenantId) {
                return $item->variant?->product?->tenant_id == $tenantId;
            });

            if ($filteredItems->isEmpty()) {
                return null;
            }

            // Check overall tenant status for this sale
            $statuses = $filteredItems->map(fn($item) => $item->kds_status)->values();
            $allReady = $statuses->every(fn($s) => in_array($s, ['ready', 'served']));
            $anyCooking = $statuses->contains('cooking');
            $anyPending = $statuses->contains('pending');

            $orderStatus = 'pending';
            if ($allReady) {
                $orderStatus = 'ready';
            } elseif ($anyCooking) {
                $orderStatus = 'cooking';
            } elseif ($anyPending) {
                $orderStatus = 'pending';
            }

            return [
                'id'             => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'table_number'   => $sale->table_number ?? 'Takeaway',
                'customer_name'  => $sale->customer_name ?? 'Umum',
                'sale_status'    => $sale->status,
                'order_status'   => $orderStatus,
                'time_ago'       => $sale->created_at ? $sale->created_at->diffForHumans() : '-',
                'created_at'     => $sale->created_at ? $sale->created_at->format('Y-m-d H:i:s') : null,
                'items_count'    => $filteredItems->sum('qty'),
                'items'          => $filteredItems->map(function ($item) {
                    return [
                        'id'           => $item->id,
                        'name'         => $item->product_name,
                        'qty'          => (int) $item->qty,
                        'kds_status'   => $item->kds_status ?? 'pending',
                        'notes'        => $item->notes ?? '',
                        'price'        => (int) $item->price,
                    ];
                })->values()->toArray(),
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'tenant'  => [
                'id'   => $tenantId,
                'name' => $user->tenant?->nama_tenant ?? 'Tenant',
            ],
            'data'    => $data,
        ]);
    }

    /**
     * POST /api/tenant/orders/{saleId}/confirm
     *
     * Mark all pending items for this tenant in the specified sale as 'cooking'.
     */
    public function confirmOrder(Request $request, $saleId)
    {
        $user = $request->user();
        $tenantId = $this->getTenantId($user);

        if (!$tenantId) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $sale = Sale::findOrFail($saleId);

        $items = SaleItem::where('sale_id', $sale->id)
            ->whereHas('variant.product', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->get();

        foreach ($items as $item) {
            if ($item->kds_status === 'pending') {
                $item->kds_status = 'cooking';
                $item->save();
            }
        }

        $this->trySyncFirestore($sale);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dikonfirmasi dan mulai dimasak.',
        ]);
    }

    /**
     * POST /api/tenant/orders/{saleId}/ready
     *
     * Mark all items for this tenant in the specified sale as 'ready'.
     */
    public function readyOrder(Request $request, $saleId)
    {
        $user = $request->user();
        $tenantId = $this->getTenantId($user);

        if (!$tenantId) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $sale = Sale::findOrFail($saleId);

        $items = SaleItem::where('sale_id', $sale->id)
            ->whereHas('variant.product', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->get();

        foreach ($items as $item) {
            $item->kds_status = 'ready';
            $item->save();
        }

        $this->trySyncFirestore($sale);

        return response()->json([
            'success' => true,
            'message' => 'Semua menu tenant berhasil ditandai selesai/siap saji.',
        ]);
    }

    /**
     * POST /api/tenant/items/{itemId}/status
     *
     * Update individual item status ('pending', 'cooking', 'ready', 'served').
     */
    public function updateItemStatus(Request $request, $itemId)
    {
        $user = $request->user();
        $tenantId = $this->getTenantId($user);

        $request->validate([
            'status' => 'required|in:pending,cooking,ready,served',
        ]);

        $item = SaleItem::with('variant.product', 'sale')->findOrFail($itemId);

        if ($item->variant?->product?->tenant_id != $tenantId) {
            return response()->json(['message' => 'Item ini bukan milik tenant Anda.'], 403);
        }

        $item->kds_status = $request->status;
        $item->save();

        if ($item->sale) {
            $this->trySyncFirestore($item->sale);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status item berhasil diperbarui.',
            'data'    => [
                'id'         => $item->id,
                'kds_status' => $item->kds_status,
            ],
        ]);
    }

    /**
     * GET /api/tenant/orders/{saleId}/receipt
     *
     * Generate base64 ESC/POS string for tenant kitchen slip printing.
     */
    public function receipt(Request $request, $saleId)
    {
        $user = $request->user();
        $tenantId = $this->getTenantId($user);

        if (!$tenantId) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $sale = Sale::with(['store', 'user', 'items.variant.product'])->findOrFail($saleId);
        $store = $sale->store;
        $tenant = Tenant::find($tenantId);

        $tenantItems = $sale->items->filter(function ($item) use ($tenantId) {
            return $item->variant?->product?->tenant_id == $tenantId;
        });

        if ($tenantItems->isEmpty()) {
            return response()->json(['message' => 'Tidak ada produk milik tenant ini dalam transaksi.'], 404);
        }

        $paper = $store?->printer_type ?? '80mm';
        $svc = new EscPosReceiptService($paper);

        $printData = [
            'is_tenant_slip' => true,
            'tenant' => [
                'id'   => $tenant?->id,
                'name' => $tenant?->nama_tenant ?? 'Tenant',
            ],
            'store' => [
                'name'    => $store?->name ?? 'RIMS POS',
                'address' => $store?->address,
                'phone'   => $store?->phone,
            ],
            'transaction' => [
                'invoice'      => $sale->invoice_number,
                'date'         => $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : date('d/m/Y H:i'),
                'cashier'      => $sale->user?->name ?? '-',
                'customer'     => $sale->customer_name ?? 'Umum',
                'table_number' => $sale->table_number ?? 'Takeaway',
            ],
            'items' => $tenantItems->map(function ($item) {
                return [
                    'name'  => $item->product_name,
                    'qty'   => (int) $item->qty,
                    'notes' => $item->notes ?? '',
                ];
            })->values()->toArray(),
            'summary' => [
                'total_qty' => $tenantItems->sum('qty'),
            ],
        ];

        $base64 = $svc->base64($printData);

        return response()->json([
            'success' => true,
            'base64'  => $base64,
        ]);
    }

    /**
     * GET /api/tenant/history
     *
     * History and daily summary of completed items today for the tenant.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $tenantId = $this->getTenantId($user);

        if (!$tenantId) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $today = Carbon::today();
        $items = SaleItem::with(['sale', 'variant.product'])
            ->whereHas('variant.product', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->whereHas('sale', function ($q) use ($today) {
                $q->whereDate('sale_date', $today);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPortions = $items->sum('qty');
        $totalRevenue = $items->sum(fn($i) => $i->price * $i->qty);

        $groupedByMenu = $items->groupBy('product_name')->map(function ($group, $menuName) {
            return [
                'menu_name'     => $menuName,
                'total_qty'     => $group->sum('qty'),
                'total_revenue' => $group->sum(fn($i) => $i->price * $i->qty),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'summary' => [
                'date'           => $today->format('d M Y'),
                'total_orders'   => $items->pluck('sale_id')->unique()->count(),
                'total_portions' => $totalPortions,
                'total_revenue'  => $totalRevenue,
            ],
            'menu_breakdown' => $groupedByMenu,
        ]);
    }

    /**
     * Helper to sync order to Firestore if applicable.
     */
    protected function trySyncFirestore(Sale $sale)
    {
        try {
            $store = $sale->store;
            if ($store && $store->business_type === 'fnb' && $store->addon_self_service) {
                app(FirestoreService::class)->syncOrder($sale);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to sync sale #{$sale->id} to Firestore from Tenant API: " . $e->getMessage());
        }
    }
}
