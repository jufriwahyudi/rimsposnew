<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\NseCalonSiswa;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Customer;
use App\Models\Member;
use App\Models\Store;
use App\Exports\LaporanBiayaExport;
use App\Exports\LaporanStokExport;
use App\Exports\LaporanPenjualanExport;
use App\Exports\LaporanPenjualanNSEExport;
use App\Exports\LaporanLabaRugiExport;
use App\Exports\LaporanHutangExport;
use App\Exports\LaporanMemberExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function penjualan()
    {
        return view('laporan.penjualan');
    }

    public function getpenjualan(Request $request)
    {
        $id_divisi = $request->id_divisi;
        $mulai = $request->mulai;
        $akhir = $request->akhir;

        $query = Sale::with([
            'items' => function ($query) {
                $query->with(['batches', 'variant.product', 'fnbDetail'])
                    ->whereIn('status', ['sold', 'exchanged_in']);
            },
            'cashier'
        ])
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->whereBetween('sale_date', [$mulai . " 00:00:00", $akhir . " 23:59:59"]);

        $sales = $query->orderBy('sale_date', 'asc')->get();
        // dd(json_encode($sales, JSON_PRETTY_PRINT));

        $store = \App\Models\Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $rows = collect();
        foreach ($sales as $i => $sale) {
            $cost = 0;
            if ($isFnB) {
                foreach ($sale->items as $item) {
                    $variant = $item->variant;
                    $tenantId = $variant && $variant->product ? $variant->product->tenant_id : null;
                    $costPriceManual = $item->cost_price ?? ($variant->cost_price_manual ?? 0);
                    if ($tenantId) {
                        $commissionAmount = $item->commission_amount ?? ($variant ? $variant->calculateCommission($item->price) : 0);
                        $costPrice = ($item->price - $commissionAmount) + $costPriceManual;
                    } else {
                        $costPrice = $costPriceManual;
                    }
                    $cost += ($item->qty * $costPrice);
                }
            } else {
                foreach ($sale->items as $item) {
                    foreach ($item->batches as $batch) {
                        $qty = $batch->qty ?? 0;
                        $costPrice = $batch->cost_price ?? ($batch->cost ?? 0);
                        $cost += ($qty * $costPrice);
                    }
                }
            }

            $jumlah = $sale->grand_total ?? 0;
            $laba = $jumlah - $cost;

            $cash = CashTransaction::where('transaction_type', 'sale')
                ->where('ref_id', $sale->id)
                ->first();

            $metode = $cash->payment_method ?? null;

            $rows->push((object) [
                'no' => $i + 1,
                'sale_type' => $sale->sale_type,
                'sale_id' => $sale->id,
                'sale_date' => $sale->sale_date,
                'customer_name' => $sale->customer_name ?: ($sale->customer_id ?: '-'),
                'jumlah_penjualan' => $jumlah,
                'modal' => $cost,
                'laba_rugi' => $laba,
                'metode_pembayaran' => $metode,
                'kasir' => optional($sale->cashier)->name ?? '-',
                'status' => $sale->status,
                'tip' => (float) ($sale->tip_amount ?? 0),
            ]);
        }

        $totalPenjualan = $rows->sum('jumlah_penjualan');
        $totalModal = $rows->sum('modal');
        $totalLabaRugi = $rows->sum('laba_rugi');
        $totalTip = $rows->sum('tip');

        // Jika request dari AJAX, return partial view (hanya tabel)
        if ($request->ajax()) {
            return view('laporan.datapenjualan_table', compact('rows', 'mulai', 'akhir', 'id_divisi', 'totalPenjualan', 'totalModal', 'totalLabaRugi', 'totalTip'));
        }

        // Jika request normal, return full page
        return view('laporan.datapenjualan', compact('rows', 'mulai', 'akhir', 'id_divisi', 'totalPenjualan', 'totalModal', 'totalLabaRugi', 'totalTip'));
    }

    public function exportPenjualan(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;

        return Excel::download(
            new LaporanPenjualanExport($mulai, $akhir),
            'laporan_penjualan_' . $mulai . '_' . $akhir . '.xlsx'
        );
    }

    public function penjualanNSE()
    {
        $divisis = DB::connection('financedb')
            ->table('master_divisi')
            ->where('stts', '1')
            ->where('school', 'Y')
            ->orderBy('position')
            ->get(['nama', 'Id as id']);
        return view('laporan.penjualanNSE', compact('divisis'));
    }

    public function getpenjualanNSE(Request $request)
    {
        $id_divisi = $request->id_divisi;
        $mulai = $request->mulai;
        $akhir = $request->akhir;

        $divisis = DB::connection('nsedb')
            ->table('master_divisi')
            ->where('kelompok', $id_divisi)
            ->get()
            ->pluck('id')
            ->toArray();

        $query = Sale::with([
            'biodata.divisi',
            'items' => function ($query) {
                $query->with('batches')
                    ->whereIn('status', ['sold', 'exchanged_in']);
            },
            'cashier'
        ])
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('sale_type', 'nse')
            ->where('status', 'paid')
            ->whereBetween('sale_date', [$mulai . " 00:00:00", $akhir . " 23:59:59"]);

        // ✅ FILTER DIVISI
        //    if ($request->filled('id_divisi')) {
        //         $query->whereHas('biodata', function ($q) use ($request) {
        //             $q->where('id_divisi', $request->id_divisi);
        //         });
        //     }

        $sales = $query->orderBy('sale_date', 'asc')->get();
        if (!empty($divisis)) {
            $sales = $sales->filter(function ($sale) use ($divisis) {
                return in_array(optional(optional($sale->biodata)->divisi)->id, $divisis);
            })->values();
        }
        // dd(json_encode($sales, JSON_PRETTY_PRINT));

        $rows = collect();
        foreach ($sales as $i => $sale) {
            $cost = 0;
            foreach ($sale->items as $item) {
                foreach ($item->batches as $batch) {
                    $qty = $batch->qty ?? 0;
                    $costPrice = $batch->cost_price ?? ($batch->cost ?? 0);
                    $cost += ($qty * $costPrice);
                }
            }

            $jumlah = $sale->grand_total ?? 0;
            $laba = $jumlah - $cost;

            $cash = CashTransaction::where('transaction_type', 'sale')
                ->where('ref_id', $sale->id)
                ->first();

            $metode = $cash->payment_method ?? null;

            $rows->push((object) [
                'no' => $i + 1,
                'sale_type' => $sale->sale_type,
                'customer_id' => $sale->customer_id,
                'sale_id' => $sale->id,
                'sale_date' => $sale->sale_date,
                'customer_name' => $sale->customer_name ?: ($sale->customer_id ?: '-'),
                'jumlah_penjualan' => $jumlah,
                'modal' => $cost,
                'laba_rugi' => $laba,
                'metode_pembayaran' => $metode,
                'no_pos' => $sale->invoice_number ?? '-',
                'kasir' => optional($sale->cashier)->name ?? '-',
                'status' => $sale->status,
                'biodata' => $sale->biodata ?? null,
            ]);
        }

        $totalPenjualan = $rows->sum('jumlah_penjualan');
        $totalModal = $rows->sum('modal');
        $totalLabaRugi = $rows->sum('laba_rugi');

        // dd($sales->count());
        // $divisis = DB::connection('nsedb')
        //     ->table('master_divisi')
        //     ->orderBy('nama')
        //     ->get()

        // $divisi = NseCalonSiswa::all();
        // $divisi = NseCalonSiswa::select('id_divisi')->distinct()->pluck('id_divisi');
        // $divisi = NseCalonSiswa::whereIn('id', $divisiIds)->get();

        // Jika request dari AJAX, return partial view (hanya tabel)
        if ($request->ajax()) {
            return view('laporan.datapenjualanNSE_table', compact('rows', 'mulai', 'akhir', 'id_divisi', 'totalPenjualan', 'totalModal', 'totalLabaRugi'));
        }

        // Jika request normal, return full page
        // return view('laporan.datapenjualanNSE', compact('rows', 'mulai', 'akhir', 'id_divisi', 'divisis', 'totalPenjualan', 'totalModal', 'totalLabaRugi'));
    }

    public function exportPenjualanNSE(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;
        $id_divisi = $request->id_divisi;

        return Excel::download(
            new LaporanPenjualanNSEExport($mulai, $akhir, $id_divisi),
            'laporan_penjualan_' . $mulai . '_' . $akhir . '.xlsx'
        );
    }

    public function pembelian()
    {
        return view('laporan.pembelian');
    }

    public function stok(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->toDateString();

        $products = Product::query()
            ->with([
                'variants' => function ($q) use ($tanggal) {

                    // stok warehouse
                    $q->select('product_variants.*')
                        ->selectSub(function ($sub) use ($tanggal) {
                            $sub->from('stock_movements')
                                ->selectRaw("
                                    COALESCE(SUM(
                                        CASE 
                                            WHEN direction = 'in' THEN qty
                                            WHEN direction = 'out' THEN -qty
                                            ELSE 0
                                        END
                                    ),0)
                                ")
                                ->whereColumn('stock_movements.product_variant_id', 'product_variants.id')
                                ->where('posisi', 'warehouse')
                                ->whereDate('tanggal', '<=', $tanggal);
                        }, 'stock_warehouse')

                        // stok store
                        ->selectSub(function ($sub) use ($tanggal) {
                            $sub->from('stock_movements')
                                ->selectRaw("
                                    COALESCE(SUM(
                                        CASE 
                                            WHEN direction = 'in' THEN qty
                                            WHEN direction = 'out' THEN -qty
                                            ELSE 0
                                        END
                                    ),0)
                                ")
                                ->whereColumn('stock_movements.product_variant_id', 'product_variants.id')
                                ->where('posisi', 'store')
                                ->whereDate('tanggal', '<=', $tanggal);
                        }, 'stock_store')

                        ->with(['variantAttributes.value'])
                        ->where('is_active', 'Y');
                }
            ])
            ->orderBy('nama_produk')
            ->get();
        // dd(json_encode($products, JSON_PRETTY_PRINT));

        return view('laporan.stok', compact('products', 'tanggal'));
    }

    public function searchstock(Request $request)
    {
        $tanggal = $request->date_filter;

        $products = Product::query()
            ->with([
                'variants' => function ($q) use ($tanggal) {
                    $q->with([
                        'variantAttributes.attribute',
                    ])
                        ->withSum([
                            'movements as stok_warehouse' => function ($m) use ($tanggal) {
                                $m->where('posisi', 'warehouse')
                                    ->whereDate('tanggal', '<=', $tanggal)
                                    ->select(DB::raw("
                              SUM(
                                  CASE 
                                      WHEN direction = 'in' THEN qty
                                      WHEN direction = 'out' THEN -qty
                                      ELSE 0
                                  END
                              )
                          "));
                            },
                            'movements as stok_store' => function ($m) use ($tanggal) {
                                $m->where('posisi', 'store')
                                    ->whereDate('tanggal', '<=', $tanggal)
                                    ->select(DB::raw("
                              SUM(
                                  CASE 
                                      WHEN direction = 'in' THEN qty
                                      WHEN direction = 'out' THEN -qty
                                      ELSE 0
                                  END
                              )
                          "));
                            }
                        ])
                        ->where('is_active', 'Y');
                }
            ])
            ->orderBy('nama_produk')
            ->get();



        // dd(json_encode($variants, JSON_PRETTY_PRINT));
        /* group per produk */
        $data = $variants->groupBy('product_id');

        return view('laporan.partials.stok-table', compact('data', 'tanggal'));
    }

    // Laporan stok terjual harian
    public function harian()
    {
        $storeId = session('store_id');
        $categories = $storeId 
            ? \App\Models\ProductCategory::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('laporan.harian', compact('categories'));
    }

    public function getharian(Request $request)
    {
        $mulai = $request->mulai ?? now()->toDateString();
        $akhir = $request->akhir ?? now()->toDateString();
        $categoryId = $request->category_id;
        $storeId = session('store_id');

        $store = $storeId ? \App\Models\Store::find($storeId) : null;
        $isFnB = $store && $store->business_type === 'fnb';

        $query = SaleItem::with(['variant.product.category', 'fnbDetail', 'batches', 'sale'])
            ->whereHas('sale', function ($q) use ($mulai, $akhir, $storeId) {
                $q->whereNull('ref_sale_id')
                    ->whereDoesntHave('refunds')
                    ->where('status', 'paid')
                    ->when($storeId, fn($sq) => $sq->where('store_id', $storeId))
                    ->whereBetween('sale_date', [$mulai . ' 00:00:00', $akhir . ' 23:59:59']);
            })
            ->whereIn('status', ['sold', 'exchanged_in']);

        if (!empty($categoryId)) {
            $query->whereHas('variant.product', function ($p) use ($categoryId) {
                $p->where('category_id', $categoryId);
            });
        }

        $rawItems = $query->get();

        foreach ($rawItems as $item) {
            $variant = $item->variant;
            if ($isFnB) {
                $tenantId = $variant && $variant->product ? $variant->product->tenant_id : null;
                $costPriceManual = $item->cost_price ?? ($variant->cost_price_manual ?? 0);
                if ($tenantId) {
                    $commissionAmount = $item->commission_amount ?? ($variant ? $variant->calculateCommission($item->price) : 0);
                    $costPrice = ($item->price - $commissionAmount) + $costPriceManual;
                } else {
                    $costPrice = $costPriceManual;
                }
                $item->calculated_modal = $item->qty * $costPrice;
            } else {
                $itemModal = 0;
                foreach ($item->batches as $batch) {
                    $itemModal += ($batch->qty * $batch->cost_price);
                }
                $item->calculated_modal = $itemModal;
            }
        }

        // 1. Grouped by Product
        $rowsProduct = $rawItems->groupBy(function ($item) {
            return $item->variant && $item->variant->product_id
                ? $item->variant->product_id
                : ('custom_' . ($item->product_name ?? 'Item'));
        })->map(function ($group) {
            $first = $group->first();
            $product = optional($first->variant)->product;
            $category = optional($product)->category;

            $totalQty = $group->sum('qty');
            $totalSubtotal = $group->sum('subtotal');
            $totalDiscount = $group->sum('discount_amount');
            $totalModal = $group->sum('calculated_modal');
            $totalLaba = $totalSubtotal - $totalModal;
            $marginPct = $totalSubtotal > 0 ? round(($totalLaba / $totalSubtotal) * 100, 1) : 0;
            $jumlahTrx = $group->pluck('sale_id')->unique()->count();

            return (object) [
                'product_id' => $product->id ?? null,
                'kode_produk' => $product->kode_produk ?? ($first->sku ?? '-'),
                'product_name' => $product->nama_produk ?? ($first->product_name ?? '-'),
                'category_name' => $category->name ?? 'Tanpa Kategori',
                'total_qty' => $totalQty,
                'total_diskon' => $totalDiscount,
                'total_subtotal' => $totalSubtotal,
                'total_modal' => $totalModal,
                'laba_rugi' => $totalLaba,
                'margin_pct' => $marginPct,
                'jumlah_trx' => $jumlahTrx,
            ];
        })->sortByDesc('total_subtotal')->values();

        // 2. Grouped by Category
        $allSubtotal = $rawItems->sum('subtotal');
        $rowsCategory = $rawItems->groupBy(function ($item) {
            $category = optional(optional($item->variant)->product)->category;
            return $category ? $category->id : 0;
        })->map(function ($group) use ($allSubtotal) {
            $first = $group->first();
            $category = optional(optional($first->variant)->product)->category;

            $totalQty = $group->sum('qty');
            $totalSubtotal = $group->sum('subtotal');
            $totalDiscount = $group->sum('discount_amount');
            $totalModal = $group->sum('calculated_modal');
            $totalLaba = $totalSubtotal - $totalModal;
            $kontribusiPct = $allSubtotal > 0 ? round(($totalSubtotal / $allSubtotal) * 100, 1) : 0;
            $jumlahTrx = $group->pluck('sale_id')->unique()->count();
            $jumlahProduk = $group->pluck('variant.product_id')->filter()->unique()->count();

            return (object) [
                'category_id' => $category->id ?? 0,
                'category_name' => $category ? $category->name : 'Tanpa Kategori',
                'jumlah_produk' => $jumlahProduk ?: 1,
                'total_qty' => $totalQty,
                'total_diskon' => $totalDiscount,
                'total_subtotal' => $totalSubtotal,
                'total_modal' => $totalModal,
                'laba_rugi' => $totalLaba,
                'kontribusi_pct' => $kontribusiPct,
                'jumlah_trx' => $jumlahTrx,
            ];
        })->sortByDesc('total_subtotal')->values();

        // 3. Grouped by Variant (existing detail)
        $rowsVariant = $rawItems->groupBy('product_variant_id')
            ->map(function ($group) {
                $first = $group->first();
                $totalQty = $group->sum('qty');
                $totalSubtotal = $group->sum('subtotal');
                $totalDiscount = $group->sum('discount_amount');
                $totalModal = $group->sum('calculated_modal');
                $totalLaba = $totalSubtotal - $totalModal;
                $jumlahTrx = $group->pluck('sale_id')->unique()->count();

                return (object) [
                    'sku' => $first->sku,
                    'product_name' => optional(optional($first->variant)->product)->nama_produk ?? ($first->product_name ?? '-'),
                    'variant_label' => optional($first->variant)->variant_label ?? '',
                    'category_name' => optional(optional(optional($first->variant)->product)->category)->name ?? '-',
                    'harga_jual' => $first->price,
                    'total_qty' => $totalQty,
                    'total_diskon' => $totalDiscount,
                    'total_subtotal' => $totalSubtotal,
                    'total_modal' => $totalModal,
                    'laba_rugi' => $totalLaba,
                    'jumlah_trx' => $jumlahTrx,
                ];
            })
            ->sortByDesc('total_qty')
            ->values();

        $rows = $rowsProduct; // Backward compatibility

        $salesQuery = \App\Models\Sale::whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('status', 'paid')
            ->when($storeId, fn($sq) => $sq->where('store_id', $storeId))
            ->whereBetween('sale_date', [$mulai . ' 00:00:00', $akhir . ' 23:59:59']);

        if (!empty($categoryId)) {
            $salesQuery->whereHas('items.variant.product', function ($p) use ($categoryId) {
                $p->where('category_id', $categoryId);
            });
        }

        $sales = $salesQuery->get();
        $totalTransDiscount = $sales->sum('trans_discount');
        $totalPointDiscount = $sales->sum('point_discount_amount');
        $totalVoucherDiscount = $sales->sum('voucher_discount_amount');

        $selectedCategory = !empty($categoryId) ? \App\Models\ProductCategory::find($categoryId) : null;

        $viewData = compact(
            'rows',
            'rowsProduct',
            'rowsCategory',
            'rowsVariant',
            'mulai',
            'akhir',
            'categoryId',
            'selectedCategory',
            'totalTransDiscount',
            'totalPointDiscount',
            'totalVoucherDiscount'
        );

        if ($request->ajax()) {
            return view('laporan.harian_table', $viewData);
        }

        $categories = $storeId 
            ? \App\Models\ProductCategory::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('laporan.harian', array_merge($viewData, compact('categories')));
    }

    public function exportHarian(Request $request)
    {
        $mulai = $request->mulai ?? now()->toDateString();
        $akhir = $request->akhir ?? now()->toDateString();
        $categoryId = $request->category_id;
        $viewType = $request->view ?? 'all';

        $suffix = $viewType !== 'all' ? "_{$viewType}" : '';
        return Excel::download(
            new \App\Exports\LaporanHarianExport($mulai, $akhir, $categoryId, $viewType),
            'Laporan_Harian' . $suffix . '_' . $mulai . '_to_' . $akhir . '.xlsx'
        );
    }

    public function penerimaanKasFrontliner()
    {
        $frontliners = User::whereHas('stores', function ($q) {
            $q->where('stores.id', session('store_id'));
        })->whereIn('id', function ($q) {
            $q->select('user_id')
                ->from('cash_transactions')
                ->where('store_id', session('store_id'))
                ->whereNotNull('user_id')
                ->distinct();
        })->orderBy('name')->get();

        return view('laporan.penerimaan_kas_frontliner', compact('frontliners'));
    }

    protected function parseDateTimeRange(?string $mulai, ?string $akhir): array
    {
        try {
            $start = $mulai ?: now()->startOfDay()->format('Y-m-d H:i');
            if (str_contains($start, ':')) {
                $parsedStart = Carbon::parse($start)->format('Y-m-d H:i:00');
            } else {
                $parsedStart = Carbon::parse($start)->startOfDay()->format('Y-m-d H:i:s');
            }
        } catch (\Throwable $e) {
            $parsedStart = now()->startOfDay()->format('Y-m-d H:i:s');
        }

        try {
            $end = $akhir ?: now()->endOfDay()->format('Y-m-d H:i');
            if (str_contains($end, ':')) {
                if (substr_count($end, ':') === 1) {
                    $parsedEnd = Carbon::parse($end)->format('Y-m-d H:i:59');
                } else {
                    $parsedEnd = Carbon::parse($end)->format('Y-m-d H:i:s');
                }
            } else {
                $parsedEnd = Carbon::parse($end)->endOfDay()->format('Y-m-d H:i:s');
            }
        } catch (\Throwable $e) {
            $parsedEnd = now()->endOfDay()->format('Y-m-d H:i:s');
        }

        return [$parsedStart, $parsedEnd];
    }

    public function getPenerimaanKas(Request $request)
    {
        $mulai = $request->mulai ?? $request->tanggal ?? now()->startOfMonth()->format('Y-m-d 00:00');
        $akhir = $request->akhir ?? $request->tanggal ?? now()->format('Y-m-d H:i');

        $userId = $request->user_id;

        [$startDateTime, $endDateTime] = $this->parseDateTimeRange($mulai, $akhir);

        // Ambil semua transaksi kas (cash & transfer)
        $kasTransactions = CashTransaction::with('user')
            ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->orderBy('transaction_date', 'asc')
            ->get();

        $cashRows = collect();
        $totalCashMasuk = 0;
        $totalCashKeluar = 0;

        $transferRows = collect();
        $totalTransferMasuk = 0;
        $totalTransferKeluar = 0;

        foreach ($kasTransactions as $trx) {
            $masuk = $trx->direction === 'in' ? $trx->amount : 0;
            $keluar = $trx->direction === 'out' ? $trx->amount : 0;

            if ($trx->payment_method === 'cash') {
                $totalCashMasuk += $masuk;
                $totalCashKeluar += $keluar;
                $cashRows->push((object) [
                    'no' => $cashRows->count() + 1,
                    'transaction_date' => $trx->transaction_date,
                    'transaction_type' => $trx->transaction_type,
                    'ref_type' => $trx->ref_type,
                    'ref_id' => $trx->ref_id,
                    'notes' => $trx->notes,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'petugas' => optional($trx->user)->name ?? '-',
                ]);
            } else {
                $totalTransferMasuk += $masuk;
                $totalTransferKeluar += $keluar;
                $transferRows->push((object) [
                    'no' => $transferRows->count() + 1,
                    'transaction_date' => $trx->transaction_date,
                    'transaction_type' => $trx->transaction_type,
                    'ref_type' => $trx->ref_type,
                    'ref_id' => $trx->ref_id,
                    'notes' => $trx->notes,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'petugas' => optional($trx->user)->name ?? '-',
                ]);
            }
        }

        $saldoCash = $totalCashMasuk - $totalCashKeluar;
        $saldoTransfer = $totalTransferMasuk - $totalTransferKeluar;

        if ($request->ajax()) {
            return view('laporan.penerimaan_kas_table', compact(
                'cashRows', 'totalCashMasuk', 'totalCashKeluar', 'saldoCash',
                'transferRows', 'totalTransferMasuk', 'totalTransferKeluar', 'saldoTransfer',
                'mulai', 'akhir', 'userId'
            ));
        }

        return view('laporan.penerimaan_kas_frontliner', compact(
            'cashRows', 'totalCashMasuk', 'totalCashKeluar', 'saldoCash',
            'transferRows', 'totalTransferMasuk', 'totalTransferKeluar', 'saldoTransfer',
            'mulai', 'akhir'
        ));
    }

    public function exportPenerimaanKas(Request $request)
    {
        $mulai = $request->mulai ?? $request->tanggal ?? now()->startOfMonth()->format('Y-m-d 00:00');
        $akhir = $request->akhir ?? $request->tanggal ?? now()->format('Y-m-d H:i');
        $userId = $request->user_id;
        $paymentMethod = $request->payment_method ?? 'cash';

        $safeMulai = str_replace([':', ' '], ['-', '_'], $mulai);
        $safeAkhir = str_replace([':', ' '], ['-', '_'], $akhir);

        return Excel::download(
            new \App\Exports\PenerimaanKasExport($mulai, $akhir, $userId, $paymentMethod),
            'Laporan_Penerimaan_Kas_' . ($paymentMethod === 'transfer' ? 'Transfer' : 'Cash') . '_' . $safeMulai . '_to_' . $safeAkhir . '.xlsx'
        );
    }

    public function cetakPenerimaanKas(Request $request, $tanggal = null, $userId = null)
    {
        $mulai = $request->mulai ?? $tanggal ?? $request->tanggal ?? now()->startOfMonth()->format('Y-m-d 00:00');
        $akhir = $request->akhir ?? $tanggal ?? $request->tanggal ?? now()->format('Y-m-d H:i');
        $userId = $request->user_id ?? $userId;
        $paymentMethod = $request->payment_method ?? 'cash';

        [$startDateTime, $endDateTime] = $this->parseDateTimeRange($mulai, $akhir);

        $transactions = CashTransaction::with('user')
            ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
            ->where('payment_method', $paymentMethod)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->orderBy('transaction_date', 'asc')
            ->get();

        $totalMasuk = 0;
        $totalKeluar = 0;

        foreach ($transactions as $trx) {
            if ($trx->direction === 'in') {
                $totalMasuk += $trx->amount;
            } else {
                $totalKeluar += $trx->amount;
            }
        }

        $saldo = $totalMasuk - $totalKeluar;

        $pdf = PDF::loadView('laporan.cetakpenerimaankas', compact(
            'transactions',
            'mulai',
            'akhir',
            'totalMasuk',
            'totalKeluar',
            'saldo',
            'paymentMethod'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('penerimaan_kas_' . $paymentMethod . '.pdf');
    }

    public function biayaOperasional()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('laporan.biaya_operasional', compact('categories'));
    }

    public function getBiayaOperasional(Request $request)
    {
        $mulai      = $request->mulai      ?? now()->startOfMonth()->toDateString();
        $akhir      = $request->akhir      ?? now()->toDateString();
        $jenis      = $request->jenis      ?? 'rekap'; // 'rekap' | 'detail'
        $metode     = $request->metode;               // null | 'cash' | 'transfer'
        $categoryId = $request->category_id;

        $expenses = Expense::with(['category', 'user'])
            ->whereBetween('transaction_date', [$mulai, $akhir])
            ->when($metode && $metode !== 'semua', fn($q) => $q->where('payment_method', $metode))
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->orderBy('transaction_date')
            ->get();

        $total             = $expenses->sum('amount');
        $totalTerbayar     = $expenses->sum('paid_amount');
        $totalSisaHutang   = $expenses->sum(fn($e) => $e->remaining_amount);
        $totalCash         = $expenses->where('payment_method', 'cash')->sum('paid_amount');
        $totalTransfer     = $expenses->where('payment_method', 'transfer')->sum('paid_amount');

        if ($jenis === 'rekap') {
            $rows = $expenses
                ->groupBy('expense_category_id')
                ->map(function ($items) {
                    return (object) [
                        'kategori'         => $items->first()->category->name ?? '-',
                        'jumlah_transaksi' => $items->count(),
                        'total_cash'       => $items->where('payment_method', 'cash')->sum('paid_amount'),
                        'total_transfer'   => $items->where('payment_method', 'transfer')->sum('paid_amount'),
                        'total'            => $items->sum('amount'),
                        'total_terbayar'   => $items->sum('paid_amount'),
                        'total_sisa'       => $items->sum(fn($e) => $e->remaining_amount),
                    ];
                })
                ->sortByDesc('total')
                ->values();
        } else {
            $rows = $expenses->values()->map(function ($e, $i) {
                return (object) [
                    'no'             => $i + 1,
                    'tanggal'        => $e->transaction_date->format('d/m/Y'),
                    'kategori'       => $e->category->name ?? '-',
                    'keterangan'     => $e->description,
                    'metode'         => ucfirst($e->payment_method),
                    'jumlah'         => $e->amount,
                    'terbayar'       => $e->paid_amount,
                    'sisa_hutang'    => $e->remaining_amount,
                    'payment_status' => $e->payment_status,
                    'dicatat_oleh'   => optional($e->user)->name ?? '-',
                    'notes'          => $e->notes,
                ];
            });
        }

        if ($request->ajax()) {
            return view(
                'laporan.biaya_operasional_table',
                compact('rows', 'jenis', 'total', 'totalTerbayar', 'totalSisaHutang', 'totalCash', 'totalTransfer', 'mulai', 'akhir', 'metode')
            );
        }

        return view(
            'laporan.biaya_operasional',
            compact('rows', 'jenis', 'total', 'totalTerbayar', 'totalSisaHutang', 'totalCash', 'totalTransfer', 'mulai', 'akhir')
        );
    }

    public function exportBiayaOperasional(Request $request)
    {
        $mulai      = $request->mulai      ?? now()->startOfMonth()->toDateString();
        $akhir      = $request->akhir      ?? now()->toDateString();
        $jenis      = $request->jenis      ?? 'rekap';
        $metode     = $request->metode;
        $categoryId = $request->category_id;

        return Excel::download(
            new LaporanBiayaExport($mulai, $akhir, $jenis, $metode, $categoryId),
            'Laporan_Biaya_Operasional_' . $mulai . '_' . $akhir . '.xlsx'
        );
    }

    public function neraca_lajur()
    {
        return view('laporan.neraca_lajur');
    }

    public function laba_rugi()
    {
        return view('laporan.laba_rugi');
    }

    public function getLabaRugi(Request $request)
    {
        $mulai = $request->mulai ?? now()->startOfMonth()->toDateString();
        $akhir = $request->akhir ?? now()->toDateString();

        // ===== PENJUALAN =====
        $sales = Sale::with([
            'items' => fn($q) => $q->with(['batches', 'variant.product.tenant', 'fnbDetail'])->whereIn('status', ['sold', 'exchanged_in']),
        ])
            ->where('status', 'paid')
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->whereBetween('sale_date', [$mulai . ' 00:00:00', $akhir . ' 23:59:59'])
            ->get();

        $store = \App\Models\Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $omset = $sales->sum('grand_total');
        $tip   = $sales->sum('tip_amount');
        $hpp   = 0;
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($isFnB) {
                    $variant = $item->variant;
                    $tenant = $variant->product->tenant ?? null;
                    $costPriceManual = $item->cost_price ?? ($variant->cost_price_manual ?? 0);
                    if ($tenant) {
                        $commissionAmount = $item->commission_amount ?? ($variant ? $variant->calculateCommission($item->price) : 0);
                        $commission = $commissionAmount * $item->qty;
                        $tenantShare = ($item->price * $item->qty) - $commission;
                        $hpp += $tenantShare + $costPriceManual * $item->qty;
                    } else {
                        $hpp += $costPriceManual * $item->qty;
                    }
                } else {
                    foreach ($item->batches as $batch) {
                        $hpp += $batch->qty * $batch->cost_price;
                    }
                }
            }
        }

        $pendapatanKotor = ($omset + $tip) - $hpp;

        // ===== BIAYA OPERASIONAL =====
        $expenses = Expense::with('category')
            ->whereBetween('transaction_date', [$mulai, $akhir])
            ->orderBy('transaction_date')
            ->get();

        $biayaPerKategori = $expenses
            ->groupBy('expense_category_id')
            ->map(function ($items) {
                return (object) [
                    'kategori' => $items->first()->category->name ?? 'Lainnya',
                    'total'    => $items->sum('amount'),
                    'jumlah'   => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $totalBiaya = $expenses->sum('amount');
        $labaRugi   = $pendapatanKotor - $totalBiaya;

        if ($request->ajax()) {
            return view('laporan.laba_rugi_table', compact(
                'mulai',
                'akhir',
                'omset',
                'tip',
                'hpp',
                'pendapatanKotor',
                'biayaPerKategori',
                'totalBiaya',
                'labaRugi'
            ));
        }

        return view('laporan.laba_rugi', compact(
            'mulai',
            'akhir',
            'omset',
            'tip',
            'hpp',
            'pendapatanKotor',
            'biayaPerKategori',
            'totalBiaya',
            'labaRugi'
        ));
    }

    public function exportLabaRugi(Request $request)
    {
        $mulai = $request->mulai ?? now()->startOfMonth()->toDateString();
        $akhir = $request->akhir ?? now()->toDateString();

        return Excel::download(
            new LaporanLabaRugiExport($mulai, $akhir, session('store_name', '')),
            'Laporan_Laba_Rugi_' . $mulai . '_' . $akhir . '.xlsx'
        );
    }

    public function exportExcel(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->toDateString();

        return Excel::download(
            new LaporanStokExport($tanggal),
            'Laporan_Stok_' . $tanggal . '.xlsx'
        );
    }

    public function tenantReport()
    {
        return view('laporan.tenant');
    }

    public function getTenantReport(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;

        $items = SaleItem::with(['variant.product.tenant', 'fnbDetail', 'sale'])
            ->whereHas('sale', function ($q) use ($mulai, $akhir) {
                $q->where('status', 'paid')
                    ->whereNull('ref_sale_id')
                    ->whereDoesntHave('refunds')
                    ->whereBetween('sale_date', [$mulai . " 00:00:00", $akhir . " 23:59:59"]);
            })
            ->whereIn('status', ['sold', 'exchanged_in'])
            ->whereHas('variant.product', function ($q) {
                $q->whereNotNull('tenant_id');
            })
            ->get();

        $rows = $items->groupBy(function ($item) {
            return $item->variant->product->tenant_id;
        })->map(function ($tenantItems, $tenantId) {
            $first = $tenantItems->first();
            $tenant = $first->variant->product->tenant;

            $totalQty = $tenantItems->sum('qty');
            $grossSales = 0;
            $totalCommission = 0;

            foreach ($tenantItems as $item) {
                $subtotal = $item->price * $item->qty;
                $commissionAmount = $item->commission_amount ?? ($item->variant ? $item->variant->calculateCommission($item->price) : 0);
                $commission = $commissionAmount * $item->qty;
                $grossSales += $subtotal;
                $totalCommission += $commission;
            }

            $tenantShare = $grossSales - $totalCommission;

            return (object) [
                'tenant_id'   => $tenantId,
                'kode_tenant' => $tenant->kode_tenant ?? '-',
                'nama_tenant' => $tenant->nama_tenant ?? 'Unknown Tenant',
                'total_qty'   => $totalQty,
                'gross_sales' => $grossSales,
                'commission'  => $totalCommission,
                'net_payout'  => $tenantShare,
            ];
        })->values();

        $totalPenjualan = $rows->sum('gross_sales');
        $totalKomisi = $rows->sum('commission');
        $totalHakTenant = $rows->sum('net_payout');

        if ($request->ajax()) {
            return view('laporan.tenant_table', compact('rows', 'mulai', 'akhir', 'totalPenjualan', 'totalKomisi', 'totalHakTenant'));
        }

        return view('laporan.tenant', compact('rows', 'mulai', 'akhir', 'totalPenjualan', 'totalKomisi', 'totalHakTenant'));
    }

    public function exportTenantReport(Request $request)
    {
        $mulai = $request->mulai ?? now()->startOfMonth()->toDateString();
        $akhir = $request->akhir ?? now()->toDateString();

        return Excel::download(
            new \App\Exports\LaporanTenantExport($mulai, $akhir),
            'Laporan_Piutang_Tenant_' . $mulai . '_' . $akhir . '.xlsx'
        );
    }

    public function laporanHutang(Request $request)
    {
        return view('laporan.hutang');
    }

    public function getLaporanHutang(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;

        $query = Customer::query()
            ->with(['sales' => function ($q) use ($mulai, $akhir) {
                $q->where('payment_status', 'hutang')
                    ->where('status', '!=', 'void');
                if ($mulai && $akhir) {
                    $q->whereBetween('sale_date', [$mulai . ' 00:00:00', $akhir . ' 23:59:59']);
                }
            }]);

        $customers = $query->get()->map(function ($customer) {
            $sales = $customer->sales;
            $totalInvoices = $sales->count();
            $totalDebt = $sales->sum('grand_total');
            $totalPaid = $sales->sum('paid_amount');
            $remaining = $totalDebt - $totalPaid;

            return (object) [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone ?? '-',
                'alamat' => $customer->alamat ?? '-',
                'total_invoices' => $totalInvoices,
                'total_debt' => $totalDebt,
                'total_paid' => $totalPaid,
                'remaining' => $remaining,
            ];
        });

        // Filter: Hanya tampilkan customer yang memiliki invoice hutang
        $rows = $customers->filter(function ($item) {
            return $item->total_invoices > 0;
        })->values();

        $totalHutang = $rows->sum('total_debt');
        $totalTerbayar = $rows->sum('total_paid');
        $totalSisaHutang = $rows->sum('remaining');

        if ($request->ajax()) {
            return view('laporan.hutang_table', compact('rows', 'mulai', 'akhir', 'totalHutang', 'totalTerbayar', 'totalSisaHutang'));
        }

        return view('laporan.hutang', compact('rows', 'mulai', 'akhir', 'totalHutang', 'totalTerbayar', 'totalSisaHutang'));
    }

    public function exportLaporanHutang(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;

        $filename = 'Laporan_Hutang_Mitra';
        if ($mulai && $akhir) {
            $filename .= '_' . $mulai . '_' . $akhir;
        } else {
            $filename .= '_Semua_Periode';
        }

        return Excel::download(
            new LaporanHutangExport($mulai, $akhir),
            $filename . '.xlsx'
        );
    }

    public function penukaranPoin()
    {
        return view('laporan.penukaran_poin');
    }

    public function getPenukaranPoin(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;
        $storeId = session('store_id');

        $query = \App\Models\MemberRedemption::with(['member', 'rewardItem', 'sale'])
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$mulai . " 00:00:00", $akhir . " 23:59:59"]);

        $redemptions = $query->orderBy('created_at', 'desc')->get();

        $totalPointsSpent = $redemptions->sum('points_spent');
        $totalPhysical = $redemptions->filter(fn($r) => $r->rewardItem->reward_type === 'physical')->count();
        $totalVoucherPercent = $redemptions->filter(fn($r) => $r->rewardItem->reward_type === 'voucher_percent')->count();
        $totalVoucherNominal = $redemptions->filter(fn($r) => $r->rewardItem->reward_type === 'voucher_nominal')->count();

        if ($request->ajax()) {
            return view('laporan.datapenukaran_poin_table', compact(
                'redemptions', 'mulai', 'akhir', 
                'totalPointsSpent', 'totalPhysical', 'totalVoucherPercent', 'totalVoucherNominal'
            ));
        }

        return view('laporan.penukaran_poin', compact(
            'redemptions', 'mulai', 'akhir', 
            'totalPointsSpent', 'totalPhysical', 'totalVoucherPercent', 'totalVoucherNominal'
        ));
    }

    public function exportPenukaranPoin(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;

        return Excel::download(
            new \App\Exports\LaporanPenukaranPoinExport($mulai, $akhir),
            'laporan_penukaran_poin_' . $mulai . '_' . $akhir . '.xlsx'
        );
    }

    public function laporanCustomer(Request $request)
    {
        $customFields = \App\Models\CustomerCustomField::where('store_id', session('store_id'))->get();
        return view('laporan.customer', compact('customFields'));
    }

    public function getLaporanCustomer(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;
        $search = $request->search;
        $storeId = session('store_id');

        $query = Customer::where('store_id', $storeId);

        if ($request->filled('mulai') && $request->filled('akhir')) {
            $query->whereBetween('created_at', [$mulai . " 00:00:00", $akhir . " 23:59:59"]);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name', 'asc')->get();
        $customFields = \App\Models\CustomerCustomField::where('store_id', $storeId)->get();

        if ($request->ajax()) {
            return view('laporan.customer_table', compact('customers', 'customFields', 'mulai', 'akhir', 'search'));
        }

        return view('laporan.customer', compact('customers', 'customFields', 'mulai', 'akhir', 'search'));
    }

    public function exportLaporanCustomer(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;
        $search = $request->search;

        $filename = 'Laporan_Customer';
        if ($mulai && $akhir) {
            $filename .= '_' . $mulai . '_' . $akhir;
        } else {
            $filename .= '_Semua_Periode';
        }

        return Excel::download(
            new \App\Exports\LaporanCustomerExport($mulai, $akhir, $search),
            $filename . '.xlsx'
        );
    }

    public function laporanMember(Request $request)
    {
        return view('laporan.member');
    }

    public function getLaporanMember(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;
        $search = $request->search;

        $storeId = session('store_id');
        $store = Store::find($storeId);
        $businessId = $store ? ($store->business_id ?: 1) : 1;

        $query = Member::where('business_id', $businessId);

        if ($request->filled('mulai') && $request->filled('akhir')) {
            $query->whereBetween('created_at', [$mulai . " 00:00:00", $akhir . " 23:59:59"]);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('name', 'asc')->get();
        $totalMember = $members->count();
        $totalPoin = $members->sum('total_points');
        $memberAktif = $members->where('is_active', true)->count();

        if ($request->ajax()) {
            return view('laporan.member_table', compact('members', 'totalMember', 'totalPoin', 'memberAktif', 'mulai', 'akhir', 'search'));
        }

        return view('laporan.member', compact('members', 'totalMember', 'totalPoin', 'memberAktif', 'mulai', 'akhir', 'search'));
    }

    public function exportLaporanMember(Request $request)
    {
        $mulai = $request->mulai;
        $akhir = $request->akhir;
        $search = $request->search;

        $filename = 'Laporan_Member';
        if ($mulai && $akhir) {
            $filename .= '_' . $mulai . '_' . $akhir;
        } else {
            $filename .= '_Semua_Periode';
        }

        return Excel::download(
            new LaporanMemberExport($mulai, $akhir, $search),
            $filename . '.xlsx'
        );
    }
}
