<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantBarcode;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\VariantAttribute;
use App\Services\JournalEntryService;
use App\Services\StockAdjustmentPostingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Milon\Barcode\DNS1D;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use App\Models\Store;
use App\Models\Tenant;

class ProdukController extends Controller
{
    public function index()
    {
        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $loyaltyService = app(\App\Services\LoyaltyPointService::class);
        $pointSettings = $loyaltyService->getSettings(session('store_id'));
        $showRewardPoints = $pointSettings && $pointSettings->is_active && in_array($pointSettings->earning_method, ['product', 'hybrid']);

        return view('produk.index', compact('isFnB', 'showRewardPoints'));
    }

    public function downloadTemplate()
    {
        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $loyaltyService = app(\App\Services\LoyaltyPointService::class);
        $pointSettings = $loyaltyService->getSettings(session('store_id'));
        $showRewardPoints = $pointSettings && $pointSettings->is_active && in_array($pointSettings->earning_method, ['product', 'hybrid']);

        $fileName = 'template_import_produk_' . ($isFnB ? 'fnb' : 'retail') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductTemplateExport($isFnB, $showRewardPoints),
            $fileName
        );
    }

    public function importProses(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'dry_run' => 'nullable',
        ]);

        try {
            $store = Store::find(session('store_id'));
            $isFnB = $store && $store->business_type === 'fnb';

            $loyaltyService = app(\App\Services\LoyaltyPointService::class);
            $pointSettings = $loyaltyService->getSettings(session('store_id'));
            $showRewardPoints = $pointSettings && $pointSettings->is_active && in_array($pointSettings->earning_method, ['product', 'hybrid']);

            $importService = new \App\Services\ProductImportService();
            $result = $importService->import([
                'file' => $request->file('file'),
                'store_id' => session('store_id'),
                'is_fnb' => $isFnB,
                'show_reward_points' => $showRewardPoints,
                'dry_run' => $request->has('dry_run') || $request->boolean('dry_run'),
            ]);

            return response()->json(array_merge(['success_status' => true], $result));
        } catch (\Exception $e) {
            return response()->json([
                'success_status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadStockTemplate()
    {
        $storeId = session('store_id');
        $fileName = 'template_import_stok_awal.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StockTemplateExport($storeId),
            $fileName
        );
    }

    public function importStockProses(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'transaction_type' => 'required|in:purchase_order,stock_adjustment',
            'dry_run' => 'nullable',
        ]);

        try {
            $importService = new \App\Services\StockImportService();
            $result = $importService->import([
                'file' => $request->file('file'),
                'store_id' => session('store_id'),
                'transaction_type' => $request->transaction_type,
                'dry_run' => $request->has('dry_run') || $request->boolean('dry_run'),
            ]);

            return response()->json(array_merge(['success_status' => true], $result));
        } catch (\Exception $e) {
            return response()->json([
                'success_status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function datatables(Request $request)
    {
        $query = Product::query()
            ->select('products.*')
            ->withCount('variants')
            ->withStockWarehouse()
            ->withStockStore();

        return DataTables::of($query)
            ->addColumn('aksi', function ($p) {
                $edit   = route('produk.edit', $p);
                $detail = route('produk.show', $p);
                return '<a href="' . $edit . '" class="btn btn-sm btn-warning me-1">Edit</a>'
                    . '<a href="' . $detail . '" class="btn btn-sm btn-info">Detail</a>';
            })
            ->filterColumn('nama_produk', function ($q, $keyword) {
                $q->where(function ($q) use ($keyword) {
                    $q->where('nama_produk', 'like', "%$keyword%")
                        ->orWhere('kode_produk', 'like', "%$keyword%");
                });
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }
    public function create()
    {
        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';
        $tenants = $isFnB ? Tenant::where('store_id', session('store_id'))->where('stts', 'Y')->get() : collect();

        $loyaltyService = app(\App\Services\LoyaltyPointService::class);
        $pointSettings = $loyaltyService->getSettings(session('store_id'));
        $showRewardPoints = $pointSettings && $pointSettings->is_active && in_array($pointSettings->earning_method, ['product', 'hybrid']);

        return view('produk.create', compact('isFnB', 'tenants', 'showRewardPoints'));
    }
    public function store(Request $request)
    {
        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $rules = [
            'kode'               => 'required|string|max:50|unique:products,kode_produk',
            'nama'               => 'required|string|max:150',
            'variants'           => 'nullable|array',
            'variants.*.nama'    => 'nullable|string|max:150',
            'variants.*.barcode' => 'nullable|string|max:100',
            'variants.*.harga'   => 'nullable|numeric|min:0',
            'variants.*.reward_points' => 'nullable|integer|min:0',
        ];

        if ($isFnB) {
            $rules['tenant_id'] = 'nullable|exists:tenants,id';
            $rules['image'] = 'nullable|image|max:2048';
            $rules['variants.*.track_stock'] = 'nullable|boolean';
            $rules['variants.*.cost_price_manual'] = 'nullable|numeric|min:0';
            $rules['variants.*.commission_type'] = 'nullable|in:global,percentage,nominal';
            $rules['variants.*.commission_rate'] = 'nullable|numeric|min:0';
        }

        $request->validate($rules);
        try {
            DB::transaction(function () use ($request, $isFnB) {
                $imagePath = null;
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('products', 'public');
                }

                $product = Product::create([
                    'store_id'    => session('store_id'),
                    'kode_produk' => strtoupper($request->kode),
                    'nama_produk' => $request->nama,
                    'deskripsi'   => $request->deskripsi,
                    'tenant_id'   => $request->tenant_id,
                    'image'       => $imagePath,
                ]);

                $variants = $request->variants ?? [];
                if (empty($variants)) {
                    $variants = [['nama' => '', 'barcode' => '', 'harga' => 0, 'reward_points' => 0]];
                }

                foreach ($variants as $i => $v) {
                    if (!empty($v['barcode'])) {
                        $barcode = strtoupper($v['barcode']);
                        if (ProductVariant::where('barcode', $barcode)->exists()) {
                            throw new \Exception("Barcode '{$barcode}' sudah digunakan.");
                        }
                    } else {
                        do {
                            $barcode = strtoupper(Str::random(8));
                        } while (ProductVariant::where('barcode', $barcode)->exists());
                    }

                    $sku = $product->kode_produk . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);

                    $variantData = [
                        'store_id'     => session('store_id'),
                        'product_id'   => $product->id,
                        'variant_name' => $v['nama'] ?? '',
                        'sku'          => $sku,
                        'barcode'      => $barcode,
                        'harga_jual'   => $v['harga'] ?? 0,
                        'reward_points' => $v['reward_points'] ?? 0,
                    ];

                    if ($isFnB) {
                        $variantData['track_stock'] = isset($v['track_stock']) ? (bool)$v['track_stock'] : true;
                        $variantData['cost_price_manual'] = $v['cost_price_manual'] ?? 0;
                        $variantData['commission_type'] = $v['commission_type'] ?? 'global';
                        $variantData['commission_rate'] = $v['commission_rate'] ?? 0;
                    }

                    $pv = ProductVariant::create($variantData);

                    $pv->barcodes()->create([
                        'barcode'   => $barcode,
                        'is_active' => 'Y',
                    ]);
                }
            });

            return redirect()
                ->route('produk.index')
                ->with('success', 'Produk berhasil disimpan');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $product = Product::with([
            'variants' => function ($q) {
                $q->with('variantAttributes.attribute', 'variantAttributes.value', 'barcodeActive')
                    ->where('is_active', 'Y');
            }
        ])
            ->withStockWarehouse()
            ->withStockStore()
            ->findOrFail($id);
        // dd($product);

        // 🔹 Cek apakah produk punya attribute Divisi
        $hasDivisi = $product->variants->contains(function ($variant) {
            return $variant->variantAttributes
                ->contains(fn($va) => $va->attribute->nama === 'Divisi');
        });

        // 🔹 Grouping variant (adaptif)
        if ($hasDivisi) {
            $variantsByGroup = $product->variants
                ->sortBy(function ($variant) {
                    return $variant->variantAttributes
                        ->firstWhere('attribute.nama', 'Divisi')
                        ?->value?->urutan ?? 999;
                })
                ->groupBy(function ($variant) {
                    return $variant->variantAttributes
                        ->firstWhere('attribute.nama', 'Divisi')
                        ?->value?->nama ?? 'Lainnya';
                });
        } else {
            // Produk sederhana (Gender saja, dll)
            $variantsByGroup = collect([
                'Varian' => $product->variants
            ]);
        }
        // dd(json_encode($product, JSON_PRETTY_PRINT));
        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $loyaltyService = app(\App\Services\LoyaltyPointService::class);
        $pointSettings = $loyaltyService->getSettings(session('store_id'));
        $showRewardPoints = $pointSettings && $pointSettings->is_active && in_array($pointSettings->earning_method, ['product', 'hybrid']);

        return view('produk.show', compact('product', 'variantsByGroup', 'hasDivisi', 'isFnB', 'showRewardPoints'));
    }
    public function showVariantDetail(Product $product, ProductVariant $variant)
    {
        abort_if($variant->product_id !== $product->id, 404);

        $warehouseMovements = StockMovement::whereHas('batch', function ($q) use ($variant) {
            $q->where('product_variant_id', $variant->id)
                ->where('posisi', 'warehouse');
        })
            ->orderBy('created_at', 'asc')
            ->get();

        // Mutasi Toko
        $storeMovements = StockMovement::whereHas('batch', function ($q) use ($variant) {
            $q->where('product_variant_id', $variant->id)
                ->where('posisi', 'store');
        })
            ->orderBy('created_at', 'asc')
            ->get();
        $variant->load('barcodes');

        // dd(json_encode($variant, JSON_PRETTY_PRINT));

        return view('produk.mutasistok', compact(
            'product',
            'variant',
            'warehouseMovements',
            'storeMovements'
        ));
    }

    public function adjustStock(Request $request, Product $product, ProductVariant $variant)
    {
        abort_if($variant->product_id !== $product->id, 404);

        $request->validate([
            'posisi'          => 'required|in:warehouse,store',
            'adjustment_type' => 'required|in:increase,decrease',
            'qty'             => 'required|integer|min:1',
            'cost'            => 'required_if:adjustment_type,increase|numeric|min:0|nullable',
            'notes'           => 'nullable|string|max:500',
            'effective_date'  => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($request, $variant) {
                // Generate code
                $code = $this->generateAdjustmentCode('SA');

                $posisi = $request->posisi;
                $qty = (int) $request->qty;
                $cost = (float) $request->cost;
                $isIncrease = $request->adjustment_type === 'increase';

                // Create StockAdjustment (DRAFT)
                $adjustment = StockAdjustment::create([
                    'store_id'       => session('store_id'),
                    'code'           => $code,
                    'effective_date' => $request->effective_date,
                    'posisi'         => $posisi,
                    'reason_type'    => 'CORRECTION',
                    'notes'          => $request->notes ?? 'Penyesuaian stok langsung untuk variant #' . $variant->sku,
                    'status'         => 'DRAFT',
                    'created_by'     => auth()->id(),
                ]);

                if ($isIncrease) {
                    // Tambah stok — posting service akan membuat batch baru
                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'product_variant_id'  => $variant->id,
                        'qty'                 => $qty,
                        'cost'                => $cost,
                        'total_value'         => $qty * $cost,
                    ]);
                } else {
                    // Kurang stok — FIFO otomatis
                    $remaining = $qty;
                    $batches = StockBatch::where('product_variant_id', $variant->id)
                        ->where('posisi', $posisi)
                        ->where('qty_sisa', '>', 0)
                        ->orderBy('created_at') // FIFO
                        ->get();

                    if ($batches->isEmpty()) {
                        throw new Exception('Tidak ada stok tersedia di posisi ' . $posisi . '.');
                    }

                    $totalAvailable = $batches->sum('qty_sisa');
                    if ($totalAvailable < $qty) {
                        throw new Exception('Stok tidak mencukupi. Tersedia: ' . $totalAvailable . ', diminta: ' . $qty . '.');
                    }

                    foreach ($batches as $batch) {
                        if ($remaining <= 0) {
                            break;
                        }
                        $takeQty = min($batch->qty_sisa, $remaining);

                        StockAdjustmentItem::create([
                            'stock_adjustment_id' => $adjustment->id,
                            'product_variant_id'  => $variant->id,
                            'stock_batch_id'      => $batch->id,
                            'qty'                 => -$takeQty,
                            'cost'                => $batch->harga_beli,
                            'total_value'         => -($takeQty * $batch->harga_beli),
                        ]);

                        $remaining -= $takeQty;
                    }
                }

                // Posting adjustment via existing service
                $service = app(StockAdjustmentPostingService::class);
                $result = $service->post($adjustment);

                // Generate jurnal akuntansi
                if (config('app.jurnal_transaksi')) {
                    $this->generateAdjustmentJournal($adjustment, $result);
                }
            });

            return redirect()->back()->with('success', 'Penyesuaian stok berhasil diposting. Stok ' . $variant->variant_label . ' telah diperbarui.');
        } catch (Exception $e) {
            Log::error('Gagal melakukan penyesuaian stok langsung', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', 'Gagal melakukan penyesuaian stok: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $product = Product::with([
            'variants' => function ($q) {
                $q->with('variantAttributes.attribute', 'variantAttributes.value')
                    ->where('is_active', 'Y');
            }
        ])->findOrFail($id);

        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';
        $tenants = $isFnB ? Tenant::where('store_id', session('store_id'))->where('stts', 'Y')->get() : collect();

        $loyaltyService = app(\App\Services\LoyaltyPointService::class);
        $pointSettings = $loyaltyService->getSettings(session('store_id'));
        $showRewardPoints = $pointSettings && $pointSettings->is_active && in_array($pointSettings->earning_method, ['product', 'hybrid']);

        // 🔹 Cek apakah produk punya attribute Divisi
        $hasDivisi = $product->variants->contains(function ($variant) {
            return $variant->variantAttributes
                ->contains(fn($va) => $va->attribute->nama === 'Divisi');
        });

        // 🔹 Grouping variant (adaptif)
        if ($hasDivisi) {
            $variantsByGroup = $product->variants
                ->sortBy(function ($variant) {
                    return $variant->variantAttributes
                        ->firstWhere('attribute.nama', 'Divisi')
                        ?->value?->urutan ?? 999;
                })
                ->groupBy(function ($variant) {
                    return $variant->variantAttributes
                        ->firstWhere('attribute.nama', 'Divisi')
                        ?->value?->nama ?? 'Lainnya';
                });
        } else {
            // Produk sederhana (Gender saja, dll)
            $variantsByGroup = collect([
                'Varian' => $product->variants
            ]);
        }

        // 🔹 Existing variant (untuk JS: disable & deteksi duplikat)
        $existingVariants = collect([]);

        // 🔹 Attribute master (tidak lagi dibutuhkan, kosongkan)
        $attributes = collect([]);

        return view('produk.edit', compact(
            'product',
            'attributes',
            'existingVariants',
            'variantsByGroup',
            'hasDivisi',
            'isFnB',
            'tenants',
            'showRewardPoints'
        ));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $rules = [
            'nama' => 'required|string|max:150',
        ];

        if ($isFnB) {
            $rules['tenant_id'] = 'nullable|exists:tenants,id';
            $rules['image'] = 'nullable|image|max:2048';
        }

        $request->validate($rules);

        $productData = [
            'nama_produk' => $request->nama,
            'deskripsi' => $request->deskripsi,
        ];

        if ($isFnB) {
            $productData['tenant_id'] = $request->tenant_id;
            if ($request->hasFile('image')) {
                if ($product->image) {
                    \Storage::disk('public')->delete($product->image);
                }
                $productData['image'] = $request->file('image')->store('products', 'public');
            }
        }
$product->update($productData);

        return redirect()
            ->route('produk.edit', $product->id)
            ->with('success', 'Produk berhasil diperbarui');
    }
    public function storeVariant(Request $request)
    {
        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $rules = [
            'product_id'          => 'required|exists:products,id',
            'variants'            => 'required|array|min:1',
            'variants.*.nama'     => 'required|string|max:150',
            'variants.*.harga_jual' => 'nullable|numeric|min:0',
            'variants.*.reward_points' => 'nullable|integer|min:0',
        ];

        if ($isFnB) {
            $rules['variants.*.track_stock'] = 'nullable|boolean';
            $rules['variants.*.cost_price_manual'] = 'nullable|numeric|min:0';
            $rules['variants.*.commission_type'] = 'nullable|in:global,percentage,nominal';
            $rules['variants.*.commission_rate'] = 'nullable|numeric|min:0';
        }

        $request->validate($rules);

        try {
            DB::transaction(function () use ($request, $isFnB) {
                $product = Product::findOrFail($request->product_id);
                $existingCount = $product->variants()->count();

                foreach ($request->variants as $i => $v) {
                    do {
                        $barcode = strtoupper(Str::random(8));
                    } while (ProductVariant::where('barcode', $barcode)->exists());

                    $sku = $product->kode_produk . '-' . str_pad($existingCount + $i + 1, 3, '0', STR_PAD_LEFT);

                    $variantData = [
                        'store_id'     => session('store_id'),
                        'product_id'   => $product->id,
                        'variant_name' => $v['nama'],
                        'sku'          => $sku,
                        'barcode'      => $barcode,
                        'harga_jual'   => $v['harga_jual'] ?? 0,
                        'reward_points' => $v['reward_points'] ?? 0,
                    ];

                    if ($isFnB) {
                        $variantData['track_stock'] = isset($v['track_stock']) ? (bool)$v['track_stock'] : true;
                        $variantData['cost_price_manual'] = $v['cost_price_manual'] ?? 0;
                        $variantData['commission_type'] = $v['commission_type'] ?? 'global';
                        $variantData['commission_rate'] = $v['commission_rate'] ?? 0;
                    }

                    $pv = ProductVariant::create($variantData);

                    $pv->barcodes()->create([
                        'barcode'   => $barcode,
                        'is_active' => 'Y',
                    ]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Varian berhasil ditambahkan.', 'icon' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage(), 'icon' => 'error'], 500);
        }
    }
    public function destroyVariant(ProductVariant $variant)
    {
        // Cek apakah variant memiliki stok di batch
        $hasStock = $variant->batches()->whereHas('movements')->exists();
        if ($hasStock) {
            $variant->update(['is_active' => 'N']);
            return response()
                ->json(['success' => true, 'message' => 'Variant memiliki histori stok. Variant dinonaktifkan.', 'icon' => 'info'], 200);
        }

        try {
            DB::transaction(function () use ($variant) {
                // Hapus relasi attribute
                $variant->variantAttributes()->delete();

                // Hapus batch kosong (jika ada)
                $variant->batches()->delete();

                // Hapus variant
                $variant->delete();
            });

            return response()
                ->json(['success' => true, 'message' => 'Variant berhasil dihapus.', 'icon' => 'success'], 200);
        } catch (\Exception $e) {
            return response()
                ->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage(), 'icon' => 'error'], 500);
        }
    }

    public function updateHarga(Request $request)
    {
        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $rules = [
            'variant_id' => 'required',
            'barcode' => 'required|string|max:100',
            'harga_jual' => 'required|numeric|min:0',
            'reward_points' => 'nullable|integer|min:0',
        ];

        if ($isFnB) {
            $rules['track_stock'] = 'nullable|boolean';
            $rules['cost_price_manual'] = 'nullable|numeric|min:0';
            $rules['commission_type'] = 'nullable|in:global,percentage,nominal';
            $rules['commission_rate'] = 'nullable|numeric|min:0';
        }

        $request->validate($rules);

        // Cek barcode unik
        $existingVariant = ProductVariant::where('barcode', $request->barcode)
            ->where('id', '!=', $request->variant_id)
            ->first();
        if ($existingVariant) {
            return back()->withInput()->with('error', 'Barcode sudah digunakan pada variant lain.');
        }

        $variantData = [
            'barcode' => $request->barcode,
            'harga_jual' => $request->harga_jual,
            'reward_points' => $request->reward_points ?? 0,
        ];

        if ($isFnB) {
            $variantData['track_stock'] = $request->boolean('track_stock');
            $variantData['cost_price_manual'] = $request->cost_price_manual ?? 0;
            $variantData['commission_type'] = $request->commission_type ?? 'global';
            $variantData['commission_rate'] = $request->commission_rate ?? 0;
        }

        ProductVariant::where('id', $request->variant_id)
            ->update($variantData);

        return back()->with('success', 'Harga berhasil diperbarui');
    }

    public function updateVariant(Request $request)
    {
        $store = Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $rules = [
            'variant_id'   => 'required|exists:product_variants,id',
            'variant_name' => 'required|string|max:150',
            'harga_jual'   => 'required|numeric|min:0',
            'reward_points' => 'nullable|integer|min:0',
        ];

        if ($isFnB) {
            $rules['track_stock'] = 'nullable|boolean';
            $rules['cost_price_manual'] = 'nullable|numeric|min:0';
            $rules['commission_type'] = 'nullable|in:global,percentage,nominal';
            $rules['commission_rate'] = 'nullable|numeric|min:0';
        }

        $request->validate($rules);

        try {
            $variant = ProductVariant::findOrFail($request->variant_id);
            
            $variantData = [
                'variant_name' => $request->variant_name,
                'harga_jual'   => $request->harga_jual,
                'reward_points' => $request->reward_points ?? 0,
            ];

            if ($isFnB) {
                $variantData['track_stock'] = $request->boolean('track_stock');
                $variantData['cost_price_manual'] = $request->cost_price_manual ?? 0;
                $variantData['commission_type'] = $request->commission_type ?? 'global';
                $variantData['commission_rate'] = $request->commission_rate ?? 0;
            }

            $variant->update($variantData);

            return response()->json([
                'success' => true,
                'message' => 'Varian berhasil diperbarui.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function barcodeImage($barcode)
    {
        $dns = new DNS1D();
        $barcode = $dns->getBarcodePNG($barcode, 'C128', 2, 60);

        return response(base64_decode($barcode))
            ->header('Content-Type', 'image/png');
    }
    public function barcodeDownload($barcode)
    {
        $dns = new DNS1D();
        $barcode = $dns->getBarcodePNG($barcode, 'C128', 2, 60);

        return response(base64_decode($barcode))
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="barcode-' . $barcode . '.png"');
    }
    public function downloadLabel40x30($variantId, $isShowPrice = true)
    {
        $variant = ProductVariant::with(['product', 'barcodeActive'])->findOrFail($variantId);

        $sku   = $variant->sku;
        $nama  = $variant->product->nama_produk;
        $harga = 'Rp ' . number_format($variant->harga_jual, 0, ',', '.');

        // === CANVAS (40x30mm @203dpi) ===
        $width  = 320;
        $height = 240;

        $canvas = imagecreatetruecolor($width, $height);
        $white  = imagecolorallocate($canvas, 255, 255, 255);
        $black  = imagecolorallocate($canvas, 0, 0, 0);

        imagefill($canvas, 0, 0, $white);

        // === BARCODE AUTO SCALE ===
        $dns = new DNS1D();

        $maxWidth = $width - 40; // margin kiri kanan (20px)
        $scale = 6; // mulai dari besar

        do {
            $barcodeBase64 = $dns->getBarcodePNG($variant->barcodeActive->barcode, 'C128', $scale, 80);
            $barcodeImage  = imagecreatefromstring(base64_decode($barcodeBase64));

            $bw = imagesx($barcodeImage);

            if ($bw > $maxWidth) {
                imagedestroy($barcodeImage);
                $scale--;
            } else {
                break;
            }
        } while ($scale > 1);

        // ukuran final barcode
        $bw = imagesx($barcodeImage);
        $bh = imagesy($barcodeImage);

        // posisi center
        $posX = ($width - $bw) / 2;
        $posY = 10;

        // === DRAW BARCODE (TANPA RESIZE) ===
        if (strlen($variant->barcodeActive->barcode) > 8) {

            imagecopyresampled(
                $canvas,
                $barcodeImage,
                10,
                10, // X, Y 
                0,
                0,
                300,
                105, // Width, Height 
                imagesx($barcodeImage),
                imagesy($barcodeImage)
            );
            $posY = 40;
        } else {
            imagecopy(
                $canvas,
                $barcodeImage,
                $posX,
                $posY,
                0,
                0,
                $bw,
                $bh
            );
        }

        // === SKU (CENTER) ===
        $skuFont = 12;
        $skuTextWidth = imagefontwidth($skuFont) * strlen($sku);
        $skuX = ($width - $skuTextWidth) / 2;

        imagestring($canvas, $skuFont, $skuX, $posY + $bh + 5, $sku, $black);

        // === NAMA PRODUK ===
        $namaFont = 10;
        $nama = mb_strimwidth($nama, 0, 32, '…');
        $namaTextWidth = imagefontwidth($namaFont) * strlen($nama);
        $namaX = ($width - $namaTextWidth) / 2;

        imagestring($canvas, $namaFont, $namaX, $posY + $bh + 25, $nama, $black);

        // === HARGA ===
        if ($isShowPrice) {
            $hargaFont = 10;
            $hargaTextWidth = imagefontwidth($hargaFont) * strlen($harga);
            $hargaX = ($width - $hargaTextWidth) / 2;

            // bold effect
            imagestring($canvas, $hargaFont, $hargaX, $posY + $bh + 50, $harga, $black);
            imagestring($canvas, $hargaFont, $hargaX + 1, $posY + $bh + 50, $harga, $black);
        }

        // === OUTPUT PNG ===
        ob_start();
        imagepng($canvas);
        $imageData = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($barcodeImage);

        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header(
                'Content-Disposition',
                'attachment; filename="label-40x30-' . $sku . '.png"'
            );
    }
    public function generateBarcode(ProductVariant $variant)
    {
        return DB::transaction(function () use ($variant) {
            do {
                $newBarcode = strtoupper(Str::random(8));
                $exists = ProductVariantBarcode::where('barcode', $newBarcode)->exists();
            } while ($exists);
            // Nonaktifkan semua barcode lama
            ProductVariantBarcode::where('product_variant_id', $variant->id)
                ->update(['is_active' => 'N']);

            // Simpan barcode baru
            ProductVariantBarcode::create([
                'product_variant_id' => $variant->id,
                'barcode' => $newBarcode,
                'is_active' => 'Y'
            ]);
            // (Optional) kalau masih simpan di tabel variant juga
            $variant->update(['barcode' => $newBarcode]);

            return response()->json([
                'success' => true,
                'barcode' => $newBarcode,
                'sku' => $variant->sku,
                'message' => 'Barcode baru berhasil digenerate.',
                'icon' => 'success'
            ], 200);
        });
    }
    public function searchProduct(Request $request)
    {
        $q = $request->get('q');
        $keywords = explode(' ', $q);

        $variants = ProductVariant::with(['product', 'barcodeActive'])
            ->where('is_active', 'Y')
            ->whereHas('product', fn($p) => $p->where('store_id', session('store_id')))
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    $query->where(function ($sub) use ($word) {

                        $sub->where('sku', 'like', "%$word%")
                            ->orWhere('variant_name', 'like', "%$word%")
                            ->orWhereHas('product', function ($q2) use ($word) {
                                $q2->where('nama_produk', 'like', "%$word%");
                            })
                            ->orWhereHas('barcodes', function ($q3) use ($word) {
                                $q3->where('barcode', 'like', "%$word%");
                            })
                            ->orWhereHas('variantAttributes.value', function ($q4) use ($word) {
                                $q4->where('nama', 'like', "%$word%");
                            })
                            ->orWhereHas('variantAttributes.attribute', function ($q5) use ($word) {
                                $q5->where('nama', 'like', "%$word%");
                            });
                    });
                }
            })
            ->limit(10)
            ->get();

        return response()->json($variants->map(function ($v) {
            return [
                'id' => $v->id,
                'nama_produk' => $v->product->nama_produk,
                'sku' => $v->sku,
                'barcode' => optional($v->barcodeActive)->barcode,
                'label' => $v->variant_label,
                'url' => route('produk.variants.detail', ['product' => $v->product_id, 'variant' => $v->id]),
            ];
        }));
    }

    public function addBarcodeToVariant(Request $request, ProductVariant $variant)
    {
        $request->validate([
            'barcode' => 'required|string|max:100|unique:product_variant_barcodes,barcode',
        ], [
            'barcode.unique' => 'Barcode sudah digunakan pada variant lain.',
            'barcode.required' => 'Field barcode tidak boleh kosong.',
            'barcode.string' => 'Field barcode harus berupa string.',
            'barcode.max' => 'Panjang karakter barcode maksimal 100.',
        ]);

        try {
            // Nonaktifkan semua barcode lama
            ProductVariantBarcode::where('product_variant_id', $variant->id)
                ->update(['is_active' => 'N']);
            ProductVariantBarcode::create([
                'product_variant_id' => $variant->id,
                'barcode' => strtoupper($request->barcode),
                'is_active' => 'Y'
            ]);

            return redirect()->back()
                ->with('success', 'Barcode baru berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function toggleBarcodeStatus(Request $request, ProductVariantBarcode $barcode)
    {
        try {
            // non aktifkan semua barcode variant
            ProductVariantBarcode::where('product_variant_id', $barcode->product_variant_id)
                ->update(['is_active' => 'N']);
            $newStatus = $barcode->is_active === 'Y' ? 'N' : 'Y';
            $barcode->update(['is_active' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Status barcode berhasil diubah.',
                'new_status' => $newStatus,
                'new_label' => $newStatus === 'Y' ? 'Nonaktifkan' : 'Aktifkan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteBarcode(ProductVariantBarcode $barcode)
    {
        try {
            $barcode->delete();

            return response()->json([
                'success' => true,
                'message' => 'Barcode berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateAdjustmentCode($prefix)
    {
        $datePart = date('Ymd');
        $storeId = session('store_id');
        $count = StockAdjustment::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        do {
            $code = sprintf("%s-%s-%04d", $prefix, $datePart, $count);
            $exists = StockAdjustment::withoutGlobalScopes()
                ->where('store_id', $storeId)
                ->where('code', $code)
                ->exists();
            if ($exists) {
                $count++;
            }
        } while ($exists);

        return $code;
    }

    private function generateAdjustmentJournal(StockAdjustment $adjustment, array $result)
    {
        if (($result['increase'] ?? 0) <= 0 && ($result['decrease'] ?? 0) <= 0) {
            return;
        }

        if ($adjustment->nojurnal) {
            return;
        }

        $inventoryAccount = $adjustment->posisi === 'store' ? '11.04.13' : '11.04.14';
        $diffAccount = '11.04.15';

        $entries = [];

        if (($result['increase'] ?? 0) > 0) {
            $entries[] = [
                'kode_akun' => $inventoryAccount,
                'amount'    => $result['increase'],
                'type'      => 'debet',
            ];
            $entries[] = [
                'kode_akun' => $diffAccount,
                'amount'    => $result['increase'],
                'type'      => 'kredit',
            ];
        }

        if (($result['decrease'] ?? 0) > 0) {
            $entries[] = [
                'kode_akun' => $diffAccount,
                'amount'    => $result['decrease'],
                'type'      => 'debet',
            ];
            $entries[] = [
                'kode_akun' => $inventoryAccount,
                'amount'    => $result['decrease'],
                'type'      => 'kredit',
            ];
        }

        if (count($entries) < 2) {
            return;
        }

        try {
            $journalService = new JournalEntryService();
            $voucher = $journalService->create(
                [
                    'tanggal'     => $adjustment->effective_date,
                    'uraian'      => 'Penyesuaian Stok ' . $adjustment->code,
                    'jns_trx'     => 12,
                    'ref_tagihan' => $adjustment->id,
                    'divisi'      => 8,
                ],
                $entries
            );

            $adjustment->update(['nojurnal' => $voucher->id]);
        } catch (\Throwable $e) {
            Log::error('Gagal generate jurnal penyesuaian stok', [
                'stock_adjustment_id' => $adjustment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
