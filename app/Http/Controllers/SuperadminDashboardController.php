<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Sale;
use App\Models\CashTransaction;
use App\Models\StockOpname;
use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Tenant;

class SuperadminDashboardController extends Controller
{
    /**
     * Start impersonating a store.
     */
    public function impersonate(Store $store)
    {
        session([
            'store_id' => $store->id,
            'store_name' => $store->name,
            'is_impersonating' => true
        ]);

        Tenant::set($store->id);

        return redirect()->route('dashboard')->with('success', "Sedang mengakses toko: {$store->name}");
    }

    /**
     * Stop impersonating and return to global dashboard.
     */
    public function stopImpersonate()
    {
        session([
            'store_id' => null,
            'store_name' => 'Super Admin Access',
            'is_impersonating' => false
        ]);

        Tenant::set(null);

        return redirect()->route('dashboard')->with('success', 'Kembali ke Akses Global Superadmin.');
    }

    /**
     * View detailed & filterable activity logs.
     */
    public function activityLogs(Request $request)
    {
        $stores = Store::orderBy('name')->get();
        $storeMap = $stores->pluck('name', 'id')->toArray();

        $storeFilter = $request->input('store_id');
        $typeFilter = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        $activities = collect();

        // 1. Penjualan
        if (!$typeFilter || $typeFilter === 'sale') {
            $salesQuery = Sale::withoutGlobalScopes()
                ->where('status', 'paid')
                ->when($storeFilter, fn($q) => $q->where('store_id', $storeFilter))
                ->when($startDate, fn($q) => $q->whereDate('sale_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('sale_date', '<=', $endDate))
                ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
                ->orderByDesc('created_at')
                ->limit(100);

            foreach ($salesQuery->get() as $sale) {
                $sName = $storeMap[$sale->store_id] ?? 'Unknown Store';
                $activities->push((object)[
                    'timestamp' => $sale->created_at,
                    'type' => 'Penjualan',
                    'icon' => 'shopping_cart',
                    'color' => 'success',
                    'store_id' => $sale->store_id,
                    'message' => "Toko <strong>{$sName}</strong> menyelesaikan transaksi POS senilai <strong>Rp " . number_format($sale->grand_total, 0, ',', '.') . "</strong> (Inv: {$sale->invoice_number})."
                ]);
            }
        }

        // 2. Biaya
        if (!$typeFilter || $typeFilter === 'expense') {
            $expensesQuery = \App\Models\Expense::withoutGlobalScopes()
                ->with('category')
                ->when($storeFilter, fn($q) => $q->where('store_id', $storeFilter))
                ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
                ->when($search, fn($q) => $q->where('description', 'like', "%{$search}%"))
                ->orderByDesc('created_at')
                ->limit(100);

            foreach ($expensesQuery->get() as $exp) {
                $sName = $storeMap[$exp->store_id] ?? 'Unknown Store';
                $catName = $exp->category->name ?? 'Lainnya';
                $activities->push((object)[
                    'timestamp' => $exp->created_at,
                    'type' => 'Biaya',
                    'icon' => 'account_balance_wallet',
                    'color' => 'danger',
                    'store_id' => $exp->store_id,
                    'message' => "Toko <strong>{$sName}</strong> mencatat biaya operasional <strong>{$catName}</strong> senilai <strong>Rp " . number_format($exp->amount, 0, ',', '.') . "</strong>. Keterangan: {$exp->description}"
                ]);
            }
        }

        // 3. Stok
        if (!$typeFilter || $typeFilter === 'stock') {
            $soQuery = StockOpname::withoutGlobalScopes()
                ->when($storeFilter, fn($q) => $q->where('store_id', $storeFilter))
                ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
                ->when($search, fn($q) => $q->where('code', 'like', "%{$search}%"))
                ->orderByDesc('created_at')
                ->limit(100);

            foreach ($soQuery->get() as $so) {
                $sName = $storeMap[$so->store_id] ?? 'Unknown Store';
                $activities->push((object)[
                    'timestamp' => $so->created_at,
                    'type' => 'Stok',
                    'icon' => 'assignment',
                    'color' => 'info',
                    'store_id' => $so->store_id,
                    'message' => "Toko <strong>{$sName}</strong> mendaftarkan stock opname baru (SO: {$so->code}) dengan status <strong>{$so->status}</strong>."
                ]);
            }

            $saQuery = StockAdjustment::withoutGlobalScopes()
                ->when($storeFilter, fn($q) => $q->where('store_id', $storeFilter))
                ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
                ->when($search, fn($q) => $q->where('code', 'like', "%{$search}%"))
                ->orderByDesc('created_at')
                ->limit(100);

            foreach ($saQuery->get() as $sa) {
                $sName = $storeMap[$sa->store_id] ?? 'Unknown Store';
                $activities->push((object)[
                    'timestamp' => $sa->created_at,
                    'type' => 'Stok',
                    'icon' => 'inventory',
                    'color' => 'primary',
                    'store_id' => $sa->store_id,
                    'message' => "Toko <strong>{$sName}</strong> melakukan penyesuaian stok (Adj: {$sa->code}) dengan status <strong>{$sa->status}</strong>."
                ]);
            }
        }

        // 4. Sesi Audit
        if (!$typeFilter || $typeFilter === 'audit') {
            $auditQuery = \App\Models\DailyAudit::when($storeFilter, fn($q) => $q->where('store_id', $storeFilter))
                ->when($startDate, fn($q) => $q->whereDate('audit_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('audit_date', '<=', $endDate))
                ->orderByDesc('created_at')
                ->limit(100);

            foreach ($auditQuery->get() as $aud) {
                $sName = $storeMap[$aud->store_id] ?? 'Unknown Store';
                $activities->push((object)[
                    'timestamp' => $aud->created_at,
                    'type' => 'Sesi',
                    'icon' => 'lock',
                    'color' => 'warning',
                    'store_id' => $aud->store_id,
                    'message' => "Toko <strong>{$sName}</strong> memperbarui sesi audit kasir harian tanggal <strong>" . Carbon::parse($aud->audit_date)->format('d-m-Y') . "</strong> (Status: <strong>{$aud->status}</strong>)."
                ]);
            }
        }

        // Sort and paginate manually
        $sortedActivities = $activities->sortByDesc('timestamp');

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $currentPageItems = $sortedActivities->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedActivities = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageItems,
            $sortedActivities->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('superadmin.activity_logs', compact('paginatedActivities', 'stores'));
    }

    /**
     * Check if user is Superadmin OR has more than 1 active store assigned.
     */
    private function checkConsolidatedReportAccess()
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $selectedRole = session('selected_role');
        $role = \App\Models\RoleMaster::find($selectedRole);
        $isSuperAdmin = $role && strtoupper($role->role_type) === 'SUPERADMIN';

        if (!$isSuperAdmin) {
            $assignedStoreCount = $user->stores()->where('stores.is_active', true)->count();
            if ($assignedStoreCount <= 1) {
                abort(403, 'Anda tidak memiliki akses ke laporan konsolidasi multi-toko.');
            }
        }
    }

    /**
     * View Consolidated Reports Dashboard.
     */
    public function consolidatedReports(Request $request)
    {
        $this->checkConsolidatedReportAccess();

        $user = auth()->user();
        $selectedRole = session('selected_role');
        $role = \App\Models\RoleMaster::find($selectedRole);
        $isSuperAdmin = $role && strtoupper($role->role_type) === 'SUPERADMIN';

        if (!$isSuperAdmin) {
            $stores = $user->stores()->where('stores.is_active', true)->orderBy('name')->get();
        } else {
            $stores = Store::where('is_active', true)->orderBy('name')->get();
        }

        return view('superadmin.consolidated_reports', compact('stores'));
    }

    /**
     * AJAX/HTML provider for Consolidated Profit & Loss.
     */
    public function getConsolidatedLabaRugi(Request $request)
    {
        $this->checkConsolidatedReportAccess();

        $mulai = $request->input('mulai') ?: Carbon::now()->startOfMonth()->toDateString();
        $akhir = $request->input('akhir') ?: Carbon::now()->toDateString();
        $storeIdsFilter = $request->input('store_ids');

        $user = auth()->user();
        $selectedRole = session('selected_role');
        $role = \App\Models\RoleMaster::find($selectedRole);
        $isSuperAdmin = $role && strtoupper($role->role_type) === 'SUPERADMIN';

        if (!$isSuperAdmin) {
            $assignedStoreIds = $user->stores()->where('stores.is_active', true)->pluck('stores.id')->toArray();
            
            $storesQuery = Store::whereIn('id', $assignedStoreIds);
            if ($storeIdsFilter) {
                $storeIdsFilter = is_array($storeIdsFilter) ? $storeIdsFilter : [$storeIdsFilter];
                $filteredStoreIds = array_intersect($storeIdsFilter, $assignedStoreIds);
                $storesQuery->whereIn('id', $filteredStoreIds);
            }
        } else {
            $storesQuery = Store::where('is_active', true);
            if ($storeIdsFilter) {
                $storeIdsFilter = is_array($storeIdsFilter) ? $storeIdsFilter : [$storeIdsFilter];
                $storesQuery->whereIn('id', $storeIdsFilter);
            }
        }

        $stores = $storesQuery->orderBy('name')->get();
        $storeIds = $stores->pluck('id')->toArray();

        // Initialize P&L data structure
        $report = [
            'omset' => [],
            'hpp' => [],
            'pendapatan_kotor' => [],
            'biaya' => [], // category_id => [store_id => amount]
            'total_biaya' => [],
            'laba_rugi' => [],
        ];

        foreach ($storeIds as $sid) {
            $report['omset'][$sid] = 0;
            $report['hpp'][$sid] = 0;
            $report['total_biaya'][$sid] = 0;
        }

        // Query sales
        $sales = Sale::withoutGlobalScopes()
            ->with([
                'items' => fn($q) => $q->with(['batches', 'variant.product.tenant', 'fnbDetail'])->whereIn('status', ['sold', 'exchanged_in']),
            ])
            ->where('status', 'paid')
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->whereBetween('sale_date', [$mulai . ' 00:00:00', $akhir . ' 23:59:59'])
            ->get();

        $storeTypes = $stores->pluck('business_type', 'id')->toArray();

        foreach ($sales as $sale) {
            $sid = $sale->store_id;
            if (!in_array($sid, $storeIds)) continue;

            $report['omset'][$sid] += $sale->grand_total + $sale->tip_amount;
            $isFnB = ($storeTypes[$sid] ?? 'retail') === 'fnb';

            foreach ($sale->items as $item) {
                if ($isFnB) {
                    $variant = $item->variant;
                    if ($variant && $variant->product) {
                        $tenant = $variant->product->tenant ?? null;
                        $costPriceManual = $item->cost_price ?? ($variant->cost_price_manual ?? 0);
                        if ($tenant) {
                            $commissionAmount = $item->commission_amount ?? ($variant ? $variant->calculateCommission($item->price) : 0);
                            $commission = $commissionAmount * $item->qty;
                            $tenantShare = ($item->price * $item->qty) - $commission;
                            $report['hpp'][$sid] += $tenantShare + $costPriceManual * $item->qty;
                        } else {
                            $report['hpp'][$sid] += $costPriceManual * $item->qty;
                        }
                    }
                } else {
                    foreach ($item->batches as $batch) {
                        $report['hpp'][$sid] += $batch->qty * $batch->cost_price;
                    }
                }
            }
        }

        // Gross profits
        foreach ($storeIds as $sid) {
            $report['pendapatan_kotor'][$sid] = $report['omset'][$sid] - $report['hpp'][$sid];
        }

        // Expenses
        $expenses = \App\Models\Expense::withoutGlobalScopes()
            ->with('category')
            ->whereBetween('transaction_date', [$mulai, $akhir])
            ->get();

        $categories = \App\Models\ExpenseCategory::withoutGlobalScopes()->orderBy('name')->get();

        foreach ($expenses as $exp) {
            $sid = $exp->store_id;
            if (!in_array($sid, $storeIds)) continue;

            $catId = $exp->expense_category_id;
            if (!isset($report['biaya'][$catId])) {
                $report['biaya'][$catId] = array_fill_keys($storeIds, 0);
            }
            $report['biaya'][$catId][$sid] += $exp->amount;
            $report['total_biaya'][$sid] += $exp->amount;
        }

        // Net profits
        foreach ($storeIds as $sid) {
            $report['laba_rugi'][$sid] = $report['pendapatan_kotor'][$sid] - $report['total_biaya'][$sid];
        }

        return view('superadmin.partials.consolidated_laba_rugi_table', compact(
            'stores',
            'report',
            'categories',
            'mulai',
            'akhir'
        ));
    }

    /**
     * AJAX/HTML provider for Consolidated Critical Stock.
     */
    public function getConsolidatedStokKritis(Request $request)
    {
        $this->checkConsolidatedReportAccess();

        $threshold = $request->input('threshold') ?? 10;
        $storeIdFilter = $request->input('store_id');

        $user = auth()->user();
        $selectedRole = session('selected_role');
        $role = \App\Models\RoleMaster::find($selectedRole);
        $isSuperAdmin = $role && strtoupper($role->role_type) === 'SUPERADMIN';

        if (!$isSuperAdmin) {
            $assignedStoreIds = $user->stores()->where('stores.is_active', true)->pluck('stores.id')->toArray();
            
            $variantsQuery = \App\Models\ProductVariant::withoutGlobalScopes()
                ->with(['product', 'store'])
                ->where('is_active', 'Y')
                ->whereIn('store_id', $assignedStoreIds);

            if ($storeIdFilter && in_array($storeIdFilter, $assignedStoreIds)) {
                $variantsQuery->where('store_id', $storeIdFilter);
            }
        } else {
            $variantsQuery = \App\Models\ProductVariant::withoutGlobalScopes()
                ->with(['product', 'store'])
                ->where('is_active', 'Y')
                ->when($storeIdFilter, fn($q) => $q->where('store_id', $storeIdFilter));
        }

        $criticalVariants = $variantsQuery->get()
            ->filter(function ($variant) use ($threshold) {
                return $variant->stok_total <= $threshold;
            })
            ->sortBy('stok_total')
            ->values();

        return view('superadmin.partials.consolidated_stok_kritis_table', compact('criticalVariants', 'threshold'));
    }
}
