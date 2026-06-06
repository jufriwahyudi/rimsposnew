<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Tenant;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerSelfServiceController extends Controller
{
    /**
     * Show the Customer Menu Portal.
     */
    public function index(Request $request)
    {
        $storeId = $request->integer('store_id');
        $table   = $request->input('table');
        $hash    = $request->input('hash');

        if (!$storeId || !$table || !$hash) {
            abort(400, 'Parameter tidak lengkap.');
        }

        // Verify signature
        $expectedHash = hash_hmac('sha256', "store_id={$storeId}&table={$table}", config('app.key'));
        if (!hash_equals($expectedHash, $hash)) {
            abort(403, 'Akses tidak sah: QR Code tidak valid.');
        }

        $store = Store::findOrFail($storeId);
        if ($store->business_type !== 'fnb' || !$store->addon_self_service) {
            return response()->view('self-service.addon_disabled', compact('store'), 403);
        }

        // Set tenant context
        Tenant::set($storeId);

        // Fetch products, variants, and tenants (food stalls)
        $products = Product::with(['variants' => function ($q) {
            $q->where('is_active', 'Y');
        }, 'tenant'])->where('store_id', $storeId)->get();

        $tenants = \App\Models\Tenant::where('store_id', $storeId)
            ->where('stts', 'Y')
            ->get();

        return view('self-service.order_portal', compact('store', 'table', 'hash', 'products', 'tenants'));
    }

    /**
     * Submit self service order.
     */
    public function submitOrder(Request $request)
    {
        $request->validate([
            'store_id'      => 'required|integer|exists:stores,id',
            'table_number'  => 'required|string|max:50',
            'customer_name' => 'required|string|max:100',
            'hash'          => 'required|string',
            'items'         => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.notes'      => 'nullable|string|max:255',
        ]);

        $storeId = $request->input('store_id');
        $table   = $request->input('table_number');
        $hash    = $request->input('hash');

        // Verify signature
        $expectedHash = hash_hmac('sha256', "store_id={$storeId}&table={$table}", config('app.key'));
        if (!hash_equals($expectedHash, $hash)) {
            return response()->json(['message' => 'Akses tidak sah: QR Code tidak valid.'], 403);
        }

        $store = Store::findOrFail($storeId);
        if ($store->business_type !== 'fnb' || !$store->addon_self_service) {
            return response()->json(['message' => 'Fitur Self-Service dinonaktifkan.'], 403);
        }

        try {
            $sale = DB::transaction(function () use ($request, $storeId, $table) {
                // Set context
                Tenant::set($storeId);

                // Find existing active unpaid sale for this table
                $existingSale = Sale::where('store_id', $storeId)
                    ->where('table_number', $table)
                    ->where('payment_status', 'unpaid')
                    ->whereNotIn('status', ['void'])
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();

                $itemsData = $request->input('items');
                $subtotal = 0;
                $itemsToCreate = [];

                foreach ($itemsData as $itemData) {
                    $variant = ProductVariant::with('product')->findOrFail($itemData['variant_id']);
                    $qty = (int)$itemData['qty'];
                    $price = (float)$variant->harga_jual;
                    $itemSubtotal = $price * $qty;
                    $subtotal += $itemSubtotal;

                    // Keep product name clean, store notes separately
                    $productName = $variant->variant_label ?? $variant->product->nama_produk;
                    $notes = trim($itemData['notes'] ?? '');

                    $itemsToCreate[] = [
                        'variant' => $variant,
                        'qty' => $qty,
                        'price' => $price,
                        'subtotal' => $itemSubtotal,
                        'product_name' => $productName,
                        'notes' => $notes,
                    ];
                }

                if ($existingSale) {
                    // Merge into existing sale
                    foreach ($itemsToCreate as $item) {
                        $variant = $item['variant'];

                        // Check if exact same item variant and notes already exists (only merge if not yet confirmed by cashier)
                        $existingItem = null;
                        if ($existingSale->user_id === null) {
                            $existingItem = SaleItem::where('sale_id', $existingSale->id)
                                ->where('product_variant_id', $variant->id)
                                ->where('notes', $item['notes'])
                                ->first();
                        }

                        if ($existingItem) {
                            $existingItem->increment('qty', $item['qty']);
                            $existingItem->increment('subtotal', $item['subtotal']);
                            $saleItem = $existingItem;
                        } else {
                            $saleItem = SaleItem::create([
                                'sale_id'            => $existingSale->id,
                                'product_id'         => $variant->product_id,
                                'product_variant_id' => $variant->id,
                                'sku'                => $variant->sku,
                                'product_name'       => $item['product_name'],
                                'price'              => $item['price'],
                                'qty'                => $item['qty'],
                                'discount_amount'    => 0,
                                'subtotal'           => $item['subtotal'],
                                'notes'              => $item['notes'],
                            ]);
                        }

                        // If sale is already confirmed, the new item starts as 'pending' in KDS so the kitchen controls it
                        if ($existingSale->user_id !== null) {
                            $saleItem->kds_status = 'pending';
                        }

                        // Decrement FIFO stocks immediately
                        $this->issueFIFOWithBatchLog(
                            now()->format('Y-m-d H:i:s'),
                            $variant->id,
                            'store',
                            $item['qty'],
                            $saleItem
                        );
                    }

                    // Recalculate parent sale totals
                    $totalSubtotal = SaleItem::where('sale_id', $existingSale->id)->sum('subtotal');
                    $existingSale->update([
                        'subtotal'    => $totalSubtotal,
                        'grand_total' => $totalSubtotal - $existingSale->discount_total + $existingSale->tax_total,
                    ]);

                    return $existingSale;
                } else {
                    // Create new sale
                    $invoiceNumber = 'QR-' . now()->format('YmdHis') . rand(10, 99);

                    $sale = Sale::create([
                        'store_id'       => $storeId,
                        'invoice_number' => $invoiceNumber,
                        'table_number'   => $table,
                        'sale_date'      => now(),
                        'sale_type'      => 'retail',
                        'customer_name'  => $request->input('customer_name'),
                        'subtotal'       => $subtotal,
                        'discount_total' => 0,
                        'trans_discount' => 0,
                        'tax_total'      => 0,
                        'grand_total'    => $subtotal,
                        'paid_amount'    => 0,
                        'change_amount'  => 0,
                        'status'         => 'hold',
                        'payment_status' => 'unpaid',
                    ]);

                    foreach ($itemsToCreate as $item) {
                        $variant = $item['variant'];

                        $saleItem = SaleItem::create([
                            'sale_id'            => $sale->id,
                            'product_id'         => $variant->product_id,
                            'product_variant_id' => $variant->id,
                            'sku'                => $variant->sku,
                            'product_name'       => $item['product_name'],
                            'price'              => $item['price'],
                            'qty'                => $item['qty'],
                            'discount_amount'    => 0,
                            'subtotal'           => $item['subtotal'],
                            'notes'              => $item['notes'],
                        ]);

                        // Decrement FIFO stocks immediately
                        $this->issueFIFOWithBatchLog(
                            now()->format('Y-m-d H:i:s'),
                            $variant->id,
                            'store',
                            $item['qty'],
                            $saleItem
                        );
                    }

                    return $sale;
                }
            });

            // Sync to Cloud Firestore
            app(FirestoreService::class)->syncOrder($sale);

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dikirim ke kasir.',
                'invoice' => $sale->invoice_number,
                'sale_id' => $sale->id,
            ]);

        } catch (\Throwable $e) {
            Log::error("Failed to submit self service order: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Gagal memproses pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show real-time order status page.
     */
    public function status(Request $request, $id)
    {
        $sale = Sale::where('invoice_number', $id)->firstOrFail();
        $store = Store::findOrFail($sale->store_id);

        return view('self-service.order_status', compact('sale', 'store'));
    }

    /**
     * Show QR Code generator utility.
     */
    public function generateQrCode(Request $request)
    {
        // Only allow Store Admin or Superadmin
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('select-store.index')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        $store = Store::findOrFail($storeId);
        
        // Fetch all generated QR Codes for the current store
        $qrCodes = \App\Models\StoreQrCode::orderBy('table_name')->get();

        return view('self-service.qr_generator', compact('store', 'qrCodes'));
    }

    /**
     * Store a new QR Code generator record in the database.
     */
    public function storeQrCode(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string|max:100',
        ]);

        $storeId = session('store_id');
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'Silakan pilih toko terlebih dahulu.'], 400);
        }

        $tableName = trim($request->input('table_name'));

        // Check if QR Code with the same table name already exists for this store
        $exists = \App\Models\StoreQrCode::where('table_name', $tableName)->first();
        if ($exists) {
            return response()->json([
                'success' => true,
                'already_exists' => true,
                'message' => "QR Code untuk {$tableName} sudah pernah dibuat.",
                'qr_code' => [
                    'id' => $exists->id,
                    'table_name' => $exists->table_name,
                    'url' => $exists->url,
                    'hash' => $exists->hash,
                    'image_url' => route('settings.qr-generator.image', $exists->id),
                    'download_url' => route('settings.qr-generator.download', $exists->id),
                ]
            ]);
        }

        try {
            $hash = hash_hmac('sha256', "store_id={$storeId}&table={$tableName}", config('app.key'));
            $url = route('order.index', [
                'store_id' => $storeId,
                'table' => $tableName,
                'hash' => $hash
            ]);

            $qrCode = \App\Models\StoreQrCode::create([
                'store_id' => $storeId,
                'table_name' => $tableName,
                'url' => $url,
                'hash' => $hash,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR Code berhasil dibuat dan disimpan.',
                'qr_code' => [
                    'id' => $qrCode->id,
                    'table_name' => $qrCode->table_name,
                    'url' => $qrCode->url,
                    'hash' => $qrCode->hash,
                    'image_url' => route('settings.qr-generator.image', $qrCode->id),
                    'download_url' => route('settings.qr-generator.download', $qrCode->id),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal membuat QR Code: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal membuat QR Code: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show QR Code image from DNS2D offline generator.
     */
    public function showQrCodeImage($id)
    {
        $qrCode = \App\Models\StoreQrCode::findOrFail($id);
        
        $dns = new \Milon\Barcode\DNS2D();
        $pngBase64 = $dns->getBarcodePNG($qrCode->url, 'QRCODE', 10, 10);
        
        return response(base64_decode($pngBase64))
            ->header('Content-Type', 'image/png');
    }

    /**
     * Download QR Code image as PNG.
     */
    public function downloadQrCode($id)
    {
        $qrCode = \App\Models\StoreQrCode::findOrFail($id);
        
        $dns = new \Milon\Barcode\DNS2D();
        $pngBase64 = $dns->getBarcodePNG($qrCode->url, 'QRCODE', 12, 12);
        
        $filename = 'qrcode-' . \Illuminate\Support\Str::slug($qrCode->table_name) . '.png';
        
        return response(base64_decode($pngBase64))
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Delete QR Code from database.
     */
    public function deleteQrCode($id)
    {
        try {
            $qrCode = \App\Models\StoreQrCode::findOrFail($id);
            $qrCode->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'QR Code berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus QR Code: ' . $e->getMessage()
            ], 500);
        }
    }

    // ── FIFO stock locking helper ───────────────────────────────────────────

    protected function issueFIFOWithBatchLog(
        string $transactionDate,
        int $variantId,
        string $posisi,
        int $qty,
        SaleItem $saleItem,
        string $refType = 'SalePos'
    ) {
        $variant = ProductVariant::find($variantId);
        if ($variant && !$variant->track_stock) {
            return;
        }

        $batches = \App\Models\StockBatch::where('product_variant_id', $variantId)
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

            \App\Models\SaleItemBatch::create([
                'sale_item_id'  => $saleItem->id,
                'stock_batch_id' => $batch->id,
                'qty'           => $ambil,
                'cost_price'    => $batch->harga_beli,
                'sell_price'    => $saleItem->price,
            ]);

            \App\Models\StockMovement::create([
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
            throw new \Exception("Stok {$variant->variant_name} tidak mencukupi di toko.");
        }
    }

    /**
     * Get order status via JSON (for REST fallback polling).
     */
    public function statusApi(Request $request, $id)
    {
        $sale = Sale::where('invoice_number', $id)->first();
        if (!$sale) {
            return response()->json(['status' => 'cancelled', 'status_reason' => 'Transaksi tidak ditemukan.']);
        }
        
        $status = 'pending';
        if ($sale->status === 'void') {
            $status = 'cancelled';
        } elseif ($sale->status === 'paid') {
            $status = 'served';
        } else {
            // Check kitchen status
            $sale->load('items.fnbDetail');
            $items = $sale->items;
            
            $preparingCount = 0;
            $readyCount = 0;
            $totalItems = $items->count();
            
            foreach ($items as $item) {
                $itemStatus = $item->kds_status; // resolved via relation
                if ($itemStatus === 'cooking' || $itemStatus === 'ready') {
                    $preparingCount++;
                }
                if ($itemStatus === 'ready' || $itemStatus === 'served') {
                    $readyCount++;
                }
            }
            
            if ($readyCount === $totalItems && $totalItems > 0) {
                $status = 'served';
            } elseif ($preparingCount > 0) {
                $status = 'preparing';
            } else {
                $status = ($sale->user_id !== null) ? 'confirmed' : 'pending';
            }
        }

        return response()->json([
            'status' => $status,
            'status_reason' => ''
        ]);
    }
}
