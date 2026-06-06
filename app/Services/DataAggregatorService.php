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
        
        // 1. Revenue Summary
        $revenueSummary = Sale::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereDate('sale_date', $targetDate)
            ->where('status', 'paid')
            ->whereNull('ref_sale_id') // Exclude refunds
            ->selectRaw('
                COUNT(*) as total_transactions,
                COALESCE(SUM(grand_total), 0) as total_revenue,
                COALESCE(AVG(grand_total), 0) as avg_transaction_value,
                COALESCE(SUM(discount_total), 0) as total_discount_given
            ')
            ->first()
            ->toArray();

        // Convert decimal values to floats/integers
        $revenueSummary['total_transactions'] = (int)$revenueSummary['total_transactions'];
        $revenueSummary['total_revenue'] = (float)$revenueSummary['total_revenue'];
        $revenueSummary['avg_transaction_value'] = (float)$revenueSummary['avg_transaction_value'];
        $revenueSummary['total_discount_given'] = (float)$revenueSummary['total_discount_given'];

        // 2. Revenue Vs Yesterday
        $yesterday = $targetDate->copy()->subDay();
        $yesterdayRevenue = Sale::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereDate('sale_date', $yesterday)
            ->where('status', 'paid')
            ->whereNull('ref_sale_id')
            ->sum('grand_total');

        $totalRevenueToday = $revenueSummary['total_revenue'];
        if ($yesterdayRevenue > 0) {
            $revenueVsYesterdayPct = (($totalRevenueToday - $yesterdayRevenue) / $yesterdayRevenue) * 100;
        } else {
            $revenueVsYesterdayPct = $totalRevenueToday > 0 ? 100.0 : 0.0;
        }
        $revenueSummary['revenue_vs_yesterday_pct'] = round($revenueVsYesterdayPct, 2);

        // 3. Top Products (based on qty sold today)
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

        // 4. Slow Products (based on qty sold today, bottom 5)
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

        // 5. Hourly Distribution
        $hourlyDist = Sale::withoutGlobalScopes()
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

        // 6. Critical Stock (< 3 units sisa on store)
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
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->nama_produk,
                    'variant' => $item->variant_name ?: 'Default',
                    'qty_remaining' => (int)$item->qty_remaining,
                ];
            })
            ->toArray();

        // 7. Expenses
        $expenses = Expense::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereDate('transaction_date', $targetDate)
            ->with('category')
            ->get();

        $totalExpenses = $expenses->sum('amount');
        $expensesByCategory = $expenses->groupBy('expense_category_id')->map(function ($group) {
            $categoryName = $group->first()->category->name ?? 'Lainnya';
            return [
                'category' => $categoryName,
                'amount' => (float)$group->sum('amount')
            ];
        })->values()->toArray();

        // Fetch Store Name & Business Type
        $store = DB::table('stores')->where('id', $storeId)->select('name', 'business_type')->first();
        $storeName = $store->name ?? 'Toko';
        $businessType = $store->business_type ?? 'retail';

        // Sanitization check: Ensure no employee names, customer names, custom phones, or IDs are included.
        return [
            'store_name' => $storeName,
            'business_type' => $businessType,
            'report_date' => $date,
            'summary' => $revenueSummary,
            'top_products' => $topProducts,
            'slow_products' => $slowProducts,
            'hourly_distribution' => $hourlyDistribution,
            'critical_stock' => $criticalStock,
            'expenses' => [
                'total' => (float)$totalExpenses,
                'by_category' => $expensesByCategory
            ]
        ];
    }
}
