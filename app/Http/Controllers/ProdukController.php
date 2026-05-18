<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantBarcode;
use App\Models\StockMovement;
use App\Models\VariantAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Milon\Barcode\DNS1D;
use Yajra\DataTables\Facades\DataTables;

class ProdukController extends Controller
{
    public function index()
    {
        return view('produk.index');
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
        $storeId = session('store_id');
        $attributes = Attribute::where('store_id', $storeId)
            ->with('values')
            ->orderBy('urutan')
            ->get();
        return view('produk.create', compact('attributes'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:50|unique:products,kode_produk',
            'nama' => 'required|string|max:150',
            'variants' => 'nullable|array',
        ]);
        try {
            DB::transaction(function () use ($request) {

                // 1️⃣ Simpan produk
                $product = Product::create([
                    'store_id'    => session('store_id'),
                    'kode_produk' => strtoupper($request->kode),
                    'nama_produk' => $request->nama,
                    'deskripsi' => $request->deskripsi,
                ]);

                /**
                 * 2️⃣ Jika TIDAK ADA VARIAN
                 * tetap buat 1 variant default
                 */
                if (empty($request->variants)) {
                    while (true) {
                        $newBarcode = strtoupper(Str::random(8));

                        // Cek keunikan barcode
                        $exists = ProductVariant::where('barcode', $newBarcode)->exists();
                        if (!$exists) {
                            break; // keluar dari loop jika barcode unik
                        }
                    }
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $product->kode_produk . '-001',
                        'barcode' => $newBarcode,
                        'harga_jual' => 0,
                    ]);
                    return;
                }

                /**
                 * 3️⃣ Loop VARIAN
                 */
                foreach ($request->variants as $variant) {

                    // Ambil attribute value
                    $valueIds = explode(',', $variant['values']);

                    $values = AttributeValue::with('attribute')
                        ->whereIn('id', $valueIds)
                        ->get()
                        ->sortBy(fn($v) => $v->attribute->urutan)
                        ->values();

                    /**
                     * 4️⃣ Generate SKU (LOGIS & STABIL)
                     * PRM-SD-L-L
                     */
                    $sku = $product->kode_produk . '-' . $values->pluck('kode')->implode('-');

                    /**
                     * 5️⃣ Simpan VARIANT
                     */
                    while (true) {
                        $newBarcode = strtoupper(Str::random(8));

                        // Cek keunikan barcode
                        $exists = ProductVariant::where('barcode', $newBarcode)->exists();
                        if (!$exists) {
                            break; // keluar dari loop jika barcode unik
                        }
                    }
                    $productVariant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'barcode' => $newBarcode,
                        'harga_jual' => $variant['harga'] ?? 0,
                    ]);

                    /**
                     * 6️⃣ Simpan relasi attribute
                     */
                    foreach ($values as $value) {
                        VariantAttribute::create([
                            'product_variant_id' => $productVariant->id,
                            'attribute_id' => $value->attribute->id,
                            'attribute_value_id' => $value->id
                        ]);
                    }
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
        return view('produk.show', compact('product', 'variantsByGroup', 'hasDivisi'));
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
    public function edit($id)
    {
        $product = Product::with([
            'variants' => function ($q) {
                $q->with('variantAttributes.attribute', 'variantAttributes.value')
                    ->where('is_active', 'Y');
            }
        ])->findOrFail($id);

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
        $existingVariants = $product->variants->map(function ($v) {
            return [
                'id' => $v->id,
                'sku' => $v->sku,
                'barcode' => $v->barcode,
                'harga_jual' => $v->harga_jual,
                'attribute_value_ids' => $v->variantAttributes
                    ->pluck('attribute_value_id')
                    ->sort()
                    ->values()
                    ->toArray(),
            ];
        });

        // 🔹 Attribute master (untuk modal)
        $attributes = Attribute::where('store_id', session('store_id'))
            ->with('values')
            ->orderBy('urutan')
            ->get();

        return view('produk.edit', compact(
            'product',
            'attributes',
            'existingVariants',
            'variantsByGroup',
            'hasDivisi'
        ));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:150',
        ]);

        $product->update([
            'nama_produk' => $request->nama,
            'deskripsi' => $request->deskripsi,
        ]);

        return redirect()
            ->route('produk.edit', $product->id)
            ->with('success', 'Produk berhasil diperbarui');
    }
    public function storeVariant(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variants' => 'nullable|array',
        ]);
        try {
            DB::transaction(function () use ($request) {
                $product = Product::findOrFail($request->product_id);
                /**
                 * 3️⃣ Loop VARIAN
                 */
                foreach ($request->variants as $variant) {

                    // Ambil attribute value
                    $valueIds = explode(',', $variant['values']);

                    $values = AttributeValue::with('attribute')->whereIn('id', $valueIds)->get();

                    /**
                     * 4️⃣ Generate SKU (LOGIS & STABIL)
                     * PRM-SD-L-L
                     */
                    // $sku = $product->kode_produk . '-' . $values->pluck('kode')->implode('-');

                    /**
                     * 5️⃣ Simpan VARIANT
                     */
                    while (true) {
                        $newBarcode = strtoupper(Str::random(8));

                        // Cek keunikan barcode
                        $exists = ProductVariant::where('barcode', $newBarcode)->exists();
                        if (!$exists) {
                            break; // keluar dari loop jika barcode unik
                        }
                    }
                    $productVariant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variant['sku'],
                        'barcode' => $newBarcode,
                        'harga_jual' => $variant['harga_jual'] ?? 0,
                    ]);

                    /**
                     * 6️⃣ Simpan relasi attribute
                     */
                    foreach ($values as $value) {
                        VariantAttribute::create([
                            'product_variant_id' => $productVariant->id,
                            'attribute_id' => $value->attribute->id,
                            'attribute_value_id' => $value->id
                        ]);
                    }
                }
            });

            return response()->json(['success' => true, 'message' => 'Variant berhasil ditambahkan.', 'icon' => 'success'], 200);
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
        $request->validate([
            'variant_id' => 'required',
            'barcode' => 'required|string|max:100',
            'harga_jual' => 'required|numeric|min:0',
        ]);
        // Cek barcode unik
        $existingVariant = ProductVariant::where('barcode', $request->barcode)
            ->where('id', '!=', $request->variant_id)
            ->first();
        if ($existingVariant) {
            return back()->withInput()->with('error', 'Barcode sudah digunakan pada variant lain.');
        }

        ProductVariant::where('id', $request->variant_id)
            ->update([
                'barcode' => $request->barcode,
                'harga_jual' => $request->harga_jual
            ]);

        return back()->with('success', 'Harga berhasil diperbarui');
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
                'label' => $v->variasi_label,
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
}
