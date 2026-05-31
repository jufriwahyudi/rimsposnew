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
use App\Exports\LaporanBiayaExport;
use App\Exports\LaporanStokExport;
use App\Exports\LaporanPenjualanExport;
use App\Exports\LaporanPenjualanNSEExport;
use App\Exports\LaporanLabaRugiExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

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
            ]);
        }

        $totalPenjualan = $rows->sum('jumlah_penjualan');
        $totalModal = $rows->sum('modal');
        $totalLabaRugi = $rows->sum('laba_rugi');

        // Jika request dari AJAX, return partial view (hanya tabel)
        if ($request->ajax()) {
            return view('laporan.datapenjualan_table', compact('rows', 'mulai', 'akhir', 'id_divisi', 'totalPenjualan', 'totalModal', 'totalLabaRugi'));
        }

        // Jika request normal, return full page
        return view('laporan.datapenjualan', compact('rows', 'mulai', 'akhir', 'id_divisi', 'totalPenjualan', 'totalModal', 'totalLabaRugi'));
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
        return view('laporan.harian');
    }

    public function getharian(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->toDateString();

        $store = \App\Models\Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $rows = SaleItem::with(['variant.product', 'fnbDetail', 'batches', 'sale'])
            ->whereHas('sale', function ($q) use ($tanggal) {
                $q->whereNull('ref_sale_id')
                    ->whereDoesntHave('refunds')
                    ->whereDate('sale_date', $tanggal);
            })
            ->whereIn('status', ['sold', 'exchanged_in'])
            ->get()
            ->groupBy('product_variant_id')
            ->map(function ($items, $variantId) use ($isFnB) {
                $first = $items->first();
                $totalQty = $items->sum('qty');
                $totalSubtotal = $items->sum('subtotal');
                $totalDiscount = $items->sum('discount_amount');
                $totalModal = 0;

                if ($isFnB) {
                    foreach ($items as $item) {
                        $variant = $item->variant;
                        $tenantId = $variant && $variant->product ? $variant->product->tenant_id : null;
                        $costPriceManual = $item->cost_price ?? ($variant->cost_price_manual ?? 0);
                        if ($tenantId) {
                            $commissionAmount = $item->commission_amount ?? ($variant ? $variant->calculateCommission($item->price) : 0);
                            $costPrice = ($item->price - $commissionAmount) + $costPriceManual;
                        } else {
                            $costPrice = $costPriceManual;
                        }
                        $totalModal += ($item->qty * $costPrice);
                    }
                } else {
                    foreach ($items as $item) {
                        foreach ($item->batches as $batch) {
                            $totalModal += ($batch->qty * $batch->cost_price);
                        }
                    }
                }

                return (object) [
                    'sku' => $first->sku,
                    'product_name' => $first->variant ? $first->variant->product->nama_produk : ($first->product_name ?? '-'),
                    'variant_label' => optional($first->variant)->variant_label ?? '',
                    'harga_jual' => $first->price,
                    'total_qty' => $totalQty,
                    'total_diskon' => $totalDiscount,
                    'total_subtotal' => $totalSubtotal,
                    'total_modal' => $totalModal,
                    'laba_rugi' => $totalSubtotal - $totalModal,
                    'jumlah_trx' => $items->count(),
                ];
            })
            ->sortByDesc('total_qty')
            ->values();

        if ($request->ajax()) {
            return view('laporan.harian_table', compact('rows', 'tanggal'));
        }

        return view('laporan.harian', compact('rows', 'tanggal'));
    }

    public function exportHarian(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->toDateString();

        return Excel::download(
            new \App\Exports\LaporanHarianExport($tanggal),
            'Laporan_Harian_' . $tanggal . '.xlsx'
        );
    }

    public function penerimaanKasFrontliner()
    {
        $frontliners = User::whereIn('id', function ($q) {
            $q->select('user_id')->from('cash_transactions')->whereNotNull('user_id')->distinct();
        })->orderBy('name')->get();

        return view('laporan.penerimaan_kas_frontliner', compact('frontliners'));
    }

    public function getPenerimaanKas(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->toDateString();

        $userId = $request->user_id;

        // Kas masuk (penerimaan): sale cash, dll
        $kasTransactions = CashTransaction::with('user')
            ->whereBetween('transaction_date', [$tanggal . ' 00:00:00', $tanggal . ' 23:59:59'])
            ->where('payment_method', 'cash')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->orderBy('transaction_date', 'asc')
            ->get();

        $rows = collect();
        $totalMasuk = 0;
        $totalKeluar = 0;

        foreach ($kasTransactions as $i => $trx) {
            $masuk = $trx->direction === 'in' ? $trx->amount : 0;
            $keluar = $trx->direction === 'out' ? $trx->amount : 0;
            $totalMasuk += $masuk;
            $totalKeluar += $keluar;

            $rows->push((object) [
                'no' => $i + 1,
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

        $saldo = $totalMasuk - $totalKeluar;

        if ($request->ajax()) {
            return view('laporan.penerimaan_kas_table', compact('rows', 'tanggal', 'totalMasuk', 'totalKeluar', 'saldo', 'userId'));
        }

        return view('laporan.penerimaan_kas_frontliner', compact('rows', 'tanggal', 'totalMasuk', 'totalKeluar', 'saldo'));
    }

    public function exportPenerimaanKas(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->toDateString();
        $userId = $request->user_id;

        return Excel::download(
            new \App\Exports\PenerimaanKasExport($tanggal, $userId),
            'Laporan_Penerimaan_Kas_' . $tanggal . '.xlsx'
        );
    }

    public function cetakPenerimaanKas($tanggal, $userId = null)
    {
        $transactions = CashTransaction::with('user')
            ->whereBetween('transaction_date', [$tanggal . ' 00:00:00', $tanggal . ' 23:59:59'])
            ->where('payment_method', 'cash')
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
            'tanggal',
            'totalMasuk',
            'totalKeluar',
            'saldo'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('penerimaan_kas.pdf');
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

        $total         = $expenses->sum('amount');
        $totalCash     = $expenses->where('payment_method', 'cash')->sum('amount');
        $totalTransfer = $expenses->where('payment_method', 'transfer')->sum('amount');

        if ($jenis === 'rekap') {
            $rows = $expenses
                ->groupBy('expense_category_id')
                ->map(function ($items) {
                    return (object) [
                        'kategori'         => $items->first()->category->name ?? '-',
                        'jumlah_transaksi' => $items->count(),
                        'total_cash'       => $items->where('payment_method', 'cash')->sum('amount'),
                        'total_transfer'   => $items->where('payment_method', 'transfer')->sum('amount'),
                        'total'            => $items->sum('amount'),
                    ];
                })
                ->sortByDesc('total')
                ->values();
        } else {
            $rows = $expenses->values()->map(function ($e, $i) {
                return (object) [
                    'no'           => $i + 1,
                    'tanggal'      => $e->transaction_date->format('d/m/Y'),
                    'kategori'     => $e->category->name ?? '-',
                    'keterangan'   => $e->description,
                    'metode'       => ucfirst($e->payment_method),
                    'jumlah'       => $e->amount,
                    'dicatat_oleh' => optional($e->user)->name ?? '-',
                    'notes'        => $e->notes,
                ];
            });
        }

        if ($request->ajax()) {
            return view(
                'laporan.biaya_operasional_table',
                compact('rows', 'jenis', 'total', 'totalCash', 'totalTransfer', 'mulai', 'akhir', 'metode')
            );
        }

        return view(
            'laporan.biaya_operasional',
            compact('rows', 'jenis', 'total', 'totalCash', 'totalTransfer', 'mulai', 'akhir')
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

        $pendapatanKotor = $omset - $hpp;

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
}
