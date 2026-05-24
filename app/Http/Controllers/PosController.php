<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\NseCalonSiswa;
use App\Models\ProductVariant;
use App\Models\Rekening;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\SeragamDistribusi;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\JournalEntryService;
use App\Services\JournalFromCashTransactionService;
use App\Services\Printer\EscPosReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PosController extends Controller
{
    /**
     * GET /api/pos/rekening?store_id=N
     * Returns bank accounts for a given store (used for transfer payment in mobile).
     */
    public function apiCustomers(Request $request)
    {
        $storeId = $request->integer('store_id');
        $search  = $request->string('search');

        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $hasAccess = auth()->user()
            ->stores()
            ->where('stores.id', $storeId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $customers = \App\Models\Customer::where('store_id', $storeId)
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $search . '%');
            }))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone']);

        return response()->json(['data' => $customers]);
    }

    public function apiRekening(Request $request)
    {
        $storeId = $request->integer('store_id');

        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $hasAccess = auth()->user()
            ->stores()
            ->where('stores.id', $storeId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $rekening = Rekening::where('store_id', $storeId)
            ->orderBy('bank_rek')
            ->get(['id', 'no_rek', 'nama_rek', 'bank_rek']);

        return response()->json(['data' => $rekening]);
    }

    public function index()
    {
        // dd($roleuserlist);
        $akunkas = Rekening::all();
        // dd($akunkas);
        $akunkasir = 0;

        return view('pos.index', compact('akunkas', 'akunkasir'));
    }
    public function sales()
    {
        return view('pos.sales');
    }
    public function datatable(Request $request)
    {
        $query = Sale::with('cashier', 'refunds', 'payments')
            ->whereNull('ref_sale_id')
            ->orderByDesc('sale_date');

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('sale_date', [
                $request->from_date . ' 00:00:00',
                $request->to_date   . ' 23:59:59',
            ]);
        }
        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn(
                'sale_date',
                fn($s) =>
                $s->sale_date->format('d-m-Y H:i')
            )

            ->addColumn(
                'kasir',
                fn($s) =>
                $s->cashier?->name ?? '-'
            )

            ->editColumn(
                'grand_total',
                fn($s) =>
                number_format($s->grand_total, 0, ',', '.')
            )

            ->addColumn('payment_method', function ($s) {
                $methods = $s->payments->pluck('payment_method')->unique()->map(function ($m) {
                    return strtoupper($m);
                })->implode(', ');

                return $methods ?: '-';
            })

            ->addColumn('payment_status', function ($s) {
                return $s->payment_status ? ucfirst($s->payment_status) : '-';
            })

            ->editColumn('status', function ($s) {
                if ($s->refunds->count() > 0) {
                    return '<span class="badge bg-warning text-dark">REFUND</span>';
                }

                if ($s->status === 'void') {
                    return '<span class="badge bg-danger">VOID</span>';
                }

                return '<span class="badge bg-success">PAID</span>';
            })


            ->addColumn('action', function ($s) {
                return '
                    <button class="btn btn-sm btn-primary"
                        onclick="Sales.showDetail(' . $s->id . ')">
                        Detail
                    </button>
                ';
            })

            ->rawColumns(['status', 'action'])
            ->make(true);
    }
    public function show(Sale $sale)
    {
        $sale->load([
            'items.variant.variantAttributes.value',
            'items.batches',
            'refunds',
            'cashier'
        ]);
        // dd(json_encode($sale, JSON_PRETTY_PRINT));
        $variants = ProductVariant::with('product')->get();
        $akunkas = Rekening::all();
        $akunkasir = 0;

        return view('pos.show', compact('sale', 'variants', 'akunkas', 'akunkasir'));
    }
    /**
     * Endpoint pencarian produk (SKU / barcode / nama)
     * Service nanti diinject
     */
    public function findProduct(Request $request)
    {
        $q = trim($request->query('q'));

        // helper biar tidak nulis berulang
        $format = function ($v) {
            return [
                'id'         => $v->id,
                'product_id' => $v->product_id,
                'sku'        => $v->sku,
                'name'       => $v->product->nama_produk,
                'variant'    => $v->variant_label,
                'price'      => (float) $v->harga_jual,
                'stok'       => (int) $v->stok_store,
            ];
        };

        // 1. Cek SKU / barcode aktif
        $variant = ProductVariant::with(['product', 'variantAttributes.value', 'barcodeActive'])
            ->where('sku', $q)
            ->orWhereHas('barcodes', function ($q2) use ($q) {
                $q2->where('barcode', $q);
            })
            ->first();

        if ($variant) {
            return response()->json([
                'type' => 'single',
                'data' => $format($variant)
            ]);
        }

        // 2. Search nama produk
        $variants = ProductVariant::with(['product', 'variantAttributes.value'])
            ->where(function ($q2) use ($q) {
                $q2->where('variant_name', 'like', "%{$q}%")
                    ->orWhereHas('product', function ($q3) use ($q) {
                        $q3->where('nama_produk', 'like', "%{$q}%");
                    });
            })
            ->where('is_active', 'Y')
            ->get();

        if ($variants->count() === 1) {
            return response()->json([
                'type' => 'single',
                'data' => $format($variants->first())
            ]);
        }

        if ($variants->count() > 1) {
            return response()->json([
                'type' => 'multiple',
                'data' => $variants->map($format)
            ]);
        }

        return response()->json(['message' => 'Produk tidak ditemukan'], 404);
    }


    /**
     * Checkout (placeholder)
     */
    public function checkout(Request $request)
    {
        try {
            $sale = DB::transaction(function () use ($request) {

                $cart = $request->cart;

                $paymentMethod   = $cart['payment_method'];
                $paidAmount      = $cart['paid_amount'];
                $cashAmount      = $cart['cash_amount'] ?? 0;
                $transferAmount  = $cart['transfer_amount'] ?? 0;
                $akunKasir       = $cart['akun_kasir'] ?? null;
                $akunBank        = $cart['akun_bank'] ?? null;
                $transactionDate = $cart['transaction_date'] ? $cart['transaction_date'] . ' ' . now()->format('H:i:s') : now();
                $customerName    = $cart['customer_name'] ?? 'Umum';

                // =========================
                // 1️⃣ VALIDASI
                // =========================
                if ($paymentMethod === 'split') {
                    if (($cashAmount + $transferAmount) != $paidAmount) {
                        throw new \Exception('Total split payment tidak sesuai');
                    }
                }

                if ($paidAmount < $cart['total']) {
                    throw new \Exception('Pembayaran kurang dari total belanja');
                }

                // =========================
                // 2️⃣ CREATE SALE
                // =========================
                $sale = Sale::create([
                    'store_id'       => session('store_id'),
                    'invoice_number' => $this->generateInvoice(),
                    'sale_date'      => $transactionDate,
                    'sale_type'      => 'retail',

                    'customer_id'    => null,
                    'customer_name'  => $customerName,
                    'user_id'        => auth()->id(),

                    'subtotal'       => $cart['subtotal'],
                    'discount_total' => $cart['discount_total'],
                    'trans_discount' => $cart['transaction_discount'] ?? 0,
                    'tax_total'      => 0,
                    'grand_total'    => $cart['total'],

                    'paid_amount'    => $paidAmount,
                    'change_amount'  => max(0, $cashAmount - $cart['total']),
                    'status'         => 'paid',
                ]);

                // =========================
                // 3️⃣ SALE ITEMS + FIFO
                // =========================
                foreach ($cart['items'] as $item) {

                    $saleItem = SaleItem::create([
                        'sale_id'            => $sale->id,
                        'product_id'         => $item['product_id'],
                        'product_variant_id' => $item['variant_id'] ?? null,
                        'sku'                => $item['sku'],
                        'product_name'       => $item['variant'] ?? $item['name'],
                        'price'              => $item['price'],
                        'qty'                => $item['qty'],
                        'discount_amount'    => $item['discount_amount'],
                        'subtotal'           => $item['subtotal'],
                    ]);

                    $this->issueFIFOWithBatchLog(
                        $transactionDate,
                        $item['variant_id'],
                        'store',
                        $item['qty'],
                        $saleItem
                    );
                }

                // =========================
                // 4️⃣ CASH TRANSACTION (CASH)
                // =========================
                if ($cashAmount > 0) {
                    CashTransaction::create([
                        'store_id'         => session('store_id'),
                        'ref_type'         => 'SalePos',
                        'ref_id'           => $sale->id,
                        'transaction_type' => 'sale',
                        'payment_method'   => 'cash',
                        'account_code'     => $akunKasir,
                        'amount'           => $cashAmount > $cart['total'] ? $cart['total'] : $cashAmount,
                        'direction'        => 'in',
                        'transaction_date' => $transactionDate,
                        'user_id'          => auth()->id(),
                        'notes'            => 'Penjualan POS (Cash) #' . $sale->invoice_number,
                    ]);
                }

                // =========================
                // 5️⃣ CASH TRANSACTION (TRANSFER)
                // =========================
                if ($transferAmount > 0) {
                    CashTransaction::create([
                        'store_id'         => session('store_id'),
                        'ref_type'         => 'SalePos',
                        'ref_id'           => $sale->id,
                        'transaction_type' => 'sale',
                        'payment_method'   => 'transfer',
                        'account_code'     => $akunBank,
                        'amount'           => $transferAmount > $cart['total'] ? $cart['total'] : $transferAmount,
                        'direction'        => 'in',
                        'transaction_date' => $transactionDate,
                        'user_id'          => auth()->id(),
                        'notes'            => 'Penjualan POS (Transfer) #' . $sale->invoice_number,
                    ]);
                }

                // jika pembayaran diskon 100% (gratisan), tetap buat cash transaction dengan amount 0 agar bisa tercatat di jurnal
                if ($paidAmount == 0) {
                    CashTransaction::create([
                        'store_id'         => session('store_id'),
                        'ref_type'         => 'SalePos',
                        'ref_id'           => $sale->id,
                        'transaction_type' => 'sale',
                        'payment_method'   => 'cash',
                        'account_code'     => $akunKasir,
                        'amount'           => 0,
                        'direction'        => 'in',
                        'transaction_date' => $transactionDate,
                        'user_id'          => auth()->id(),
                        'notes'            => 'Penjualan POS (Gratis) #' . $sale->invoice_number,
                    ]);
                }

                // Pembukuan jurnal
                if (config('app.jurnal_transaksi')) {
                    $service = new JournalFromCashTransactionService();
                    $service->createForSale($sale->id);
                }

                return $sale;
            });

            return response()->json([
                'message' => 'Transaksi berhasil',
                'invoice' => $sale->invoice_number,
                'sale_id' => $sale->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Transaksi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API Checkout — Mobile app version.
     * Accepts store_id from request body instead of session.
     */
    public function apiCheckout(Request $request)
    {
        $storeId = $request->input('store_id');

        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        // Handle bukti_bayar upload (optional, for transfer/split)
        $buktiBayarPath = null;
        if ($request->hasFile('bukti_bayar') && $request->file('bukti_bayar')->isValid()) {
            $buktiBayarPath = $request->file('bukti_bayar')
                ->store('bukti_bayar', 'public');
        }

        try {
            $sale = DB::transaction(function () use ($request, $storeId, $buktiBayarPath) {

                // Support both JSON body and multipart/form-data
                $cart = $request->input('cart');
                if (is_string($cart)) {
                    $cart = json_decode($cart, true) ?? [];
                }

                $paymentMethod  = $cart['payment_method'];
                $paidAmount     = $cart['paid_amount'];
                $cashAmount     = $cart['cash_amount'] ?? 0;
                $transferAmount = $cart['transfer_amount'] ?? 0;
                $akunBank       = $cart['akun_bank'] ?? null;
                $transactionDate = $cart['transaction_date']
                    ? $cart['transaction_date'] . ' ' . now()->format('H:i:s')
                    : now();
                $customerName  = $cart['customer_name'] ?? 'Umum';
                $customerPhone = $cart['customer_phone'] ?? null;
                $customerId    = null;

                // ── Hutang: buat/temukan pelanggan ──────────────────────────
                if ($paymentMethod === 'hutang') {
                    if (empty($customerName) || $customerName === 'Umum') {
                        throw new \Exception('Nama pelanggan wajib diisi untuk transaksi hutang');
                    }
                    // Cari customer yang sudah ada by id, atau by phone, atau buat baru
                    if (!empty($cart['customer_id'])) {
                        $customer = \App\Models\Customer::where('store_id', $storeId)
                            ->find($cart['customer_id']);
                    }
                    if (empty($customer) && $customerPhone) {
                        $customer = \App\Models\Customer::firstOrCreate(
                            ['store_id' => $storeId, 'phone' => $customerPhone],
                            ['name' => $customerName]
                        );
                    }
                    if (empty($customer)) {
                        $customer = \App\Models\Customer::create([
                            'store_id' => $storeId,
                            'name'     => $customerName,
                            'phone'    => $customerPhone,
                        ]);
                    }
                    $customerId   = $customer->id;
                    $customerName = $customer->name;
                    $paidAmount   = 0;
                }

                if ($paymentMethod !== 'hutang' && $paidAmount < $cart['total']) {
                    throw new \Exception('Pembayaran kurang dari total belanja');
                }

                $sale = Sale::create([
                    'store_id'       => $storeId,
                    'invoice_number' => $this->generateInvoice(),
                    'sale_date'      => $transactionDate,
                    'sale_type'      => 'retail',
                    'customer_id'    => $customerId,
                    'customer_name'  => $customerName,
                    'customer_phone' => $customerPhone,
                    'user_id'        => auth()->id(),
                    'subtotal'       => $cart['subtotal'],
                    'discount_total' => $cart['discount_total'],
                    'trans_discount' => $cart['transaction_discount'] ?? 0,
                    'tax_total'      => 0,
                    'grand_total'    => $cart['total'],
                    'paid_amount'    => $paidAmount,
                    'change_amount'  => max(0, $cashAmount - $cart['total']),
                    'status'         => 'paid',
                    'payment_status' => $paymentMethod === 'hutang' ? 'hutang' : 'lunas',
                ]);

                foreach ($cart['items'] as $item) {
                    $saleItem = SaleItem::create([
                        'sale_id'            => $sale->id,
                        'product_id'         => $item['product_id'],
                        'product_variant_id' => $item['variant_id'] ?? null,
                        'sku'                => $item['sku'],
                        'product_name'       => $item['variant'] ?? $item['name'],
                        'price'              => $item['price'],
                        'qty'                => $item['qty'],
                        'discount_amount'    => $item['discount_amount'],
                        'subtotal'           => $item['subtotal'],
                    ]);

                    $this->issueFIFOWithBatchLog(
                        $transactionDate,
                        $item['variant_id'],
                        'store',
                        $item['qty'],
                        $saleItem
                    );
                }

                if ($cashAmount > 0) {
                    CashTransaction::create([
                        'store_id'         => $storeId,
                        'ref_type'         => 'SalePos',
                        'ref_id'           => $sale->id,
                        'transaction_type' => 'sale',
                        'payment_method'   => 'cash',
                        'account_code'     => 0,
                        'amount'           => min($cashAmount, $cart['total']),
                        'direction'        => 'in',
                        'transaction_date' => $transactionDate,
                        'user_id'          => auth()->id(),
                        'notes'            => 'POS Mobile (Cash) #' . $sale->invoice_number,
                    ]);
                }

                if ($transferAmount > 0) {
                    CashTransaction::create([
                        'store_id'         => $storeId,
                        'ref_type'         => 'SalePos',
                        'ref_id'           => $sale->id,
                        'transaction_type' => 'sale',
                        'payment_method'   => 'transfer',
                        'account_code'     => $akunBank,
                        'amount'           => min($transferAmount, $cart['total']),
                        'direction'        => 'in',
                        'transaction_date' => $transactionDate,
                        'user_id'          => auth()->id(),
                        'notes'            => 'POS Mobile (Transfer) #' . $sale->invoice_number,
                        'bukti_bayar'      => $buktiBayarPath,
                    ]);
                }

                return $sale;
            });

            return response()->json([
                'message' => 'Transaksi berhasil',
                'invoice' => $sale->invoice_number,
                'sale_id' => $sale->id,
                'change'  => $sale->change_amount,
            ]);
        } catch (\Exception $e) {
            // Clean up uploaded file if transaction failed
            if ($buktiBayarPath) {
                \Storage::disk('public')->delete($buktiBayarPath);
            }
            return response()->json([
                'message' => 'Transaksi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: List sales for the authenticated user and given store (mobile).
     * GET /api/pos/sales?store_id=N&from=YYYY-MM-DD&to=YYYY-MM-DD&page=N&per_page=20
     * Default: last 30 days when no date filter is provided.
     */
    public function apiSales(Request $request)
    {
        $storeId = $request->integer('store_id');

        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        // Pastikan user memiliki akses ke store ini
        $hasAccess = auth()->user()
            ->stores()
            ->where('stores.id', $storeId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        // Parse & validasi tanggal — default 30 hari terakhir
        try {
            $from = $request->from
                ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->from)->startOfDay()
                : now()->subDays(30)->startOfDay();

            $to = $request->to
                ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->to)->endOfDay()
                : now()->endOfDay();
        } catch (\Exception) {
            return response()->json(['message' => 'Format tanggal tidak valid (YYYY-MM-DD)'], 422);
        }

        if ($from->gt($to)) {
            return response()->json(['message' => 'Tanggal awal tidak boleh melebihi tanggal akhir'], 422);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);

        $customerSearch   = trim((string) $request->query('customer_name', ''));
        $paymentStatusFilter = $request->query('payment_status'); // 'lunas' | 'hutang' | null

        Log::alert('API Sales request', [
            'user_id' => auth()->id(),
            'store_id' => $storeId,
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'customer_search' => $customerSearch,
            'payment_status_filter' => $paymentStatusFilter,
        ]);

        $paginated = Sale::with('payments')
            ->withCount('items')
            ->where('store_id', $storeId)
            ->where('user_id', auth()->id())
            ->whereNull('ref_sale_id')
            ->whereBetween('sale_date', [$from, $to])
            ->when($customerSearch !== '', function ($q) use ($customerSearch) {
                $q->where('customer_name', 'like', '%' . $customerSearch . '%');
            })
            ->when($paymentStatusFilter, function ($q) use ($paymentStatusFilter) {
                $q->where('payment_status', $paymentStatusFilter);
            })
            ->orderByDesc('sale_date')
            ->paginate($perPage);


        $data = $paginated->getCollection()->map(function ($sale) {
            $paymentMethods = $sale->payments
                ->pluck('payment_method')
                ->unique()
                ->values();

            return [
                'id'              => $sale->id,
                'invoice_number'  => $sale->invoice_number,
                'sale_date'       => $sale->sale_date->format('Y-m-d H:i:s'),
                'customer_name'   => $sale->customer_name,
                'grand_total'     => (float) $sale->grand_total,
                'paid_amount'     => (float) $sale->paid_amount,
                'change_amount'   => (float) $sale->change_amount,
                'status'          => $sale->status,
                'payment_status'  => $sale->payment_status,
                'payment_methods' => $paymentMethods,
                'items_count'     => $sale->items_count,
            ];
        });

        Log::info('API Sales fetched', [
            'user_id' => auth()->id(),
            'store_id' => $storeId,
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'result_count' => count($data),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'from'         => $from->format('Y-m-d'),
                'to'           => $to->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * API: Sale detail with items and payment breakdown (mobile).
     * GET /api/pos/sales/{id}?store_id=N
     */
    /**
     * GET /api/pos/sales/{id}/receipt?store_id=N
     *
     * Hasilkan Android Intent URI untuk dicetak via RawBT.
     * Response: { paper, intent_uri }
     */
    public function apiReceipt(Request $request, int $id)
    {
        $storeId = $request->integer('store_id');

        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $hasAccess = auth()->user()
            ->stores()
            ->where('stores.id', $storeId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $sale = Sale::with(['items', 'cashier'])
            ->where('store_id', $storeId)
            ->findOrFail($id);

        $store = Store::findOrFail($storeId);
        $paper = $store->printer_type ?? '80mm';

        if (!in_array($paper, ['58mm', '80mm'])) {
            $paper = '80mm';
        }

        $items = $sale->items
            ->filter(fn($i) => !in_array($i->status, ['voided', 'exchanged_out']))
            ->map(fn($item) => [
                'name'  => $item->product_name,
                'sku'   => $item->sku,
                'qty'   => $item->qty,
                'price' => round($item->price),
            ])->values()->toArray();

        $data = [
            'store' => [
                'name'    => $store->name ?? 'RimsPos',
                'address' => $store->address,
                'city'    => $store->city,
                'phone'   => $store->phone,
                'logo'    => null,
            ],
            'transaction' => [
                'invoice'  => $sale->invoice_number,
                'date'     => $sale->sale_date->format('d-m-Y H:i'),
                'cashier'  => $sale->cashier?->name ?? 'Admin',
                'customer' => $sale->customer_name ?? 'Umum',
                'status'   => strtoupper($sale->status),
                'payment_status' => strtoupper($sale->payment_status),
            ],
            'items'   => $items,
            'summary' => [
                'subtotal' => round($sale->subtotal),
                'discount' => round(($sale->discount_total ?? 0) + ($sale->trans_discount ?? 0)),
                'total'    => round($sale->grand_total),
                'paid'     => round($sale->paid_amount),
                'change'   => round($sale->change_amount),
            ],
        ];

        $service   = new EscPosReceiptService($paper);
        $base64    = $service->base64($data);

        return response()->json([
            'paper'  => $paper,
            'base64' => $base64,
        ]);
    }

    /**
     * POST /api/pos/sales/{id}/void?store_id=N
     * Batalkan transaksi hari ini (hanya boleh di hari yang sama).
     */
    public function apiVoid(Request $request, int $id)
    {
        $storeId = $request->integer('store_id');

        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $hasAccess = auth()->user()
            ->stores()
            ->where('stores.id', $storeId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $sale = Sale::with(['items.batches'])
            ->where('store_id', $storeId)
            ->findOrFail($id);

        if ($sale->status === 'void') {
            return response()->json(['message' => 'Transaksi sudah di-void'], 422);
        }

        if (!$sale->sale_date->isToday()) {
            return response()->json(['message' => 'Void hanya bisa dilakukan di hari yang sama dengan transaksi'], 422);
        }

        try {
            DB::transaction(function () use ($sale) {

                // 1. Kembalikan stok (reverse FIFO)
                foreach ($sale->items->whereIn('status', ['sold', 'exchanged_in']) as $item) {
                    foreach ($item->batches as $batch) {
                        StockBatch::where('id', $batch->stock_batch_id)
                            ->increment('qty_sisa', $batch->qty);

                        StockMovement::create([
                            'product_variant_id' => $item->product_variant_id,
                            'stock_batch_id'     => $batch->stock_batch_id,
                            'posisi'             => 'store',
                            'tanggal'            => now(),
                            'tipe'               => 'in',
                            'direction'          => 'in',
                            'qty'                => $batch->qty,
                            'ref_type'           => 'SaleVoid',
                            'ref_id'             => $sale->id,
                        ]);
                    }
                    $item->update(['status' => 'voided']);
                }

                // 2. Update status sale
                $sale->update(['status' => 'void']);

                // 3. Hapus cash transaction & jurnal
                $cashTrx = CashTransaction::whereIn('transaction_type', ['sale', 'nse'])
                    ->where('ref_id', $sale->id)
                    ->get();
                $jurnalService = new JournalEntryService();
                foreach ($cashTrx as $trx) {
                    if ($trx->nojurnal) {
                        $jurnalService->delete($trx->nojurnal);
                    }
                    $trx->delete();
                }
            });

            return response()->json(['message' => 'Transaksi berhasil di-void']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal void transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/pos/sales/{id}/refund?store_id=N
     * Refund transaksi yang sudah lewat hari ini.
     */
    public function apiRefund(Request $request, int $id)
    {
        $storeId = $request->integer('store_id');

        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $hasAccess = auth()->user()
            ->stores()
            ->where('stores.id', $storeId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $sale = Sale::with(['items.batches', 'payments'])
            ->where('store_id', $storeId)
            ->findOrFail($id);

        if ($sale->status === 'void') {
            return response()->json(['message' => 'Transaksi sudah di-void'], 422);
        }

        if ($sale->refunds()->exists()) {
            return response()->json(['message' => 'Transaksi sudah pernah di-refund'], 422);
        }

        try {
            DB::transaction(function () use ($sale, $storeId) {

                // Tentukan metode pembayaran dari transaksi asli
                $paymentMethod = $sale->payments->first()?->payment_method ?? 'cash';

                // 1. Buat sale refund
                $refund = Sale::create([
                    'store_id'       => $storeId,
                    'invoice_number' => 'RF-' . $sale->invoice_number,
                    'sale_date'      => now(),
                    'sale_type'      => $sale->sale_type,
                    'customer_id'    => $sale->customer_id,
                    'customer_name'  => $sale->customer_name,
                    'user_id'        => auth()->id(),
                    'subtotal'       => -$sale->subtotal,
                    'discount_total' => -$sale->discount_total,
                    'trans_discount' => - ($sale->trans_discount ?? 0),
                    'tax_total'      => 0,
                    'grand_total'    => -$sale->grand_total,
                    'paid_amount'    => 0,
                    'change_amount'  => 0,
                    'status'         => 'paid',
                    'ref_sale_id'    => $sale->id,
                ]);

                // 2. Loop item & kembalikan stok dari batch asli
                foreach ($sale->items->whereIn('status', ['sold', 'exchanged_in']) as $item) {
                    $refundItem = SaleItem::create([
                        'sale_id'            => $refund->id,
                        'product_id'         => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'sku'                => $item->sku,
                        'product_name'       => $item->product_name,
                        'price'              => $item->price,
                        'qty'                => $item->qty,
                        'discount_amount'    => -$item->discount_amount,
                        'subtotal'           => -$item->subtotal,
                        'status'             => 'refunded',
                    ]);

                    foreach ($item->batches as $batch) {
                        $batch->stockBatch->increment('qty_sisa', $batch->qty);

                        StockMovement::create([
                            'product_variant_id' => $item->product_variant_id,
                            'stock_batch_id'     => $batch->stock_batch_id,
                            'posisi'             => 'store',
                            'tanggal'            => now(),
                            'tipe'               => 'in',
                            'direction'          => 'in',
                            'qty'                => $batch->qty,
                            'ref_type'           => 'SaleRefund',
                            'ref_id'             => $refundItem->id,
                        ]);
                    }
                }

                // 3. Cash transaction refund
                CashTransaction::create([
                    'store_id'         => $storeId,
                    'ref_type'         => 'SalePosRefund',
                    'ref_id'           => $refund->id,
                    'transaction_type' => 'refund',
                    'payment_method'   => $paymentMethod,
                    'account_code'     => 0,
                    'amount'           => $sale->grand_total,
                    'direction'        => 'out',
                    'transaction_date' => now(),
                    'user_id'          => auth()->id(),
                    'notes'            => 'Refund POS Mobile #' . $sale->invoice_number,
                ]);

                // Pembukuan jurnal
                if (config('app.jurnal_transaksi')) {
                    $service = new JournalFromCashTransactionService();
                    $service->createForRefund($refund->id);
                }
            });

            return response()->json(['message' => 'Refund berhasil diproses']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal proses refund: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiPayDebt(Request $request, int $id)
    {
        $storeId = $request->input('store_id');

        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $hasAccess = auth()->user()
            ->stores()
            ->where('stores.id', $storeId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $sale = Sale::where('store_id', $storeId)->findOrFail($id);

        if ($sale->payment_status !== 'hutang') {
            return response()->json(['message' => 'Transaksi ini bukan hutang'], 422);
        }

        if ($sale->status === 'void') {
            return response()->json(['message' => 'Transaksi sudah di-void'], 422);
        }

        $amount        = (float) $request->input('amount', 0);
        $paymentMethod = $request->input('payment_method', 'cash');
        $akunBank      = $request->input('akun_bank');
        $alreadyPaid   = (float) $sale->paid_amount;
        $remaining     = $sale->grand_total - $alreadyPaid;

        if ($amount <= 0) {
            return response()->json(['message' => 'Jumlah pembayaran harus lebih dari 0'], 422);
        }

        if ($amount > $remaining + 0.01) {
            return response()->json([
                'message' => 'Jumlah melebihi sisa hutang (Rp ' . number_format($remaining, 0, ',', '.') . ')',
            ], 422);
        }

        $effectiveAmount = min($amount, $remaining);

        // Handle bukti_bayar upload (optional, for transfer)
        $buktiBayarPath = null;
        if ($request->hasFile('bukti_bayar') && $request->file('bukti_bayar')->isValid()) {
            $buktiBayarPath = $request->file('bukti_bayar')
                ->store('bukti_bayar', 'public');
        }

        try {
            $result = DB::transaction(function () use ($sale, $effectiveAmount, $paymentMethod, $akunBank, $storeId, $alreadyPaid, $buktiBayarPath) {
                CashTransaction::create([
                    'store_id'         => $storeId,
                    'ref_type'         => 'SaleDebt',
                    'ref_id'           => $sale->id,
                    'transaction_type' => 'sale',
                    'payment_method'   => $paymentMethod,
                    'account_code'     => $paymentMethod === 'transfer' ? ($akunBank ?? 0) : 0,
                    'amount'           => $effectiveAmount,
                    'direction'        => 'in',
                    'transaction_date' => now(),
                    'user_id'          => auth()->id(),
                    'notes'            => 'Bayar Hutang #' . $sale->invoice_number,
                    'bukti_bayar'      => $buktiBayarPath,
                ]);

                $newPaidTotal = $alreadyPaid + $effectiveAmount;
                $newRemaining = $sale->grand_total - $newPaidTotal;
                $isLunas      = $newRemaining <= 0.01;

                $sale->update([
                    'paid_amount'    => $isLunas ? (float) $sale->grand_total : $newPaidTotal,
                    'payment_status' => $isLunas ? 'lunas' : 'hutang',
                ]);

                return [
                    'isLunas'      => $isLunas,
                    'newPaidTotal' => $newPaidTotal,
                    'newRemaining' => $newRemaining,
                ];
            });

            return response()->json([
                'message'        => $result['isLunas'] ? 'Hutang lunas!' : 'Pembayaran berhasil diterima',
                'payment_status' => $result['isLunas'] ? 'lunas' : 'hutang',
                'paid_total'     => (float) $result['newPaidTotal'],
                'remaining'      => (float) max(0, $result['newRemaining']),
            ]);
        } catch (\Exception $e) {
            if ($buktiBayarPath) {
                \Storage::disk('public')->delete($buktiBayarPath);
            }
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function apiSaleDetail(Request $request, int $id)
    {
        $storeId = $request->integer('store_id');

        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $hasAccess = auth()->user()
            ->stores()
            ->where('stores.id', $storeId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $sale = Sale::with(['items', 'payments', 'cashier'])
            ->where('store_id', $storeId)
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $items = $sale->items->map(function ($item) {
            return [
                'id'              => $item->id,
                'sku'             => $item->sku,
                'product_name'    => $item->product_name,
                'price'           => (float) $item->price,
                'qty'             => (int) $item->qty,
                'discount_amount' => (float) $item->discount_amount,
                'subtotal'        => (float) $item->subtotal,
            ];
        });

        $payments = $sale->payments->map(function ($p) {
            return [
                'method' => $p->payment_method,
                'amount' => (float) $p->amount,
            ];
        });

        return response()->json([
            'id'               => $sale->id,
            'invoice_number'   => $sale->invoice_number,
            'sale_date'        => $sale->sale_date->format('Y-m-d H:i:s'),
            'customer_name'    => $sale->customer_name,
            'cashier_name'     => $sale->cashier?->name ?? '-',
            'subtotal'         => (float) $sale->subtotal,
            'discount_total'   => (float) $sale->discount_total,
            'trans_discount'   => (float) $sale->trans_discount,
            'grand_total'      => (float) $sale->grand_total,
            'paid_amount'      => (float) $sale->paid_amount,
            'change_amount'    => (float) $sale->change_amount,
            'payment_status'   => $sale->payment_status,
            'status'           => $sale->status,
            'bukti_bayar_url'  => (function () use ($sale) {
                $path = CashTransaction::where('ref_id', $sale->id)
                    ->whereIn('ref_type', ['SalePos', 'SaleDebt'])
                    ->whereNotNull('bukti_bayar')
                    ->latest()
                    ->value('bukti_bayar');
                return $path ? asset('storage/' . $path) : null;
            })(),
            'items'            => $items,
            'payments'         => $payments,
        ]);
    }

    protected function issueFIFOWithBatchLog(
        string $transactionDate,
        int $variantId,
        string $posisi,
        int $qty,
        SaleItem $saleItem,
        string $refType = 'SalePos'
    ) {
        $batches = StockBatch::where('product_variant_id', $variantId)
            ->where('posisi', $posisi)
            ->where('qty_sisa', '>', 0)
            ->orderBy('tanggal_masuk')
            ->lockForUpdate()
            ->get();

        $sisa = $qty;

        foreach ($batches as $batch) {
            if ($sisa <= 0) break;

            $ambil = min($batch->qty_sisa, $sisa);

            $batch->decrement('qty_sisa', $ambil);

            // LOG FIFO DETAIL
            SaleItemBatch::create([
                'sale_item_id'  => $saleItem->id,
                'stock_batch_id' => $batch->id,
                'qty'           => $ambil,
                'cost_price'    => $batch->harga_beli,
                'sell_price'    => $saleItem->price,
            ]);

            // OPTIONAL: movement log (kalau belum dipanggil di StockService)
            StockMovement::create([
                'product_variant_id' => $variantId,
                'stock_batch_id'     => $batch->id,
                'posisi'             => $posisi,
                'tanggal'            => $transactionDate,
                'tipe'               => 'out',
                'direction'          => 'out',
                'qty'                => $ambil,
                'ref_type'           => $refType,
                'ref_id'             => $saleItem->id,
            ]);

            $sisa -= $ambil;
        }

        if ($sisa > 0) {
            throw new \Exception('Stok tidak mencukupi');
        }
    }

    public function void(Sale $sale)
    {
        if ($sale->status === 'void') {
            abort(400, 'Transaksi sudah void');
        }
        try {
            DB::transaction(function () use ($sale) {

                // 1️⃣ Kembalikan stok (reverse FIFO)
                foreach ($sale->items->whereIn('status', ['sold', 'exchanged_in']) as $item) {
                    foreach ($item->batches as $batch) {

                        // Tambah kembali qty_sisa batch
                        StockBatch::where('id', $batch->stock_batch_id)
                            ->increment('qty_sisa', $batch->qty);

                        // Log movement IN
                        StockMovement::create([
                            'product_variant_id' => $item->product_variant_id,
                            'stock_batch_id'     => $batch->stock_batch_id,
                            'posisi'             => 'store',
                            'tanggal'            => now(),
                            'tipe'               => 'in',
                            'direction'          => 'in',
                            'qty'                => $batch->qty,
                            'ref_type'           => $sale->sale_type === 'nse' ? 'NSEVoid' : 'SaleVoid',
                            'ref_id'             => $sale->id,
                        ]);
                    }
                    $item->update([
                        'status' => 'voided'
                    ]);

                    // if ($sale->sale_type === 'nse') {
                    //     // Update juga status di tabel seragam_distribusi
                    //     DB::connection('nsedb')->table('seragam_distribusi')
                    //         ->where('sale_item_id', $item->id)
                    //         ->update(['status' => 'fulfilled', 'scanned_at' => null, 'scanned_by' => 0, 'sale_item_id' => 0]);

                    //     $pendingWajib = SeragamDistribusi::where('id_biodata', $sale->customer_id)
                    //         ->where('status', 'pending')
                    //         ->whereRelation('seragam', 'wajib', 'Y')
                    //         ->exists();

                    //     $siswa = NseCalonSiswa::findOrFail($sale->customer_id);
                    //     $siswa->update([
                    //         'ambil_seragam'   => $pendingWajib ? 'S' : 'N',
                    //         'voucher_seragam' => 'N'
                    //     ]);
                    // }
                }

                // 2️⃣ Update status sale
                $sale->update([
                    'status' => 'void'
                ]);

                // 3️⃣ Hapus cash transaction
                $cashTrx = CashTransaction::whereIn('transaction_type', ['sale', 'nse'])
                    ->where('ref_id', $sale->id)
                    ->get();
                $jurnalService = new JournalEntryService();
                foreach ($cashTrx as $trx) {
                    if ($trx->nojurnal) {
                        $jurnalService->delete($trx->nojurnal);
                    }
                    $trx->delete();
                }

                // Hapus jurnal terkait exchanged jika ada
                $dataexchange = $sale->items()->whereNotNull('ref_sale_item_id')->get();
                foreach ($dataexchange as $item) {
                    $cashTrxExchange = CashTransaction::where('ref_type', 'Exchange')
                        ->where('ref_id', $item->id)
                        ->get();
                    foreach ($cashTrxExchange as $trx) {
                        if ($trx->nojurnal) {
                            $jurnalService->delete($trx->nojurnal);
                        }
                        $trx->delete();
                    }
                }
            });

            return back()->with('success', 'Transaksi berhasil di-VOID');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal void transaksi: ' . $e->getMessage());
        }
    }

    public function refund(Request $request, Sale $sale)
    {
        // 🔒 Validasi
        if ($sale->refunds()->exists()) {
            return back()->with('error', 'Transaksi sudah pernah di-refund');
        }
        try {
            DB::transaction(function () use ($request, $sale) {

                // 1️⃣ BUAT SALE REFUND
                $refund = Sale::create([
                    'store_id'       => session('store_id'),
                    'invoice_number' => 'RF-' . $sale->invoice_number,
                    'sale_date'      => now(),
                    'sale_type'      => $sale->sale_type,

                    'customer_id'    => $sale->customer_id,
                    'customer_name'  => $sale->customer_name,
                    'user_id'        => auth()->id(),

                    'subtotal'       => -$sale->subtotal,
                    'discount_total' => -$sale->discount_total,
                    'tax_total'      => 0,
                    'grand_total'    => -$sale->grand_total,

                    'paid_amount'    => 0,
                    'change_amount'  => 0,
                    'status'         => 'paid',

                    'ref_sale_id'    => $sale->id,
                ]);

                // 2️⃣ LOOP ITEM SALE ASLI
                foreach ($sale->items->whereIn('status', ['sold', 'exchanged_in']) as $item) {

                    $refundItem = SaleItem::create([
                        'sale_id'           => $refund->id,
                        'product_id'        => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,

                        'sku'               => $item->sku,
                        'product_name'      => $item->product_name,

                        'price'             => $item->price,
                        'qty'               => $item->qty,
                        'discount_amount'   => -$item->discount_amount,
                        'subtotal'          => -$item->subtotal,
                        'status'            => 'refunded',
                    ]);

                    // 3️⃣ KEMBALIKAN STOK DARI BATCH ASLI
                    foreach ($item->batches as $batch) {

                        // tambah stok batch
                        $batch->stockBatch->increment('qty_sisa', $batch->qty);

                        StockMovement::create([
                            'product_variant_id' => $item->product_variant_id,
                            'stock_batch_id'     => $batch->stock_batch_id,
                            'posisi'             => 'store',
                            'tanggal'            => now(),
                            'tipe'               => 'in',
                            'direction'          => 'in',
                            'qty'                => $batch->qty,
                            'ref_type'           => 'SaleRefund',
                            'ref_id'             => $refundItem->id,
                        ]);
                    }

                    // if ($sale->sale_type === 'nse') {
                    //     // Update juga status di tabel seragam_distribusi
                    //     DB::connection('nsedb')->table('seragam_distribusi')
                    //         ->where('sale_item_id', $item->id)
                    //         ->update(['status' => 'pending', 'scanned_at' => null, 'sale_item_id' => 0]);
                    // }
                }
                // 4️⃣ CASH TRANSACTION REFUND
                CashTransaction::create([
                    'store_id'         => session('store_id'),
                    'ref_type'         => 'SalePosRefund',
                    'ref_id'           => $refund->id,
                    'transaction_type' => 'refund',
                    'payment_method'   => $request->payment_method,
                    'account_code'     => $request->akun_bank,
                    'amount'           => $request->paid_amount,
                    'direction'        => 'out',
                    'transaction_date' => now(),
                    'user_id'          => auth()->id(),
                    'notes'            => 'Refund penjualan POS #' . $sale->invoice_number,
                ]);

                // Pembukuan jurnal
                if (config('app.jurnal_transaksi')) {
                    $service = new JournalFromCashTransactionService();
                    $service->createForRefund($refund->id);
                }
            });

            return back()->with('success', 'Refund berhasil diproses');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal proses refund: ' . $e->getMessage());
        }
    }

    public function exchange(Request $request, Sale $sale)
    {
        $request->validate([
            'old_item_id'    => 'required|exists:sale_items,id',
            'new_variant_id' => 'required|exists:product_variants,id',
            'qty'            => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $sale) {
            $paymentMethod = $request->payment_method ?? 'cash';
            if ($paymentMethod === 'cash') {
                $akunkasir = 0;
                $accountCode = $akunkasir;
            } else if ($paymentMethod === 'transfer') {
                $accountCode = $request->akun_bank;
            } else {
                throw new \Exception('Metode pembayaran tidak valid');
            }
            /** ======================================================
             * 1️⃣ VALIDASI ITEM LAMA
             * ====================================================== */
            $originalItem = SaleItem::with('batches')->findOrFail($request->old_item_id);

            if ($request->qty > $originalItem->qty) {
                throw new \Exception('Qty exchange melebihi qty item');
            }

            $exchangeQty = (int) $request->qty;

            /** ======================================================
             * 2️⃣ HANDLE PARTIAL / FULL EXCHANGE (ITEM LAMA)
             * ====================================================== */
            if ($exchangeQty < $originalItem->qty) {

                // Kurangi item lama (yang tetap sold)
                $originalItem->update([
                    'qty'      => $originalItem->qty - $exchangeQty,
                    'subtotal' => ($originalItem->qty - $exchangeQty) * $originalItem->price,
                ]);

                // Buat item khusus untuk exchanged_out
                $exchangedOutItem = SaleItem::create([
                    'sale_id'            => $sale->id,
                    'product_id'         => $originalItem->product_id,
                    'product_variant_id' => $originalItem->product_variant_id,
                    'sku'                => $originalItem->sku,
                    'product_name'       => $originalItem->product_name,
                    'price'              => $originalItem->price,
                    'qty'                => $exchangeQty,
                    'discount_amount'    => 0,
                    'subtotal'           => $exchangeQty * $originalItem->price,
                    'status'             => 'exchanged_out',
                ]);

                // SPLIT BATCH
                $this->splitBatches($originalItem, $exchangedOutItem, $exchangeQty);
            } else {

                // Full exchange
                $originalItem->update(['status' => 'exchanged_out']);
                $exchangedOutItem = $originalItem;
            }

            /** ======================================================
             * 3️⃣ KEMBALIKAN STOK ITEM LAMA
             * ====================================================== */
            foreach ($exchangedOutItem->batches as $batch) {

                StockBatch::where('id', $batch->stock_batch_id)
                    ->increment('qty_sisa', $batch->qty);

                StockMovement::create([
                    'product_variant_id' => $exchangedOutItem->product_variant_id,
                    'stock_batch_id'     => $batch->stock_batch_id,
                    'posisi'             => 'store',
                    'tanggal'            => now(),
                    'tipe'               => 'in',
                    'direction'          => 'in',
                    'qty'                => $batch->qty,
                    'ref_type'           => 'ExchangeInStore',
                    'ref_id'             => $exchangedOutItem->id,
                ]);
            }

            /** ======================================================
             * 4️⃣ ITEM BARU (EXCHANGED_IN)
             * ====================================================== */
            $variantNew = ProductVariant::with('product')->findOrFail($request->new_variant_id);

            $newItem = SaleItem::create([
                'sale_id'            => $sale->id,
                'product_id'         => $variantNew->product_id,
                'product_variant_id' => $variantNew->id,
                'sku'                => $variantNew->sku,
                'product_name'       => $variantNew->product->nama_produk,
                'price'              => $variantNew->harga_jual,
                'qty'                => $exchangeQty,
                'discount_amount'    => 0,
                'subtotal'           => $exchangeQty * $variantNew->harga_jual,
                'status'             => 'exchanged_in',
                'ref_sale_item_id'   => $exchangedOutItem->id,
            ]);

            // FIFO ambil stok baru
            // app(\App\Services\StockService::class)->issueFIFO(
            //     $variantNew->id,
            //     'store',
            //     $exchangeQty,
            //     'ExchangeIn',
            //     $newItem->id
            // );
            $this->issueFIFOWithBatchLog(
                now(),
                $variantNew->id,
                'store', // atau outlet
                $exchangeQty,
                $newItem,
                'ExchangeOutStore'
            );

            /** ======================================================
             * 5️⃣ SELISIH HARGA → CASH TRANSACTION
             * ====================================================== */
            $oldTotal = $exchangedOutItem->subtotal;
            $newTotal = $newItem->subtotal;
            $diff     = $newTotal - $oldTotal;

            $cashtrx = CashTransaction::create([
                'store_id'         => session('store_id'),
                'ref_type'         => 'Exchange',
                'ref_id'           => $newItem->id,
                'transaction_type' => $diff >= 0 ? 'exchange_additional' : 'exchange_refund',
                'payment_method'   => $paymentMethod,
                'account_code'     => $accountCode,
                'amount'           => abs($diff),
                'direction'        => $diff >= 0 ? 'in' : 'out', // kalau in berarti customer bayar tambahan, kalau out berarti refund ke customer
                'transaction_date' => now(),
                'user_id'          => auth()->id(),
                'note'             => 'Exchange barang',
            ]);

            /** ======================================================
             * 6️⃣ UPDATE TOTAL SALE
             * ====================================================== */
            $sale->update([
                'subtotal' => $sale->items()
                    ->where('status', '!=', 'exchanged_out')
                    ->sum('subtotal'),

                'grand_total' => $sale->items()
                    ->where('status', '!=', 'exchanged_out')
                    ->sum('subtotal'),
                'paid_amount' => $sale->paid_amount + ($diff > 0 ? $diff : 0) - ($diff < 0 ? abs($diff) : 0), // kalau customer bayar tambahan, tambahkan ke paid_amount
            ]);

            // Pembukuan jurnal
            if (config('app.jurnal_transaksi')) {
                if ($sale->sale_type === 'nse') {
                    $akun = DB::connection('nsedb')
                        ->table('biaya_du as p')
                        ->join('master_daftar_harga as q', 'p.id_komponen', '=', 'q.id')
                        ->where('p.id_biodata', $sale->customer_id)
                        ->where('q.nama', 'like', '%UNIFORM%')
                        ->value('q.kdbeban');
                    $cashtrx->update(['account_code' => $akun]);
                    $service = new JournalFromCashTransactionService();
                    $service->createForNseExchange($newItem->id, $akun);
                } else {
                    $service = new JournalFromCashTransactionService();
                    $service->createForExchange($newItem->id);
                }
            }
        });

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Exchange berhasil');
    }

    /**
     * ======================================================
     * SPLIT SALE ITEM BATCHES (PARTIAL EXCHANGE)
     * ======================================================
     */
    private function splitBatches(SaleItem $fromItem, SaleItem $toItem, int $qty)
    {
        foreach ($fromItem->batches as $batch) {

            if ($qty <= 0) break;

            if ($batch->qty > $qty) {

                // Kurangi batch lama
                $batch->update([
                    'qty' => $batch->qty - $qty
                ]);

                // Batch baru untuk exchanged_out
                SaleItemBatch::create([
                    'sale_item_id'  => $toItem->id,
                    'stock_batch_id' => $batch->stock_batch_id,
                    'qty'           => $qty,
                    'cost_price'    => $batch->cost_price,
                    'sell_price'    => $batch->sell_price,
                ]);

                $qty = 0;
            } else {

                // Pindahkan batch utuh
                $batch->update([
                    'sale_item_id' => $toItem->id
                ]);

                $qty -= $batch->qty;
            }
        }
    }

    public function printThermal(Sale $sale)
    {
        $store    = Store::findOrFail(session('store_id'));
        $paper    = $store->printer_type ?? '80mm';
        $data     = $this->printReceipt($sale->id)->getData(true);
        $service  = new EscPosReceiptService($paper);
        $intentUri = $service->intentUri($data);

        return response($intentUri, 200, ['Content-Type' => 'text/plain']);
    }

    public function printReceipt($id)
    {
        $store = Store::findOrFail(session('store_id'));
        $sale = Sale::with(['items' => function ($query) {
            $query->whereIn('status', ['sold', 'exchanged_in']);
        }, 'cashier', 'refunds'])->findOrFail($id);

        return response()->json([
            'store' => [
                'name'    => $store->name ?? 'RimsPos',
                'address' => $store->address,
                'city'    => $store->city,
                'phone'   => $store->phone,
                'logo'    => $store->logo ? Storage::url($store->logo) : null,
            ],
            'transaction' => [
                'invoice' => $sale->invoice_number,
                'date'    => $sale->sale_date->format('d-m-Y H:i'),
                'cashier' => $sale->cashier->name ?? 'Admin',
                'customer' => $sale->customer_name ?? 'Umum',
                'status'   => $sale->refunds->isNotEmpty() ? 'REFUNDED' : strtoupper($sale->status),
            ],
            'items' => $sale->items->map(function ($item) {
                return [
                    'name'  => $item->product_name,
                    'sku'   => $item->sku,
                    'qty'   => $item->qty,
                    'price' => round($item->price),
                ];
            })->toArray(),
            'summary' => [
                'subtotal' => round($sale->subtotal),
                'discount' => round($sale->discount_total),
                'total'    => round($sale->grand_total),
                'paid'     => round($sale->paid_amount),
                'change'   => round($sale->change_amount),
            ]
        ]);
    }

    /**
     * Endpoint plain-text mirip CI3 showticketprint.
     *
     * POST /sales/{id}/showticketprint
     *
     * Kembalikan Android Intent URI sebagai plain text.
     * Dipakai oleh cetakStruk() via fetch → lalu:
     *   - Android : window.location.href = intentUri
     *   - PC      : WebSocket ws://127.0.0.1:40213/ → kirim intentUri
     */
    public function showticketprint($id)
    {
        $store     = Store::findOrFail(session('store_id'));
        $paper     = $store->printer_type ?? '80mm';
        $data      = $this->printReceipt($id)->getData(true);
        $service   = new EscPosReceiptService($paper);
        $intentUri = $service->intentUri($data);
        // dd(substr($intentUri, 0, 100));
        // dd(substr($intentUri, -200));

        return response($intentUri, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Cetak via RawBT (Android).
     *
     * GET /sales/{id}/rawbt          → pakai printer_type dari setting toko
     * GET /sales/{id}/rawbt/58mm     → paksa 58mm
     * GET /sales/{id}/rawbt/80mm     → paksa 80mm
     *
     * Response JSON:
     *   intent_uri  → langsung pakai: window.location.href = data.intent_uri
     *   base64      → untuk WebPrint API RawBT (POST ke http://localhost:8080/rawbt)
     *   paper       → ukuran kertas yang digunakan
     */
    public function printRawbt(Request $request, $id, string $paper = null)
    {
        $store = Store::findOrFail(session('store_id'));
        $paper = $paper ?? $store->printer_type ?? '80mm';

        if (!in_array($paper, ['58mm', '80mm'])) {
            $paper = '80mm';
        }

        $data    = $this->printReceipt($id)->getData(true);
        $service = new EscPosReceiptService($paper);

        return response()->json([
            'paper'      => $paper,
            'intent_uri' => $service->intentUri($data),
            'base64'     => $service->base64($data),
        ]);
    }

    /**
     * Halaman cetak RawBT untuk Android/mobile.
     *
     * Browser navigasi langsung ke halaman ini (bukan lewat fetch async),
     * sehingga intent URI tetap terikat ke user-gesture dan Chrome Android
     * dapat membuka RawBT yang sudah terinstall dengan benar.
     *
     * GET /sales/{id}/rawbt-print
     * GET /sales/{id}/rawbt-print/58mm
     * GET /sales/{id}/rawbt-print/80mm
     */
    public function printRawbtPage($id, string $paper = null)
    {
        $store = Store::findOrFail(session('store_id'));
        $paper = $paper ?? $store->printer_type ?? '80mm';

        if (!in_array($paper, ['58mm', '80mm'])) {
            $paper = '80mm';
        }

        $data      = $this->printReceipt($id)->getData(true);
        $service   = new EscPosReceiptService($paper);
        $intentUri = $service->intentUri($data);
        $backUrl   = route('sales.show', $id);

        return view('pos.rawbt-print', compact('intentUri', 'backUrl'));
    }

    /**
     * Tampilkan halaman struk untuk window.print
     * Menggunakan data yang sama dengan printReceipt()
     */
    public function showReceipt($id)
    {
        $store = Store::findOrFail(session('store_id'));
        $data = $this->printReceipt($id)->getData(true);

        $view = ($store->printer_type === '58mm') ? 'pos.receipt-58mm' : 'pos.receipt';

        return view($view, [
            'store'       => $data['store'],
            'transaction' => $data['transaction'],
            'items'       => $data['items'],
            'summary'     => $data['summary'],
        ]);
    }

    protected function generateInvoice()
    {
        return 'POS-' . now()->format('YmdHis');
    }
}
