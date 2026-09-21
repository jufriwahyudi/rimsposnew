<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\StockBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExternalApiController extends Controller
{
    /**
     * GET /api/v1/external/store
     * Mengambil profil dan informasi toko yang terasosiasi dengan API Key.
     */
    public function storeInfo(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');

        return response()->json([
            'status'  => 'success',
            'message' => 'Informasi profil toko berhasil diambil.',
            'data'    => [
                'id'            => $store->id,
                'name'          => $store->name,
                'code'          => $store->code,
                'business_type' => $store->business_type,
                'address'       => $store->address,
                'city'          => $store->city,
                'phone'         => $store->phone,
                'printer_type'  => $store->printer_type,
                'logo_url'      => $store->logo ? url('storage/' . ltrim($store->logo, '/')) : null,
                'qris_url'      => $store->qris_image_url,
                'addons'        => [
                    'self_service'  => (bool) $store->addon_self_service,
                    'kds'           => (bool) $store->addon_kds,
                    'multi_printer' => (bool) $store->addon_multi_printer,
                    'multi_unit'    => (bool) $store->addon_multi_unit,
                    'fefo'          => (bool) $store->addon_fefo,
                    'concoction'    => (bool) $store->addon_concoction,
                    'sales_person'  => (bool) $store->addon_sales_person,
                ],
                'subscription'  => $store->subscription ? [
                    'status'      => $store->subscription->status,
                    'starts_at'   => $store->subscription->starts_at?->toIso8601String(),
                    'expires_at'  => $store->subscription->expires_at?->toIso8601String(),
                    'is_expired'  => $store->subscription->isExpired(),
                ] : null,
            ],
            'meta'    => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/external/categories
     * Mengambil daftar kategori produk toko.
     */
    public function categories(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');

        $categories = ProductCategory::where('store_id', $store->id)
            ->where('is_active', true)
            ->withCount(['products' => function ($q) use ($store) {
                $q->where('store_id', $store->id);
            }])
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($cat) {
                return [
                    'id'             => $cat->id,
                    'name'           => $cat->name,
                    'slug'           => $cat->slug,
                    'icon'           => $cat->icon,
                    'sort_order'     => $cat->sort_order,
                    'products_count' => $cat->products_count,
                ];
            });

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar kategori berhasil diambil.',
            'data'    => $categories,
            'meta'    => [
                'total'     => $categories->count(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/external/products
     * Mengambil daftar katalog produk beserta varian harga dan stok.
     */
    public function products(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');

        $search     = $request->query('search');
        $categoryId = $request->query('category_id');
        $perPage    = min((int) ($request->query('per_page', 20)), 100);

        $query = Product::where('store_id', $store->id)
            ->with([
                'category:id,name,slug',
                'units:id,product_id,unit_name,conversion_factor,price',
                'variants' => function ($q) use ($store) {
                    $q->where('store_id', $store->id)
                      ->where('is_active', true);
                },
                'variants.barcodes',
            ]);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                  ->orWhere('kode_produk', 'like', "%{$search}%")
                  ->orWhereHas('variants', function ($vq) use ($search) {
                      $vq->where('variant_name', 'like', "%{$search}%")
                         ->orWhere('sku', 'like', "%{$search}%")
                         ->orWhere('barcode', 'like', "%{$search}%");
                  });
            });
        }

        $paginator = $query->orderBy('nama_produk', 'asc')->paginate($perPage);

        $formattedData = collect($paginator->items())->map(function ($product) {
            return [
                'id'           => $product->id,
                'kode_produk'  => $product->kode_produk,
                'nama_produk'  => $product->nama_produk,
                'deskripsi'    => $product->deskripsi,
                'product_type' => $product->product_type,
                'image_url'    => $product->image_url,
                'category'     => $product->category ? [
                    'id'   => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'units'        => $product->units->map(function ($u) {
                    return [
                        'id'                => $u->id,
                        'unit_name'         => $u->unit_name,
                        'conversion_factor' => (float) $u->conversion_factor,
                        'price'             => (float) $u->price,
                    ];
                }),
                'variants'     => $product->variants->map(function ($variant) {
                    return [
                        'id'              => $variant->id,
                        'variant_name'    => $variant->variant_name,
                        'sku'             => $variant->sku,
                        'barcode'         => $variant->barcode,
                        'harga_jual'      => (float) $variant->harga_jual,
                        'track_stock'     => (bool) $variant->track_stock,
                        'stock_store'     => (float) $variant->stok_store,
                        'stock_warehouse' => (float) $variant->stok_warehouse,
                        'stock_total'     => (float) $variant->stok_total,
                        'is_available'    => (bool) $variant->is_available,
                        'is_sold_out'     => (bool) $variant->is_sold_out,
                        'image_url'       => $variant->image_url,
                        'barcodes'        => $variant->barcodes->pluck('barcode')->toArray(),
                    ];
                }),
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar produk berhasil diambil.',
            'data'    => $formattedData,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'timestamp'    => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/external/products/{id}
     * Mengambil detail satu produk berdasarkan ID produk.
     */
    public function productDetail(Request $request, int $id): JsonResponse
    {
        $store = $request->attributes->get('store');

        $product = Product::where('store_id', $store->id)
            ->where('id', $id)
            ->with([
                'category:id,name,slug',
                'units',
                'variants' => function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                },
                'variants.barcodes',
            ])
            ->first();

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produk tidak ditemukan.',
                'code'    => 404,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail produk berhasil diambil.',
            'data'    => [
                'id'           => $product->id,
                'kode_produk'  => $product->kode_produk,
                'nama_produk'  => $product->nama_produk,
                'deskripsi'    => $product->deskripsi,
                'product_type' => $product->product_type,
                'image_url'    => $product->image_url,
                'category'     => $product->category ? [
                    'id'   => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'units'        => $product->units->map(function ($u) {
                    return [
                        'id'                => $u->id,
                        'unit_name'         => $u->unit_name,
                        'conversion_factor' => (float) $u->conversion_factor,
                        'price'             => (float) $u->price,
                    ];
                }),
                'variants'     => $product->variants->map(function ($variant) {
                    return [
                        'id'              => $variant->id,
                        'variant_name'    => $variant->variant_name,
                        'sku'             => $variant->sku,
                        'barcode'         => $variant->barcode,
                        'harga_jual'      => (float) $variant->harga_jual,
                        'track_stock'     => (bool) $variant->track_stock,
                        'stock_store'     => (float) $variant->stok_store,
                        'stock_warehouse' => (float) $variant->stok_warehouse,
                        'stock_total'     => (float) $variant->stok_total,
                        'is_available'    => (bool) $variant->is_available,
                        'is_sold_out'     => (bool) $variant->is_sold_out,
                        'image_url'       => $variant->image_url,
                        'barcodes'        => $variant->barcodes->pluck('barcode')->toArray(),
                    ];
                }),
            ],
            'meta'    => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/external/stock
     * Mengambil ringkasan data stok barang toko real-time.
     */
    public function stock(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');

        $search     = $request->query('search');
        $lowStock   = $request->boolean('low_stock');
        $threshold  = (float) $request->query('threshold', 5);
        $perPage    = min((int) ($request->query('per_page', 50)), 150);

        $query = ProductVariant::where('store_id', $store->id)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->with(['product:id,nama_produk,kode_produk']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('variant_name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('nama_produk', 'like', "%{$search}%");
                  });
            });
        }

        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(function ($v) {
            return [
                'variant_id'      => $v->id,
                'product_id'      => $v->product_id,
                'product_name'    => $v->product->nama_produk ?? '-',
                'variant_name'    => $v->variant_name ?: 'Default',
                'sku'             => $v->sku,
                'barcode'         => $v->barcode,
                'stock_store'     => (float) $v->stok_store,
                'stock_warehouse' => (float) $v->stok_warehouse,
                'stock_total'     => (float) $v->stok_total,
                'is_sold_out'     => (bool) $v->is_sold_out,
            ];
        });

        if ($lowStock) {
            $items = $items->filter(function ($item) use ($threshold) {
                return $item['stock_total'] <= $threshold;
            })->values();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Data stok toko berhasil diambil.',
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'timestamp'    => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/external/sales
     * Mengambil riwayat transaksi penjualan toko.
     */
    public function sales(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');

        $startDate     = $request->query('start_date');
        $endDate       = $request->query('end_date');
        $status        = $request->query('status'); // paid, unpaid, void, draft
        $paymentMethod = $request->query('payment_method');
        $perPage       = min((int) ($request->query('per_page', 20)), 100);

        $query = Sale::where('store_id', $store->id)
            ->with(['customer:id,name,phone'])
            ->withCount('items');

        if ($startDate) {
            $query->whereDate('sale_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('sale_date', '<=', $endDate);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        $paginator = $query->orderBy('sale_date', 'desc')->paginate($perPage);

        $formatted = collect($paginator->items())->map(function ($sale) {
            return [
                'id'             => $sale->id,
                'no_faktur'      => $sale->no_faktur,
                'sale_date'      => $sale->sale_date?->toIso8601String(),
                'status'         => $sale->status,
                'payment_method' => $sale->payment_method,
                'payment_status' => $sale->payment_status,
                'customer'       => $sale->customer ? [
                    'id'    => $sale->customer->id,
                    'name'  => $sale->customer->name,
                    'phone' => $sale->customer->phone,
                ] : null,
                'subtotal'       => (float) $sale->subtotal,
                'discount_total' => (float) $sale->discount_total,
                'tax'            => (float) $sale->tax,
                'tip_amount'     => (float) ($sale->tip_amount ?? 0),
                'grand_total'    => (float) $sale->grand_total,
                'total_paid'     => (float) $sale->total_paid,
                'change_amount'  => (float) $sale->change_amount,
                'items_count'    => $sale->items_count,
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat transaksi penjualan berhasil diambil.',
            'data'    => $formatted,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'timestamp'    => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/external/sales/{id}
     * Mengambil detail lengkap satu transaksi penjualan beserta rincian item.
     */
    public function saleDetail(Request $request, int $id): JsonResponse
    {
        $store = $request->attributes->get('store');

        $sale = Sale::where('store_id', $store->id)
            ->where('id', $id)
            ->with(['customer:id,name,phone,email', 'items'])
            ->first();

        if (!$sale) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data penjualan tidak ditemukan.',
                'code'    => 404,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail transaksi penjualan berhasil diambil.',
            'data'    => [
                'id'             => $sale->id,
                'no_faktur'      => $sale->no_faktur,
                'sale_date'      => $sale->sale_date?->toIso8601String(),
                'status'         => $sale->status,
                'payment_method' => $sale->payment_method,
                'payment_status' => $sale->payment_status,
                'notes'          => $sale->notes,
                'customer'       => $sale->customer ? [
                    'id'    => $sale->customer->id,
                    'name'  => $sale->customer->name,
                    'phone' => $sale->customer->phone,
                    'email' => $sale->customer->email,
                ] : null,
                'subtotal'       => (float) $sale->subtotal,
                'discount_total' => (float) $sale->discount_total,
                'tax'            => (float) $sale->tax,
                'tip_amount'     => (float) ($sale->tip_amount ?? 0),
                'grand_total'    => (float) $sale->grand_total,
                'total_paid'     => (float) $sale->total_paid,
                'change_amount'  => (float) $sale->change_amount,
                'items'          => $sale->items->map(function ($item) {
                    return [
                        'id'             => $item->id,
                        'product_id'     => $item->product_id,
                        'product_name'   => $item->product_name,
                        'variant_id'     => $item->product_variant_id,
                        'variant_name'   => $item->variant_name,
                        'qty'            => (float) $item->qty,
                        'price'          => (float) $item->price,
                        'discount'       => (float) $item->discount,
                        'subtotal'       => (float) $item->subtotal,
                        'notes'          => $item->notes,
                        'status'         => $item->status,
                    ];
                }),
            ],
            'meta'    => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
