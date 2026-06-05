<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use App\Services\FirestoreService;

class KitchenController extends Controller
{
    /**
     * Display the Kitchen Display System page.
     */
    public function index()
    {
        return view('kitchen.index');
    }

    /**
     * Fetch all active orders/items for KDS.
     * Orders are sales with status 'hold'.
     */
    public function orders(Request $request)
    {
        $user = auth()->user();
        $role = activeRole();
        $isStelling = $role && $role->role_type === 'STELLING';

        // Find sales with 'hold' status for current store that have been approved (user_id is not null)
        $query = Sale::with(['items.variant.product', 'items.fnbDetail'])
            ->where('store_id', session('store_id'))
            ->where('status', 'hold')
            ->whereNotNull('user_id');

        $sales = $query->orderBy('sale_date', 'asc')->get();

        $data = $sales->map(function ($sale) use ($user, $isStelling) {
            // Filter items: if user is STELLING (tenant specific koki), show only items belonging to their tenant_id
            $filteredItems = $sale->items->filter(function ($item) use ($user, $isStelling) {
                if ($isStelling) {
                    return $item->variant?->product?->tenant_id == $user->tenant_id;
                }
                return true;
            });

            // If there are no items for this tenant in this sale, skip this sale from display
            if ($filteredItems->isEmpty()) {
                return null;
            }

            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'table_number' => $sale->table_number ?? '-',
                'customer_name' => $sale->customer_name ?? 'Umum',
                'time_ago' => $sale->sale_date->diffForHumans(),
                'items' => $filteredItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->product_name,
                        'qty' => $item->qty,
                        'kds_status' => $item->kds_status,
                        'notes' => $item->notes,
                    ];
                })->values()->toArray(),
            ];
        })->filter()->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Mark a single item as ready/cooking.
     */
    public function markItemReady(Request $request, $id)
    {
        $item = SaleItem::findOrFail($id);
        $status = $request->input('status', 'ready'); // cooking or ready
        
        if (!in_array($status, ['pending', 'cooking', 'ready', 'served'])) {
            return response()->json(['message' => 'Status tidak valid'], 422);
        }

        $item->update([
            'kds_status' => $status
        ]);

        $sale = $item->sale;
        if ($sale) {
            try {
                $store = $sale->store;
                if ($store && $store->business_type === 'fnb' && $store->addon_self_service) {
                    app(FirestoreService::class)->syncOrder($sale);
                }
            } catch (\Throwable $e) {
                \Log::error("Failed to sync sale #{$sale->id} to Firestore from KDS item ready: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Status item berhasil diperbarui',
            'data' => [
                'id' => $item->id,
                'kds_status' => $item->kds_status
            ]
        ]);
    }

    /**
     * Mark all items in a sale (optionally filtered by tenant) as ready.
     */
    public function markSaleReady(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);
        $user = auth()->user();
        $role = activeRole();
        $isStelling = $role && $role->role_type === 'STELLING';
        
        $query = SaleItem::where('sale_id', $sale->id);
        
        if ($isStelling) {
            $query->whereHas('variant.product', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);
            });
        }
        
        $saleItemIds = $query->pluck('id')->toArray();
        \App\Models\SaleItemFnBDetail::whereIn('sale_item_id', $saleItemIds)->update(['kds_status' => 'ready']);

        try {
            $store = $sale->store;
            if ($store && $store->business_type === 'fnb' && $store->addon_self_service) {
                app(FirestoreService::class)->syncOrder($sale);
            }
        } catch (\Throwable $e) {
            \Log::error("Failed to sync sale #{$sale->id} to Firestore from KDS sale ready: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Semua pesanan meja ini berhasil ditandai selesai',
        ]);
    }
}
