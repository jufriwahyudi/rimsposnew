<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Services\JournalEntryService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GoodsReceiptController extends Controller
{
    public function create(PurchaseOrder $po)
    {
        $po->load('items.variant.product', 'goodsReceipts.items.purchaseOrderItem.variant.product', 'goodsReceipts.receiver');
        // dd(json_encode($po->items, JSON_PRETTY_PRINT));
        return view('goods_receipts.create', compact('po'));
    }
    /* =========================
     * CREATE GOODS RECEIPT
     * ========================= */
    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'receipt_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.qty_received' => 'nullable|numeric|min:0',
        ]);


        DB::transaction(function () use ($request) {

            $po = PurchaseOrder::with('items')->findOrFail($request->purchase_order_id);

            if (!in_array($po->status, ['APPROVED', 'PARTIAL_RECEIVED'])) {
                abort(400, 'PO belum siap diterima');
            }

            $receipt = GoodsReceipt::create([
                'purchase_order_id' => $po->id,
                'receipt_number' => $this->generateReceiptNumber(),
                'receipt_date' => $request->receipt_date,
                'received_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                // continue jika qty_received kosong atau 0
                if (empty($item['qty_received']) || $item['qty_received'] <= 0) {
                    continue;
                }
                $poItem = PurchaseOrderItem::lockForUpdate()->findOrFail($item['purchase_item_id']);

                // Safety check
                if ($item['qty_received'] > ($poItem->qty_order - $poItem->qty_received)) {
                    throw new \Exception('Qty diterima melebihi sisa PO');
                }

                GoodsReceiptItem::create([
                    'goods_receipt_id' => $receipt->id,
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => $item['qty_received'],
                ]);

                // Update qty_received di PO item
                $poItem->increment('qty_received', $item['qty_received']);

                // 🔴 NANTI: tambah stok
                StockService::receiveFromPurchase(
                    $poItem->product_variant_id,
                    $poItem->id,
                    'warehouse',
                    $request->receipt_date,
                    $item['qty_received'],
                    $poItem->price,
                    $receipt->id
                );
            }

            // Update status PO
            $po->load('items');
            $fullyReceived = $po->items->every(
                fn($item) => $item->qty_received >= $item->qty_order
            );

            \Log::info(
                'Fully received: ' . ($fullyReceived ? 'yes' : 'no') .
                ' Jumlah Receipt Item: ' . $po->items->sum('qty_received') .
                ' Jumlah Order Item: ' . $po->items->sum('qty_order')
            );

            $po->update([
                'status' => $fullyReceived ? 'RECEIVED' : 'PARTIAL_RECEIVED'
            ]);

            // Jurnal pengakuan barang diterima
            $journalService = new JournalEntryService();
            $voucher = $journalService->create(
                [
                    'tanggal' => $request->receipt_date,
                    'uraian' => 'Penerimaan Barang PO #' . $po->po_number,
                    'jns_trx' => 10,
                    'ref_tagihan' => $receipt->id,
                    'divisi' => 8,
                ],
                [
                    [
                        'kode_akun' => '11.04.14', // Persediaan Koperasi - Gudang
                        'amount' => $receipt->items->sum(fn($item) => $item->qty_received * $item->purchaseOrderItem->price),
                        'type' => 'debet',
                    ],
                    [
                        'kode_akun' => '11.04.16', // Persediaan Koperasi Dalam Perjalanan
                        'amount' => $receipt->items->sum(fn($item) => $item->qty_received * $item->purchaseOrderItem->price),
                        'type' => 'kredit',
                    ],
                ]
            );
            $receipt->update(['nojurnal' => $voucher->id]);
        });

        return back()->with('success', 'Barang berhasil diterima');
    }
    /* =========================
     * DELETE GOODS RECEIPT
     * ========================= */
    public function destroy(GoodsReceipt $gr)
    {
        try {
            if (!StockService::canRollbackGoodsReceipt($gr)) {
                return back()->with(
                    'error',
                    StockService::rollbackBlockReason($gr)
                    ?? 'Goods Receipt tidak dapat dihapus'
                );
            }
            DB::transaction(function () use ($gr) {

                // Lock GR items
                $gr->load('items.purchaseOrderItem');

                // 1️⃣ Revert qty_received PO item
                foreach ($gr->items as $item) {
                    $item->purchaseOrderItem()
                        ->lockForUpdate()
                        ->decrement('qty_received', $item->qty_received);
                }

                // 2️⃣ Revert stock (movement + batch)
                foreach ($gr->stockMovements as $movement) {
                    $movement->delete();
                    optional($movement->batch)->delete();
                }

                // 3️⃣ Hapus jurnal
                if ($gr->nojurnal) {
                    DB::connection('financedb')
                        ->table('jurnal')
                        ->where('ref', $gr->nojurnal)
                        ->delete();

                    DB::connection('financedb')
                        ->table('voucher')
                        ->where('Id', $gr->nojurnal)
                        ->delete();
                }
                // Update status PO
                $po = PurchaseOrder::lockForUpdate()->find($gr->purchase_order_id);

                $hasReceived = $po->items()->where('qty_received', '>', 0)->exists();
                $fullyReceived = !$po->items()
                    ->whereColumn('qty_received', '<', 'qty_order')
                    ->exists();

                $po->update([
                    'status' => $fullyReceived
                        ? 'RECEIVED'
                        : ($hasReceived ? 'PARTIAL_RECEIVED' : 'APPROVED')
                ]);

                $gr->items()->delete();
                $gr->delete();
            });

            return back()->with('success', 'Goods Receipt berhasil dihapus dan stok berhasil direvert');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus Goods Receipt. ' . $e->getMessage());
        }
    }


    private function generateReceiptNumber()
    {
        return 'GR-' . date('Ymd') . '-' . Str::upper(Str::random(4));
    }


    //  DOWNLOAD BARCODES AS ZIP
    public function downloadBarcodes(GoodsReceipt $gr)
    {
        $gr->load('items.purchaseOrderItem.variant.product', 'items.purchaseOrderItem.variant.barcodeActive');

        if ($gr->items->isEmpty()) {
            return back()->with('error', 'Tidak ada item dalam goods receipt ini');
        }

        $dns = new \Milon\Barcode\DNS1D();
        $zipFileName = 'barcodes-' . $gr->receipt_number . '.zip';
        $tempDir = storage_path('app/temp_barcodes_' . time());

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            foreach ($gr->items as $item) {
                $variant = $item->purchaseOrderItem->variant;
                $productName = $variant->product->nama_produk;
                $sku = $variant->sku;
                $barcode = $variant->barcodeActive?->barcode ?? $variant->barcode ?? $sku;

                if (empty($barcode)) {
                    continue;
                }

                // Generate barcode image
                $barcodeBase64 = $dns->getBarcodePNG($barcode, 'C128', 2, 80);
                $barcodeImage = imagecreatefromstring(base64_decode($barcodeBase64));

                if (!$barcodeImage) {
                    continue;
                }

                // Create label with product info
                $width = 320;
                $height = 240;
                $canvas = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($canvas, 255, 255, 255);
                $black = imagecolorallocate($canvas, 0, 0, 0);
                imagefill($canvas, 0, 0, $white);

                // Copy barcode ke canvas
                $bw = imagesx($barcodeImage);
                $bh = imagesy($barcodeImage);
                $posX = ($width - $bw) / 2;
                $posY = 10;
                imagecopy($canvas, $barcodeImage, $posX, $posY, 0, 0, $bw, $bh);

                // Tambahkan text info di bawah barcode
                $productNameShort = mb_strimwidth($productName, 0, 30, '…');
                $font = 12;

                // --- SKU ---
                $skuText = $sku;
                $textWidth = imagefontwidth($font) * strlen($skuText);
                $x = ($width - $textWidth) / 2;
                imagestring($canvas, $font, $x, $posY + $bh + 5, $skuText, $black);

                // --- NAMA PRODUK ---
                $textWidth = imagefontwidth($font) * strlen($productNameShort);
                $x = ($width - $textWidth) / 2;
                imagestring($canvas, $font, $x, $posY + $bh + 25, $productNameShort, $black);

                // --- QTY ---
                // $qtyText = 'Qty : ' . number_format($item->qty_received);
                // $textWidth = imagefontwidth($font) * strlen($qtyText);
                // $x = ($width - $textWidth) / 2;
                // imagestring($canvas, $font, $x, $posY + $bh + 45, $qtyText, $black);

                // Save as PNG
                $fileName = preg_replace('/[^a-zA-Z0-9_-]/', '_',  (int) $item->qty_received . '_' . $productNameShort . '_' . $sku) . '.png';
                imagepng($canvas, $tempDir . '/' . $fileName);

                // Clean up
                imagedestroy($barcodeImage);
                imagedestroy($canvas);
            }

            // Membuat ZIP
            $zip = new \ZipArchive();
            $zipPath = storage_path('app/' . $zipFileName);

            if (file_exists($zipPath)) {
                unlink($zipPath);
            }

            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                $files = glob($tempDir . '/*');
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }

            // Clean up temp directory
            array_map('unlink', glob($tempDir . '/*'));
            rmdir($tempDir);

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Clean up on error
            if (is_dir($tempDir)) {
                array_map('unlink', glob($tempDir . '/*'));
                rmdir($tempDir);
            }
            return back()->with('error', 'Gagal generate barcode: ' . $e->getMessage());
        }
    }
}
