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
                $q->with(['variant.product', 'product', 'fnbDetail']);
            },
            'store'
        ]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $today = Carbon::today();

        // Filter active hold sales or today's paid sales (exclude void or cancelled)
        $query->whereNotIn('status', ['void', 'cancelled'])
            ->where(function ($q) use ($today, $tenantId) {
                $q->where('status', 'hold')
                    ->orWhere(function ($q2) use ($today, $tenantId) {
                        $q2->where('status', 'paid')
                            ->where(function ($q3) use ($today, $tenantId) {
                                $q3->whereDate('sale_date', $today)
                                    ->orWhereDate('created_at', $today)
                                    ->orWhereDate('updated_at', $today)
                                    ->orWhereHas('items', function ($qi) use ($today, $tenantId) {
                                        $qi->whereDate('created_at', $today)
                                            ->whereNotIn('status', ['voided', 'refunded'])
                                            ->where(function ($qt) use ($tenantId) {
                                                $qt->whereHas('variant.product', fn($qp) => $qp->where('tenant_id', $tenantId))
                                                    ->orWhereHas('product', fn($qp) => $qp->where('tenant_id', $tenantId));
                                            });
                                    });
                            });
                    });
            });

        $sales = $query->orderBy('created_at', 'desc')->get();

        $data = $sales->map(function ($sale) use ($tenantId) {
            // Filter only items belonging to this tenant (exclude voided/refunded items)
            $filteredItems = $sale->items->filter(function ($item) use ($tenantId) {
                if (in_array($item->status, ['voided', 'refunded'])) {
                    return false;
                }
                $itemTenant = $item->variant?->product?->tenant_id ?? $item->product?->tenant_id;
                return $itemTenant == $tenantId;
            });

            if ($filteredItems->isEmpty()) {
                return null;
            }

            // Check overall tenant status for this sale
            $statuses = $filteredItems->map(fn($item) => $item->kds_status)->values();
            $allReady = $statuses->every(fn($s) => in_array($s, ['ready', 'served']));
            $anyPending = $statuses->contains('pending');
            $anyCooking = $statuses->contains('cooking');

            if ($allReady) {
                $orderStatus = 'ready';
            } elseif ($anyPending) {
                $orderStatus = 'pending';
            } elseif ($anyCooking) {
                $orderStatus = 'cooking';
            } else {
                $orderStatus = 'ready';
            }

            $latestItemTime = $filteredItems->max('created_at') ?: $sale->created_at;
            $orderTime = $latestItemTime ? Carbon::parse($latestItemTime) : $sale->created_at;

            return [
                'id'             => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'table_number'   => $sale->table_number ?? 'Takeaway',
                'customer_name'  => $sale->customer_name ?? 'Umum',
                'sale_status'    => $sale->status,
                'order_status'   => $orderStatus,
                'time_ago'       => $orderTime ? $orderTime->diffForHumans() : '-',
                'created_at'     => $orderTime ? $orderTime->format('Y-m-d H:i:s') : null,
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

        if (in_array($sale->status, ['void', 'cancelled'])) {
            return response()->json(['message' => 'Pesanan ini telah dibatalkan.'], 422);
        }

        $items = SaleItem::where('sale_id', $sale->id)
            ->where(function ($q) use ($tenantId) {
                $q->whereHas('variant.product', function ($q2) use ($tenantId) {
                    $q2->where('tenant_id', $tenantId);
                })->orWhereHas('product', function ($q2) use ($tenantId) {
                    $q2->where('tenant_id', $tenantId);
                });
            })
            ->get();

        foreach ($items as $item) {
            if ($item->kds_status === 'pending') {
                $item->kds_status = 'cooking';
                $item->save();
                \App\Models\SaleItemFnBDetail::updateOrCreate(
                    ['sale_item_id' => $item->id],
                    ['kds_status' => 'cooking']
                );
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

        if (in_array($sale->status, ['void', 'cancelled'])) {
            return response()->json(['message' => 'Pesanan ini telah dibatalkan.'], 422);
        }

        $items = SaleItem::where('sale_id', $sale->id)
            ->where(function ($q) use ($tenantId) {
                $q->whereHas('variant.product', function ($q2) use ($tenantId) {
                    $q2->where('tenant_id', $tenantId);
                })->orWhereHas('product', function ($q2) use ($tenantId) {
                    $q2->where('tenant_id', $tenantId);
                });
            })
            ->get();

        foreach ($items as $item) {
            $item->kds_status = 'ready';
            $item->save();
            \App\Models\SaleItemFnBDetail::updateOrCreate(
                ['sale_item_id' => $item->id],
                ['kds_status' => 'ready']
            );
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

        $item = SaleItem::with(['variant.product', 'product', 'sale'])->findOrFail($itemId);

        if ($item->sale && in_array($item->sale->status, ['void', 'cancelled'])) {
            return response()->json(['message' => 'Pesanan ini telah dibatalkan.'], 422);
        }

        if (in_array($item->status, ['voided', 'refunded'])) {
            return response()->json(['message' => 'Item ini sudah dibatalkan/void.'], 422);
        }

        $itemTenant = $item->variant?->product?->tenant_id ?? $item->product?->tenant_id;
        if ($itemTenant != $tenantId) {
            return response()->json(['message' => 'Item ini bukan milik tenant Anda.'], 403);
        }

        $item->kds_status = $request->status;
        $item->save();
        \App\Models\SaleItemFnBDetail::updateOrCreate(
            ['sale_item_id' => $item->id],
            ['kds_status' => $request->status]
        );

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

        $sale = Sale::with(['store', 'user', 'items.variant.product', 'items.product'])->findOrFail($saleId);
        $store = $sale->store;
        $tenant = Tenant::find($tenantId);

        $tenantItems = $sale->items->filter(function ($item) use ($tenantId) {
            if (in_array($item->status, ['voided', 'refunded'])) {
                return false;
            }
            $itemTenant = $item->variant?->product?->tenant_id ?? $item->product?->tenant_id;
            return $itemTenant == $tenantId;
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

        $storeId = $request->input('store_id');
        if (!$storeId) {
            $firstStore = $user->stores()->first();
            $storeId = $firstStore?->id;
        }

        $today = Carbon::today();
        $items = SaleItem::with(['sale', 'variant.product', 'product', 'fnbDetail'])
            ->whereNotIn('status', ['voided', 'refunded'])
            ->where(function ($q) use ($tenantId) {
                $q->whereHas('variant.product', function ($q2) use ($tenantId) {
                    $q2->where('tenant_id', $tenantId);
                })->orWhereHas('product', function ($q2) use ($tenantId) {
                    $q2->where('tenant_id', $tenantId);
                });
            })
            ->whereHas('sale', function ($q) use ($storeId) {
                $q->whereNotIn('status', ['void', 'cancelled']);
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
            })
            ->where(function ($q) use ($today) {
                $q->whereDate('created_at', $today)
                    ->orWhereHas('sale', function ($sq) use ($today) {
                        $sq->whereDate('sale_date', $today);
                    });
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
     * GET /api/tenant/sales-history
     *
     * Detailed sales history for authenticated tenant with date range filtering,
     * pagination, search, financial metrics (gross sales, commissions, net payout),
     * and menu breakdown.
     */
    public function salesHistory(Request $request)
    {
        $user = $request->user();
        $tenantId = $this->getTenantId($user);

        if (!$tenantId) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $from = $request->input('from', Carbon::today()->format('Y-m-d'));
        $to = $request->input('to', Carbon::today()->format('Y-m-d'));
        $startRange = $from . ' 00:00:00';
        $endRange = $to . ' 23:59:59';
        $search = trim($request->input('search', ''));
        $paymentStatus = $request->input('payment_status', 'all');
        $storeId = $request->input('store_id');
        if (!$storeId) {
            $firstStore = $user->stores()->first();
            $storeId = $firstStore?->id;
        }
        $perPage = (int) $request->input('per_page', 20);

        $tenantItemScope = function ($q) use ($tenantId) {
            $q->whereHas('variant.product', function ($q2) use ($tenantId) {
                $q2->where('tenant_id', $tenantId);
            })->orWhereHas('product', function ($q2) use ($tenantId) {
                $q2->where('tenant_id', $tenantId);
            });
        };

        $itemDateScope = function ($q) use ($startRange, $endRange) {
            $q->whereBetween('created_at', [$startRange, $endRange])
                ->orWhereHas('sale', function ($sq) use ($startRange, $endRange) {
                    $sq->whereBetween('sale_date', [$startRange, $endRange]);
                });
        };

        // 1. Overall Summary across the entire filtered period
        $summaryItems = SaleItem::with(['variant.product', 'product', 'fnbDetail'])
            ->whereNotIn('status', ['voided', 'refunded'])
            ->where($tenantItemScope)
            ->where($itemDateScope)
            ->whereHas('sale', function ($q) use ($storeId, $paymentStatus, $search) {
                $q->whereNotIn('status', ['void', 'cancelled']);
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
                if ($paymentStatus && $paymentStatus !== 'all') {
                    $q->where('status', $paymentStatus);
                }
                if ($search !== '') {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('invoice_number', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%")
                            ->orWhere('table_number', 'like', "%{$search}%");
                    });
                }
            })
            ->get();

        $totalOrders = $summaryItems->pluck('sale_id')->unique()->count();
        $totalPortions = (int) $summaryItems->sum('qty');
        $grossSales = 0;
        $totalCommission = 0;

        foreach ($summaryItems as $item) {
            $subtotal = $item->price * $item->qty;
            $commAmount = $item->commission_amount ?? ($item->variant ? $item->variant->calculateCommission($item->price) : 0);
            $commission = $commAmount * $item->qty;
            $grossSales += $subtotal;
            $totalCommission += $commission;
        }
        $netPayout = $grossSales - $totalCommission;

        // Menu breakdown for the selected period
        $menuBreakdown = $summaryItems->groupBy('product_name')->map(function ($group, $name) {
            return [
                'menu_name'     => $name,
                'total_qty'     => (int) $group->sum('qty'),
                'total_revenue' => (int) $group->sum(fn($i) => $i->price * $i->qty),
            ];
        })->values()->sortByDesc('total_qty')->values();

        // 2. Paginated Sales List
        $salesQuery = Sale::with([
            'items' => function ($q) use ($tenantItemScope, $itemDateScope) {
                $q->whereNotIn('status', ['voided', 'refunded'])
                    ->where($tenantItemScope)
                    ->where($itemDateScope)
                    ->with(['variant.product', 'product', 'fnbDetail']);
            },
            'store'
        ])
            ->whereNotIn('status', ['void', 'cancelled'])
            ->whereHas('items', function ($q) use ($tenantItemScope, $itemDateScope) {
                $q->whereNotIn('status', ['voided', 'refunded'])
                    ->where($tenantItemScope)
                    ->where($itemDateScope);
            });

        if ($storeId) {
            $salesQuery->where('store_id', $storeId);
        }

        if ($paymentStatus && $paymentStatus !== 'all') {
            $salesQuery->where('status', $paymentStatus);
        }

        if ($search !== '') {
            $salesQuery->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('table_number', 'like', "%{$search}%");
            });
        }

        $paginatedSales = $salesQuery->orderBy('updated_at', 'desc')->orderBy('sale_date', 'desc')->paginate($perPage);

        $salesData = collect($paginatedSales->items())->map(function ($sale) use ($tenantId) {
            $items = $sale->items->filter(function ($item) use ($tenantId) {
                return ($item->variant?->product?->tenant_id ?? $item->product?->tenant_id) == $tenantId;
            });

            $tenantTotal = (int) $items->sum(fn($i) => $i->price * $i->qty);
            $tenantPortions = (int) $items->sum('qty');
            $latestItemTime = $items->max('created_at');
            $displayDate = $latestItemTime
                ? Carbon::parse($latestItemTime)->format('Y-m-d H:i:s')
                : ($sale->sale_date ? $sale->sale_date->format('Y-m-d H:i:s') : ($sale->created_at ? $sale->created_at->format('Y-m-d H:i:s') : null));

            return [
                'id'              => $sale->id,
                'invoice_number'  => $sale->invoice_number,
                'sale_date'       => $displayDate,
                'table_number'    => $sale->table_number ?? 'Takeaway',
                'customer_name'   => $sale->customer_name ?? 'Umum',
                'payment_status'  => $sale->status,
                'tenant_total'    => $tenantTotal,
                'tenant_portions' => $tenantPortions,
                'items'           => $items->map(function ($item) {
                    return [
                        'id'         => $item->id,
                        'name'       => $item->product_name,
                        'variant'    => $item->variant?->variant_name,
                        'qty'        => (int) $item->qty,
                        'price'      => (int) $item->price,
                        'subtotal'   => (int) ($item->price * $item->qty),
                        'notes'      => $item->notes ?? '',
                        'kds_status' => $item->kds_status ?? 'pending',
                    ];
                })->values()->toArray(),
            ];
        });

        return response()->json([
            'success' => true,
            'tenant'  => [
                'id'   => $tenantId,
                'name' => $user->tenant?->nama_tenant ?? 'Tenant',
            ],
            'summary' => [
                'from'             => $from,
                'to'               => $to,
                'total_orders'     => $totalOrders,
                'total_portions'   => $totalPortions,
                'gross_sales'      => (int) $grossSales,
                'total_commission' => (int) $totalCommission,
                'net_payout'       => (int) $netPayout,
            ],
            'sales' => [
                'current_page' => $paginatedSales->currentPage(),
                'last_page'    => $paginatedSales->lastPage(),
                'per_page'     => $paginatedSales->perPage(),
                'total'        => $paginatedSales->total(),
                'data'         => $salesData,
            ],
            'menu_breakdown' => $menuBreakdown,
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
