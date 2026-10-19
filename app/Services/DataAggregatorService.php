<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DataAggregatorService
{
    /**
     * Aggregate all store data for a specific date (e.g. daily summary)
     * and sanitize any sensitive information.
     */
    public function aggregateForNewspaper(int $storeId, string $date): array
    {
        $targetDate = Carbon::parse($date);
        $dayName = $targetDate->locale('id')->isoFormat('dddd');
        
        // 1. Revenue & Transactions Summary
        $revenueRow = DB::table('sales')
            ->where('store_id', $storeId)
            ->whereDate('sale_date', $targetDate)
            ->where('status', 'paid')
            ->whereNull('ref_sale_id')
            ->where('grand_total', '>=', 0)
            ->selectRaw('
                COUNT(*) as total_transactions,
                COALESCE(SUM(grand_total), 0) as gross_revenue,
                COALESCE(AVG(grand_total), 0) as avg_transaction_value,
                COALESCE(SUM(discount_total + trans_discount + point_discount_amount + voucher_discount_amount), 0) as total_discount_given
            ')
            ->first();

        // Refunds / Returns
        $refundRow = DB::table('sales')
            ->where('store_id', $storeId)
            ->whereDate('sale_date', $targetDate)
            ->where(function ($q) {
                $q->whereNotNull('ref_sale_id')
                  ->orWhere('grand_total', '<', 0);
            })
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(ABS(grand_total)), 0) as amount')
            ->first();

        $grossRevenue = (float)($revenueRow->gross_revenue ?? 0);
        $refundAmount = (float)($refundRow->amount ?? 0);
        $netRevenue = max(0, $grossRevenue - $refundAmount);

        // 2. Comparisons (Yesterday, Same Day Last Week, 7-Day Average)
        $yesterday = $targetDate->copy()->subDay();
        $lastWeek = $targetDate->copy()->subDays(7);

        $yesterdayRevenue = (float) DB::table('sales')
            ->where('store_id', $storeId)
            ->whereDate('sale_date', $yesterday)
            ->where('status', 'paid')
            ->whereNull('ref_sale_id')
            ->where('grand_total', '>=', 0)
            ->sum('grand_total');

        $lastWeekRevenue = (float) DB::table('sales')
            ->where('store_id', $storeId)
            ->whereDate('sale_date', $lastWeek)
            ->where('status', 'paid')
            ->whereNull('ref_sale_id')
            ->where('grand_total', '>=', 0)
            ->sum('grand_total');

        $avg7dRevenue = (float) DB::table('sales')
            ->where('store_id', $storeId)
            ->whereBetween(DB::raw('DATE(sale_date)'), [
                $targetDate->copy()->subDays(7)->toDateString(),
                $targetDate->copy()->subDay()->toDateString()
            ])
            ->where('status', 'paid')
            ->whereNull('ref_sale_id')
            ->where('grand_total', '>=', 0)
            ->sum('grand_total');
        $avgDaily7d = round($avg7dRevenue / 7.0, 0);

        $revenueVsYesterdayPct = $yesterdayRevenue > 0
            ? round((($netRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : ($netRevenue > 0 ? 100.0 : 0.0);

        $revenueVsLastWeekPct = $lastWeekRevenue > 0
            ? round((($netRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100, 1)
            : ($netRevenue > 0 ? 100.0 : 0.0);

        $revenueSummary = [
            'total_transactions' => (int)($revenueRow->total_transactions ?? 0),
            'gross_revenue' => $grossRevenue,
            'total_refunds_count' => (int)($refundRow->count ?? 0),
            'total_refunds_amount' => $refundAmount,
            'net_revenue' => $netRevenue,
            'total_revenue' => $netRevenue, // backward compatible key
            'avg_transaction_value' => round((float)($revenueRow->avg_transaction_value ?? 0), 0),
            'total_discount_given' => (float)($revenueRow->total_discount_given ?? 0),
            'revenue_yesterday' => $yesterdayRevenue,
            'revenue_vs_yesterday_pct' => $revenueVsYesterdayPct,
            'revenue_same_day_last_week' => $lastWeekRevenue,
            'revenue_vs_last_week_pct' => $revenueVsLastWeekPct,
            'avg_daily_revenue_7d' => $avgDaily7d,
        ];

        // 3. Category Breakdown (Share of revenue)
        $categoriesRaw = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->leftJoin('products as pr', 'si.product_id', '=', 'pr.id')
            ->leftJoin('product_categories as pc', 'pr.category_id', '=', 'pc.id')
            ->where('s.store_id', $storeId)
            ->whereDate('s.sale_date', $targetDate)
            ->where('s.status', 'paid')
            ->whereIn('si.status', ['sold', 'exchanged_in'])
            ->select(
                DB::raw("COALESCE(pc.name, 'Tanpa Kategori') as category"),
                DB::raw('SUM(si.qty) as qty_sold'),
                DB::raw('SUM(si.subtotal) as revenue')
            )
            ->groupBy('category')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        $totalCatRevenue = $categoriesRaw->sum('revenue');
        $categories = $categoriesRaw->map(function ($item) use ($totalCatRevenue) {
            $rev = (float)$item->revenue;
            return [
                'category' => $item->category,
                'qty_sold' => (int)$item->qty_sold,
                'revenue' => $rev,
                'share_pct' => $totalCatRevenue > 0 ? round(($rev / $totalCatRevenue) * 100, 1) : 0,
            ];
        })->toArray();

        // 4. Market Basket Pairs (Frequent item combos)
        $basketPairs = DB::table('sale_items as a')
            ->join('sale_items as b', function ($join) {
                $join->on('a.sale_id', '=', 'b.sale_id')
                     ->whereColumn('a.product_name', '<', 'b.product_name');
            })
            ->join('sales as s', 'a.sale_id', '=', 's.id')
            ->where('s.store_id', $storeId)
            ->whereDate('s.sale_date', $targetDate)
            ->where('s.status', 'paid')
            ->whereIn('a.status', ['sold', 'exchanged_in'])
            ->whereIn('b.status', ['sold', 'exchanged_in'])
            ->select('a.product_name as item_a', 'b.product_name as item_b', DB::raw('COUNT(*) as pair_count'))
            ->groupBy('item_a', 'item_b')
            ->orderByDesc('pair_count')
            ->limit(5)
            ->get()
            ->map(function ($p) {
                return [
                    'combo' => "{$p->item_a} + {$p->item_b}",
                    'pair_count' => (int)$p->pair_count,
                ];
            })
            ->toArray();

        // 5. Dine-in vs Takeaway (FnB dining pattern)
        $dineInRow = DB::table('sales')
            ->where('store_id', $storeId)
            ->whereDate('sale_date', $targetDate)
            ->where('status', 'paid')
            ->whereNull('ref_sale_id')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN table_number IS NOT NULL AND table_number != '' THEN 1 ELSE 0 END), 0) as dine_in_count,
                COALESCE(SUM(CASE WHEN table_number IS NULL OR table_number = '' THEN 1 ELSE 0 END), 0) as takeaway_count
            ")
            ->first();

        $dineInCount = (int)($dineInRow->dine_in_count ?? 0);
        $takeawayCount = (int)($dineInRow->takeaway_count ?? 0);
        $totalDine = $dineInCount + $takeawayCount;

        $dineInTakeaway = [
            'dine_in_count' => $dineInCount,
            'dine_in_pct' => $totalDine > 0 ? round(($dineInCount / $totalDine) * 100, 1) : 0,
            'takeaway_count' => $takeawayCount,
            'takeaway_pct' => $totalDine > 0 ? round(($takeawayCount / $totalDine) * 100, 1) : 0,
        ];

        // 6. Payment Methods Distribution
        $paymentMethods = DB::table('cash_transactions')
            ->where('store_id', $storeId)
            ->whereDate('transaction_date', $targetDate)
            ->where('transaction_type', 'sale')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total_amount'))
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($pm) {
                return [
                    'method' => strtoupper($pm->payment_method ?: 'CASH'),
                    'count' => (int)$pm->count,
                    'amount' => (float)$pm->total_amount,
                ];
            })
            ->toArray();

        // 7. Voids / Cancelled items
        $voidRow = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->where('s.store_id', $storeId)
            ->whereDate('s.sale_date', $targetDate)
            ->where(function ($q) {
                $q->where('si.status', 'void')
                  ->orWhere('s.status', 'void');
            })
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(si.subtotal), 0) as amount')
            ->first();

        $voidMetrics = [
            'void_items_count' => (int)($voidRow->count ?? 0),
            'void_items_amount' => (float)($voidRow->amount ?? 0),
        ];

        // 8. Anonymized Cashier Workload
        $cashierRows = DB::table('sales')
            ->where('store_id', $storeId)
            ->whereDate('sale_date', $targetDate)
            ->where('status', 'paid')
            ->whereNull('ref_sale_id')
            ->select('user_id', DB::raw('COUNT(*) as tx_count'), DB::raw('SUM(grand_total) as revenue'))
            ->groupBy('user_id')
            ->orderByDesc('tx_count')
            ->get();

        $anonymizedCashiers = [];
        $letters = range('A', 'Z');
        foreach ($cashierRows as $idx => $crow) {
            $alias = 'Kasir ' . ($letters[$idx] ?? ($idx + 1));
            $anonymizedCashiers[] = [
                'alias' => $alias,
                'transactions' => (int)$crow->tx_count,
                'revenue' => (float)$crow->revenue,
            ];
        }

        // 9. Top Products (based on qty sold today)
        $topProducts = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->where('s.store_id', $storeId)
            ->whereDate('s.sale_date', $targetDate)
            ->where('s.status', 'paid')
            ->whereIn('si.status', ['sold', 'exchanged_in'])
            ->select('si.product_name', DB::raw('SUM(si.qty) as qty_sold'), DB::raw('SUM(si.subtotal) as revenue'))
            ->groupBy('si.product_name')
            ->orderByDesc('qty_sold')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'qty_sold' => (int)$item->qty_sold,
                    'revenue' => (float)$item->revenue
                ];
            })
            ->toArray();

        // 10. Slow Products (bottom 5 sold items)
        $slowProducts = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->where('s.store_id', $storeId)
            ->whereDate('s.sale_date', $targetDate)
            ->where('s.status', 'paid')
            ->whereIn('si.status', ['sold', 'exchanged_in'])
            ->select('si.product_name', DB::raw('SUM(si.qty) as qty_sold'), DB::raw('SUM(si.subtotal) as revenue'))
            ->groupBy('si.product_name')
            ->orderBy('qty_sold')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'qty_sold' => (int)$item->qty_sold,
                    'revenue' => (float)$item->revenue
                ];
            })
            ->toArray();

        // 11. Hourly Distribution
        $hourlyDist = DB::table('sales')
            ->where('store_id', $storeId)
            ->whereDate('sale_date', $targetDate)
            ->where('status', 'paid')
            ->selectRaw('HOUR(sale_date) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $hourlyDistribution = [];
        foreach ($hourlyDist as $hour => $count) {
            $formattedHour = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $hourlyDistribution[$formattedHour] = (int)$count;
        }

        // 12. Critical Stock (< 3 units remaining)
        $criticalStock = DB::table('stock_batches as sb')
            ->join('product_variants as pv', 'sb.product_variant_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->where('pv.store_id', $storeId)
            ->where('sb.posisi', 'store')
            ->where('pv.track_stock', 1)
            ->where('pv.is_active', 1)
            ->select('p.nama_produk', 'pv.variant_name', DB::raw('SUM(sb.qty_sisa) as qty_remaining'))
            ->groupBy('pv.id', 'p.nama_produk', 'pv.variant_name')
            ->having('qty_remaining', '<', 3)
            ->orderBy('qty_remaining')
            ->limit(8)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->nama_produk,
                    'variant' => $item->variant_name ?: 'Default',
                    'qty_remaining' => (int)$item->qty_remaining,
                ];
            })
            ->toArray();

        // 13. Expenses
        $expenses = Expense::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereDate('transaction_date', $targetDate)
            ->with('category')
            ->get();

        $totalExpenses = (float)$expenses->sum('amount');
        $expensesByCategory = $expenses->groupBy('expense_category_id')->map(function ($group) {
            $categoryName = $group->first()->category->name ?? 'Lainnya';
            return [
                'category' => $categoryName,
                'amount' => (float)$group->sum('amount')
            ];
        })->values()->toArray();

        // 14. Data Quality / Food Cost Tracking Flag
        $hasCostTracking = DB::table('product_variants')
            ->where('store_id', $storeId)
            ->where('is_active', 1)
            ->where('cost_price_manual', '>', 0)
            ->exists();

        // 15. Store Metadata
        $store = DB::table('stores')->where('id', $storeId)->select('name', 'business_type')->first();
        $storeName = $store->name ?? 'Toko';
        $businessType = $store->business_type ?? 'retail';

        return [
            'store_name' => $storeName,
            'business_type' => $businessType,
            'report_date' => $date,
            'day_name' => $dayName,
            'summary' => $revenueSummary,
            'categories' => $categories,
            'basket_pairs' => $basketPairs,
            'dine_in_takeaway' => $dineInTakeaway,
            'payment_methods' => $paymentMethods,
            'void_metrics' => $voidMetrics,
            'cashier_distribution' => $anonymizedCashiers,
            'top_products' => $topProducts,
            'slow_products' => $slowProducts,
            'hourly_distribution' => $hourlyDistribution,
            'critical_stock' => $criticalStock,
            'expenses' => [
                'total' => $totalExpenses,
                'by_category' => $expensesByCategory
            ],
            'data_quality' => [
                'food_cost_tracked' => $hasCostTracking,
                'guideline' => $hasCostTracking
                    ? 'Data HPP tercatat di sistem.'
                    : 'Toko belum mencatat HPP/Food Cost di sistem POS. JANGAN membuat estimasi laba kotor/margin rupiah/persentase fiktif. Fokuskan ulasan pada omset, kontribusi kategori menu, basket size, dan jam sibuk operasional.'
            ]
        ];
    }
}
