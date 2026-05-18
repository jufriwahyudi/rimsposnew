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
}
