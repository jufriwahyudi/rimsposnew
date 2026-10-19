<?php

namespace App\Http\Controllers;

use App\Models\Consignor;
use App\Models\ConsignorItem;
use App\Models\DailyConsignment;
use App\Models\DailyConsignmentItem;
use App\Models\ConsignmentSettlement;
use App\Models\CashTransaction;
use App\Models\CashRegister;
use App\Models\ProductVariant;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsignmentController extends Controller
{
    /**
     * Get active store ID from session.
     */
    protected function getStoreId(): int
    {
        $storeId = session('store_id');
        if (!$storeId) {
            abort(403, 'Pilih toko terlebih dahulu.');
        }
        return (int) $storeId;
    }

    /**
     * Master Data: List of Consignors & their template items.
     */
    public function index(Request $request)
    {
        $storeId = $this->getStoreId();
        $consignors = Consignor::where('store_id', $storeId)
            ->with(['items.variant.product'])
            ->withCount(['dailyConsignments'])
            ->orderBy('nama_penitip')
            ->get();

        // Product variants for mapping (e.g. Kue Basah, Kue Kering)
        $variants = ProductVariant::where('store_id', $storeId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('variant_name')
            ->get();

        return view('consignments.index', compact('consignors', 'variants'));
    }

    /**
     * Store new Consignor.
     */
    public function storeConsignor(Request $request)
    {
        $storeId = $this->getStoreId();

        $request->validate([
            'nama_penitip'   => 'required|string|max:150',
            'kode_penitip'   => 'nullable|string|max:50',
            'telepon'        => 'nullable|string|max:50',
            'alamat'         => 'nullable|string',
            'nama_bank'      => 'nullable|string|max:50',
            'nomor_rekening' => 'nullable|string|max:100',
            'tipe'           => 'required|in:daily,periodic',
        ]);

        $consignor = Consignor::create([
            'store_id'       => $storeId,
            'nama_penitip'   => $request->nama_penitip,
            'kode_penitip'   => $request->kode_penitip,
            'telepon'        => $request->telepon,
            'alamat'         => $request->alamat,
            'nama_bank'      => $request->nama_bank,
            'nomor_rekening' => $request->nomor_rekening,
            'tipe'           => $request->tipe,
            'is_active'      => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Penitip berhasil ditambahkan.',
            'data'    => $consignor
        ]);
    }

    /**
     * Update Consignor.
     */
    public function updateConsignor(Request $request, Consignor $consignor)
    {
        $storeId = $this->getStoreId();
        if ($consignor->store_id !== $storeId) {
            abort(403);
        }

        $request->validate([
            'nama_penitip'   => 'required|string|max:150',
            'kode_penitip'   => 'nullable|string|max:50',
            'telepon'        => 'nullable|string|max:50',
            'alamat'         => 'nullable|string',
            'nama_bank'      => 'nullable|string|max:50',
            'nomor_rekening' => 'nullable|string|max:100',
            'tipe'           => 'required|in:daily,periodic',
            'is_active'      => 'nullable|boolean',
        ]);

        $consignor->update([
            'nama_penitip'   => $request->nama_penitip,
            'kode_penitip'   => $request->kode_penitip,
            'telepon'        => $request->telepon,
            'alamat'         => $request->alamat,
            'nama_bank'      => $request->nama_bank,
            'nomor_rekening' => $request->nomor_rekening,
            'tipe'           => $request->tipe,
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data penitip berhasil diperbarui.'
        ]);
    }

    /**
     * Delete Consignor.
     */
    public function destroyConsignor(Consignor $consignor)
    {
        $storeId = $this->getStoreId();
        if ($consignor->store_id !== $storeId) {
            abort(403);
        }

        if ($consignor->dailyConsignments()->where('status', 'open')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Penitip tidak dapat dihapus karena masih memiliki sesi titipan aktif hari ini.'
            ], 422);
        }

        $consignor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Penitip berhasil dihapus.'
        ]);
    }

    /**
     * Add template item to Consignor.
     */
    public function storeConsignorItem(Request $request, Consignor $consignor)
    {
        $storeId = $this->getStoreId();
        if ($consignor->store_id !== $storeId) {
            abort(403);
        }

        $request->validate([
            'nama_item'          => 'required|string|max:150',
            'kode_display'       => 'nullable|string|max:50',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'harga_setor'        => 'required|numeric|min:0',
            'harga_jual'         => 'required|numeric|min:0',
        ]);

        $item = ConsignorItem::create([
            'consignor_id'       => $consignor->id,
            'product_variant_id' => $request->product_variant_id ?: null,
            'nama_item'          => $request->nama_item,
            'kode_display'       => $request->kode_display,
            'harga_setor'        => (float) $request->harga_setor,
            'harga_jual'         => (float) $request->harga_jual,
            'is_active'          => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Menu/item titipan berhasil ditambahkan.',
            'data'    => $item
        ]);
    }

    /**
     * Delete template item from Consignor.
     */
    public function destroyConsignorItem(ConsignorItem $item)
    {
        $storeId = $this->getStoreId();
        if ($item->consignor->store_id !== $storeId) {
            abort(403);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item titipan berhasil dihapus.'
        ]);
    }

    /**
     * Main Daily Consignment Page (Pagi: Terima Titipan | Sore: Rekonsiliasi & Bayar).
     */
    public function daily(Request $request)
    {
        $storeId = $this->getStoreId();
        $date = $request->get('date', Carbon::today()->toDateString());

        // Daily consignments on selected date
        $sessions = DailyConsignment::where('store_id', $storeId)
            ->whereDate('tanggal', $date)
            ->with(['consignor', 'items.consignorItem', 'settlement', 'settledBy'])
            ->orderByDesc('id')
            ->get();

        // Active consignors for daily dropdown
        $consignors = Consignor::where('store_id', $storeId)
            ->where('is_active', true)
            ->where('tipe', 'daily')
            ->with(['items.variant'])
            ->orderBy('nama_penitip')
            ->get();

        // POS variants for quick mapping
        $variants = ProductVariant::where('store_id', $storeId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('variant_name')
            ->get();

        // Summary metrics today
        $totalTitipToday = $sessions->sum('total_titip');
        $totalLakuToday = $sessions->sum('total_laku');
        $totalPayoutToday = $sessions->where('status', 'settled')->sum('total_payout');
        $totalMarginToday = $sessions->where('status', 'settled')->sum('total_kantin_margin');

        return view('consignments.daily', compact(
            'sessions',
            'consignors',
            'variants',
            'date',
            'totalTitipToday',
            'totalLakuToday',
            'totalPayoutToday',
            'totalMarginToday'
        ));
    }

    /**
     * Morning Intake: Create Daily Consignment Session.
     */
    public function storeDaily(Request $request)
    {
        $storeId = $this->getStoreId();

        $request->validate([
            'consignor_id' => 'required|exists:consignors,id',
            'tanggal'      => 'required|date',
            'items'        => 'required|array|min:1',
            'items.*.consignor_item_id' => 'required|exists:consignor_items,id',
            'items.*.qty_titip'         => 'required|integer|min:1',
            'catatan'                   => 'nullable|string|max:500',
        ]);

        $consignor = Consignor::where('id', $request->consignor_id)
            ->where('store_id', $storeId)
            ->firstOrFail();

        // Check if an open session already exists for this consignor today
        $existing = DailyConsignment::where('store_id', $storeId)
            ->where('consignor_id', $consignor->id)
            ->whereDate('tanggal', $request->tanggal)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Penitip {$consignor->nama_penitip} sudah memiliki sesi titipan terbuka hari ini. Silakan selesaikan sesi tersebut atau edit rinciannya."
            ], 422);
        }

        DB::beginTransaction();
        try {
            $totalTitip = 0;

            $daily = DailyConsignment::create([
                'store_id'     => $storeId,
                'consignor_id' => $consignor->id,
                'tanggal'      => $request->tanggal,
                'status'       => 'open',
                'catatan'      => $request->catatan,
            ]);

            foreach ($request->items as $row) {
                $qty = (int) $row['qty_titip'];
                if ($qty <= 0) continue;

                $cItem = ConsignorItem::where('id', $row['consignor_item_id'])
                    ->where('consignor_id', $consignor->id)
                    ->firstOrFail();

                DailyConsignmentItem::create([
                    'daily_consignment_id' => $daily->id,
                    'consignor_item_id'    => $cItem->id,
                    'product_variant_id'   => $cItem->product_variant_id,
                    'qty_titip'            => $qty,
                    'qty_sisa'             => 0,
                    'qty_laku'             => 0,
                    'harga_setor'          => $cItem->harga_setor,
                    'harga_jual'           => $cItem->harga_jual,
                    'subtotal_payout'      => 0,
                    'subtotal_kantin_margin'=> 0,
                ]);

                $totalTitip += $qty;
            }

            $daily->update(['total_titip' => $totalTitip]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Titipan pagi {$consignor->nama_penitip} berhasil dicatat ({$totalTitip} pcs).",
                'data'    => $daily->load('items.consignorItem')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to store daily consignment: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Evening Reconciliation & Cash Payout (Reverse Stock Settlement).
     */
    public function settleDaily(Request $request, DailyConsignment $daily)
    {
        $storeId = $this->getStoreId();
        if ($daily->store_id !== $storeId) {
            abort(403);
        }

        if ($daily->status === 'settled') {
            return response()->json(['success' => false, 'message' => 'Sesi titipan ini sudah diselesaikan sebelumnya.'], 422);
        }

        $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.id'            => 'required|exists:daily_consignment_items,id',
            'items.*.qty_sisa'      => 'required|integer|min:0',
            'payment_method'        => 'required|in:CASH,TRANSFER',
            'catatan'               => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $totalTitip = 0;
            $totalSisa = 0;
            $totalLaku = 0;
            $totalPayout = 0;
            $totalMargin = 0;

            foreach ($request->items as $row) {
                $dItem = DailyConsignmentItem::where('id', $row['id'])
                    ->where('daily_consignment_id', $daily->id)
                    ->firstOrFail();

                $qtySisa = min((int) $row['qty_sisa'], $dItem->qty_titip);
                $qtyLaku = max(0, $dItem->qty_titip - $qtySisa);
                $subPayout = $qtyLaku * $dItem->harga_setor;
                $subMargin = $qtyLaku * max(0, $dItem->harga_jual - $dItem->harga_setor);

                $dItem->update([
                    'qty_sisa'               => $qtySisa,
                    'qty_laku'               => $qtyLaku,
                    'subtotal_payout'        => $subPayout,
                    'subtotal_kantin_margin' => $subMargin,
                ]);

                $totalTitip += $dItem->qty_titip;
                $totalSisa += $qtySisa;
                $totalLaku += $qtyLaku;
                $totalPayout += $subPayout;
                $totalMargin += $subMargin;
            }

            // Find active CashRegister for this cashier session if cash payment
            $cashRegisterId = null;
            if ($request->payment_method === 'CASH') {
                $activeReg = CashRegister::where('store_id', $storeId)
                    ->where('cashier_id', auth()->id())
                    ->where('status', 'open')
                    ->latest('opened_at')
                    ->first();
                $cashRegisterId = $activeReg?->id;
            }

            // Generate Settlement Number
            $settlementNumber = 'SETL-' . date('Ymd') . '-' . str_pad($daily->id, 4, '0', STR_PAD_LEFT);

            // Create Settlement Record
            $settlement = ConsignmentSettlement::create([
                'store_id'             => $storeId,
                'daily_consignment_id' => $daily->id,
                'consignor_id'         => $daily->consignor_id,
                'cash_register_id'     => $cashRegisterId,
                'user_id'              => auth()->id(),
                'settlement_number'    => $settlementNumber,
                'settlement_date'      => now()->toDateString(),
                'total_amount'         => $totalPayout,
                'payment_method'       => $request->payment_method,
                'catatan'              => $request->catatan,
            ]);

            // If CASH and active CashRegister exists, record CashTransaction to decrement drawer cash
            if ($totalPayout > 0 && $request->payment_method === 'CASH') {
                CashTransaction::create([
                    'store_id'         => $storeId,
                    'ref_type'         => 'ConsignmentSettlement',
                    'ref_id'           => $settlement->id,
                    'transaction_type' => 'cash_out',
                    'payment_method'   => 'cash',
                    'amount'           => $totalPayout,
                    'direction'        => 'out',
                    'transaction_date' => now(),
                    'user_id'          => auth()->id(),
                    'cash_register_id' => $cashRegisterId,
                    'notes'            => "Bayar Titipan: {$daily->consignor->nama_penitip} (#{$settlementNumber})",
                ]);
            }

            // Update Daily Consignment status
            $daily->update([
                'status'              => 'settled',
                'total_titip'         => $totalTitip,
                'total_sisa'          => $totalSisa,
                'total_laku'          => $totalLaku,
                'total_payout'        => $totalPayout,
                'total_kantin_margin' => $totalMargin,
                'settled_at'          => now(),
                'settled_by_user_id'  => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Rekonsiliasi selesai! Total dibayar ke {$daily->consignor->nama_penitip}: Rp " . number_format($totalPayout, 0, ',', '.'),
                'data'    => [
                    'settlement_id'     => $settlement->id,
                    'settlement_number' => $settlementNumber,
                    'total_payout'      => $totalPayout,
                    'print_url'         => route('consignments.receipt', $settlement->id),
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to settle daily consignment #{$daily->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal rekonsiliasi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Thermal Receipt View for Consignment Settlement.
     */
    public function printReceipt(ConsignmentSettlement $settlement)
    {
        $settlement->load([
            'consignor',
            'dailyConsignment.items.consignorItem',
            'user',
            'store'
        ]);

        return view('consignments.receipt', compact('settlement'));
    }

    /**
     * Settlement History & Reports.
     */
    public function settlements(Request $request)
    {
        $storeId = $this->getStoreId();
        $dateFrom = $request->get('date_from', Carbon::today()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::today()->toDateString());
        $consignorId = $request->get('consignor_id');

        $query = ConsignmentSettlement::where('store_id', $storeId)
            ->whereBetween('settlement_date', [$dateFrom, $dateTo])
            ->with(['consignor', 'user', 'dailyConsignment']);

        if ($consignorId) {
            $query->where('consignor_id', $consignorId);
        }

        $settlements = $query->orderByDesc('id')->get();
        $consignors = Consignor::where('store_id', $storeId)->orderBy('nama_penitip')->get();

        $totalPayout = $settlements->sum('total_amount');

        return view('consignments.settlements', compact(
            'settlements',
            'consignors',
            'dateFrom',
            'dateTo',
            'consignorId',
            'totalPayout'
        ));
    }
}
