<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\CashTransaction;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $selectedRole = session('selected_role');
        $role = \App\Models\RoleMaster::find($selectedRole);

        if ($role && $role->role_type === 'SUPERADMIN' && !session('is_impersonating')) {
            return $this->indexSuperadmin();
        }

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Jumlah produk aktif
        $totalProducts = Product::count();
        $totalVariants = ProductVariant::where('is_active', 1)->count();

        // Stok gudang & store filter yang product_variants dengan store_id sesuai session
        $storeId = session('store_id');

        $stockData = DB::table('stock_movements as sm')
            ->join('product_variants as pv', 'pv.id', '=', 'sm.product_variant_id')
            ->select(
                'sm.posisi',
                DB::raw("
                    COALESCE(
                        SUM(
                            CASE 
                                WHEN sm.direction = 'in' THEN sm.qty
                                WHEN sm.direction = 'out' THEN -sm.qty
                                ELSE 0
                            END
                        ), 0
                    ) as total_stock
                ")
            )
            ->where('pv.store_id', $storeId)
            ->whereDate('sm.tanggal', '<=', $today)
            ->groupBy('sm.posisi')
            ->pluck('total_stock', 'sm.posisi');


        $stokGudang = $stockData['warehouse'] ?? 0;
        $stokStore = $stockData['store'] ?? 0;

        // Penjualan hari ini
        $salesToday = Sale::whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->whereDate('sale_date', $today)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(grand_total), 0) as total')
            ->first();

        // Penjualan bulan ini
        $salesMonth = Sale::whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(grand_total), 0) as total')
            ->first();

        // Trend penjualan harian (30 hari terakhir)
        $dailySales = Sale::whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('sale_date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(sale_date) as tanggal, COUNT(*) as jumlah, COALESCE(SUM(grand_total), 0) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // Top 10 produk terlaris bulan ini
        $topProducts = DB::table('sale_items')
            ->join('sales', function ($join) {
                $join->on('sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.store_id', session('store_id'))
                    ->where('sales.status', '=', 'paid');
            })
            ->whereNull('sales.ref_sale_id')
            ->whereBetween('sales.sale_date', [$startOfMonth, $endOfMonth])
            ->whereIn('sale_items.status', ['sold', 'exchanged_in'])
            ->select('sale_items.product_name', DB::raw('SUM(sale_items.qty) as total_qty'), DB::raw('SUM(sale_items.subtotal) as total_nilai'))
            ->groupBy('sale_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // Metode pembayaran bulan ini
        $paymentMethods = CashTransaction::where('transaction_type', 'sale')
            ->where('direction', 'in')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->select('payment_method', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();
        // Stok keluar hari ini
        $stockOutToday = $this->getStockOut($today->toDateString());
        // Produk hampir habis (stok total <= 10)
        $lowStock = ProductVariant::where('is_active', 1)
            ->with('product')
            ->get()
            ->filter(fn($v) => $v->stok_total <= 5)
            ->sortByDesc('stok_total')
            ->take(10);

        return view('beranda', compact(
            'totalProducts',
            'totalVariants',
            'stokGudang',
            'stokStore',
            'salesToday',
            'salesMonth',
            'dailySales',
            'topProducts',
            'paymentMethods',
            'lowStock',
            'stockOutToday'
        ));
    }

    public function stockOut(Request $request)
    {
        $tanggal = $request->tanggal ?? Carbon::today()->toDateString();
        $data = $this->getStockOut($tanggal);

        return response()->json($data);
    }

    private function getStockOut(string $tanggal)
    {
        return StockMovement::where('direction', 'out')
            ->whereHas('productVariant', function ($query) {
                $query->where('store_id', session('store_id'));
            })
            ->whereDate('tanggal', $tanggal)
            ->select(
                'product_variant_id',
                'posisi',
                'ref_type',
                DB::raw('SUM(qty) as total_qty')
            )
            ->groupBy('product_variant_id', 'posisi', 'ref_type')
            ->with(['productVariant.product'])
            ->orderByDesc('total_qty')
            ->get()
            ->map(function ($item) {
                return [
                    'sku' => $item->productVariant->sku ?? '-',
                    'produk' => ($item->productVariant->product->nama_produk ?? '-') . (optional($item->productVariant)->variant_label ? ' - ' . $item->productVariant->variant_label : ''),
                    'posisi' => ucfirst($item->posisi),
                    'ref_type' => $item->ref_type ?? '-',
                    'total_qty' => $item->total_qty,
                ];
            });
    }

    public function indexSuperadmin()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 1. Total Toko (Aktif, Masa Percobaan, Expired, Non-aktif)
        $stores = \App\Models\Store::with('subscription')->get();
        $totalStores = $stores->count();
        $aktifStores = 0;
        $graceStores = 0;
        $expiredStores = 0;
        $nonAktifStores = 0;

        foreach ($stores as $store) {
            if (!$store->is_active) {
                $nonAktifStores++;
            } else {
                if (!$store->subscription) {
                    $expiredStores++;
                } else {
                    $status = $store->subscription->subscription_status;
                    if ($status === 'active') {
                        $aktifStores++;
                    } elseif ($status === 'grace_period') {
                        $graceStores++;
                    } else {
                        $expiredStores++;
                    }
                }
            }
        }

        // 2. Global KPIs
        $salesToday = Sale::withoutGlobalScopes()
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('status', 'paid')
            ->whereDate('sale_date', $today)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(grand_total), 0) as total')
            ->first();

        $salesMonth = Sale::withoutGlobalScopes()
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('status', 'paid')
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(grand_total), 0) as total')
            ->first();

        $expensesMonth = \App\Models\Expense::withoutGlobalScopes()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // SaaS Billing Metrics
        $unpaidInvoicesSum = \App\Models\SubscribedInvoice::where('status', 'unpaid')->sum('billing_amount');
        $unpaidInvoicesCount = \App\Models\SubscribedInvoice::where('status', 'unpaid')->count();
        $totalSubscribedRevenue = \App\Models\SubscribedPayment::sum('amount');

        // 3. Performa Toko (Top Stores Table)
        $storeSales = Sale::withoutGlobalScopes()
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('status', 'paid')
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->select('store_id', DB::raw('COUNT(*) as count_trx'), DB::raw('SUM(grand_total) as total_sales'))
            ->groupBy('store_id')
            ->get()
            ->keyBy('store_id');

        $topStores = [];
        foreach ($stores as $store) {
            $saleData = $storeSales->get($store->id);
            $totalSales = $saleData ? $saleData->total_sales : 0;
            $countTrx = $saleData ? $saleData->count_trx : 0;
            $basketSize = $countTrx > 0 ? ($totalSales / $countTrx) : 0;
            $subStatus = $store->subscription ? $store->subscription->subscription_status : 'expired';

            $topStores[] = (object) [
                'id' => $store->id,
                'name' => $store->name,
                'count_trx' => $countTrx,
                'total_sales' => $totalSales,
                'basket_size' => $basketSize,
                'sub_status' => $subStatus,
                'is_active' => $store->is_active,
            ];
        }
        usort($topStores, fn($a, $b) => $b->total_sales <=> $a->total_sales);
        $topStores = array_slice($topStores, 0, 10);

        // 4. Live Activity Feed
        $activities = collect();
        $storeMap = $stores->pluck('name', 'id')->toArray();

        $latestSales = Sale::withoutGlobalScopes()
            ->where('status', 'paid')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        foreach ($latestSales as $sale) {
            $sName = $storeMap[$sale->store_id] ?? 'Unknown Store';
            $activities->push((object)[
                'timestamp' => $sale->created_at,
                'type' => 'Penjualan',
                'icon' => 'shopping_cart',
                'color' => 'success',
                'message' => "Toko <strong>{$sName}</strong> menyelesaikan transaksi POS senilai <strong>Rp " . number_format($sale->grand_total, 0, ',', '.') . "</strong> (Inv: {$sale->invoice_number})."
            ]);
        }

        $latestExpenses = \App\Models\Expense::withoutGlobalScopes()
            ->with('category')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        foreach ($latestExpenses as $exp) {
            $sName = $storeMap[$exp->store_id] ?? 'Unknown Store';
            $catName = $exp->category->name ?? 'Lainnya';
            $activities->push((object)[
                'timestamp' => $exp->created_at,
                'type' => 'Biaya',
                'icon' => 'account_balance_wallet',
                'color' => 'danger',
                'message' => "Toko <strong>{$sName}</strong> mencatat biaya operasional <strong>{$catName}</strong> senilai <strong>Rp " . number_format($exp->amount, 0, ',', '.') . "</strong>."
            ]);
        }

        $latestSO = \App\Models\StockOpname::withoutGlobalScopes()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        foreach ($latestSO as $so) {
            $sName = $storeMap[$so->store_id] ?? 'Unknown Store';
            $activities->push((object)[
                'timestamp' => $so->created_at,
                'type' => 'Stok',
                'icon' => 'assignment',
                'color' => 'info',
                'message' => "Toko <strong>{$sName}</strong> mendaftarkan stock opname baru (SO: {$so->code}) dengan status <strong>{$so->status}</strong>."
            ]);
        }

        $latestSA = \App\Models\StockAdjustment::withoutGlobalScopes()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        foreach ($latestSA as $sa) {
            $sName = $storeMap[$sa->store_id] ?? 'Unknown Store';
            $activities->push((object)[
                'timestamp' => $sa->created_at,
                'type' => 'Stok',
                'icon' => 'inventory',
                'color' => 'primary',
                'message' => "Toko <strong>{$sName}</strong> melakukan penyesuaian stok (Adj: {$sa->code}) dengan status <strong>{$sa->status}</strong>."
            ]);
        }

        $latestAudit = \App\Models\DailyAudit::orderByDesc('created_at')
            ->limit(10)
            ->get();
        foreach ($latestAudit as $aud) {
            $sName = $storeMap[$aud->store_id] ?? 'Unknown Store';
            $activities->push((object)[
                'timestamp' => $aud->created_at,
                'type' => 'Sesi',
                'icon' => 'lock',
                'color' => 'warning',
                'message' => "Toko <strong>{$sName}</strong> memperbarui sesi audit kasir harian tanggal <strong>" . Carbon::parse($aud->audit_date)->format('d-m-Y') . "</strong> (Status: <strong>{$aud->status}</strong>)."
            ]);
        }

        $activities = $activities->sortByDesc('timestamp')->take(10)->values();

        // 5. Visualisasi Chart
        $storeRevenueComparison = Sale::withoutGlobalScopes()
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('status', 'paid')
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->select('store_id', DB::raw('SUM(grand_total) as total_sales'))
            ->groupBy('store_id')
            ->get()
            ->map(function ($item) use ($storeMap) {
                return [
                    'label' => $storeMap[$item->store_id] ?? 'Unknown Store',
                    'total' => (float)$item->total_sales
                ];
            });

        $dailySales = Sale::withoutGlobalScopes()
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('status', 'paid')
            ->where('sale_date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(sale_date) as tanggal, COALESCE(SUM(grand_total), 0) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $paymentMethods = CashTransaction::withoutGlobalScopes()
            ->where('transaction_type', 'sale')
            ->where('direction', 'in')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->select('payment_method', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        return view('superadmin.dashboard', compact(
            'totalStores',
            'aktifStores',
            'graceStores',
            'expiredStores',
            'nonAktifStores',
            'salesToday',
            'salesMonth',
            'expensesMonth',
            'topStores',
            'activities',
            'storeRevenueComparison',
            'dailySales',
            'paymentMethods',
            'unpaidInvoicesSum',
            'unpaidInvoicesCount',
            'totalSubscribedRevenue'
        ));
    }
}
