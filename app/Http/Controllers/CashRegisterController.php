<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Sale;
use App\Models\Store;
use App\Services\CashRegisterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class CashRegisterController extends Controller
{
    protected CashRegisterService $cashRegisterService;

    public function __construct(CashRegisterService $cashRegisterService)
    {
        $this->cashRegisterService = $cashRegisterService;
    }

    /**
     * Get active register status for current store & user.
     */
    public function status(Request $request)
    {
        $storeId = session('store_id') ?: $request->integer('store_id');
        $userId  = auth()->id();

        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'store_id diperlukan'], 422);
        }

        $store = Store::find($storeId);
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $requireRegister = (bool) $store->enable_cash_register;
        $activeRegister  = $this->cashRegisterService->getActiveRegister($storeId, $userId);

        $summary = null;
        $freshness = null;
        if ($activeRegister) {
            $summary = $this->cashRegisterService->calculateSummary($activeRegister);
            $freshness = $this->cashRegisterService->evaluateShiftFreshness($activeRegister);
        }

        return response()->json([
            'success'          => true,
            'require_register' => $requireRegister,
            'is_open'          => $activeRegister !== null,
            'is_stale_shift'   => $freshness ? $freshness['is_stale'] : false,
            'must_close'       => $freshness ? $freshness['must_close'] : false,
            'shift_info'       => $freshness,
            'register'         => $activeRegister,
            'summary'          => $summary,
        ]);
    }

    /**
     * Open a new cashier shift session.
     */
    public function open(Request $request)
    {
        $storeId = session('store_id') ?: $request->integer('store_id');
        $userId  = auth()->id();

        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'store_id diperlukan'], 422);
        }

        $validator = Validator::make($request->all(), [
            'opening_cash' => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ], [
            'opening_cash.required' => 'Modal kas awal wajib diisi.',
            'opening_cash.numeric'  => 'Modal kas awal harus berupa angka.',
            'opening_cash.min'      => 'Modal kas awal tidak boleh bernilai negatif.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $openingCash = (float) $request->opening_cash;
        $notes       = $request->notes;

        $register = $this->cashRegisterService->openRegister($storeId, $userId, $openingCash, $notes);

        return response()->json([
            'success' => true,
            'message' => 'Kasir berhasil dibuka. Selamat bertugas!',
            'data'    => $register,
        ]);
    }

    /**
     * Get live summary calculation before closing cashier.
     */
    public function summary(Request $request)
    {
        $storeId = session('store_id') ?: $request->integer('store_id');
        $userId  = auth()->id();

        $activeRegister = $this->cashRegisterService->getActiveRegister($storeId, $userId);
        if (!$activeRegister) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada sesi kasir yang sedang aktif.',
            ], 404);
        }

        $summary = $this->cashRegisterService->calculateSummary($activeRegister);

        return response()->json([
            'success'  => true,
            'register' => $activeRegister,
            'summary'  => $summary,
        ]);
    }

    /**
     * Get raw Base64 ESC/POS print payload for 1 continuous long shift report receipt.
     */
    public function printReport(Request $request)
    {
        $storeId    = session('store_id') ?: $request->integer('store_id');
        $userId     = auth()->id();
        $registerId = $request->integer('register_id');
        $paperSize  = $request->query('paper_size', '58mm');

        if ($registerId) {
            $register = CashRegister::with(['cashier', 'store'])->find($registerId);
        } else {
            $register = $this->cashRegisterService->getActiveRegister($storeId, $userId);
        }

        if (!$register) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi kasir tidak ditemukan atau belum dibuka.',
            ], 404);
        }

        $reportData = $this->cashRegisterService->getShiftReportData($register);

        $escposService = new \App\Services\Printer\EscPosReceiptService($paperSize);
        $base64 = $escposService->fullShiftReportBase64($reportData);

        return response()->json([
            'success'     => true,
            'report_data' => $reportData,
            'base64'      => $base64,
        ]);
    }

    /**
     * Record Petty Cash In / Out during active shift.
     */
    public function cashMovement(Request $request)
    {
        $storeId = session('store_id') ?: $request->integer('store_id');
        $userId  = auth()->id();

        $activeRegister = $this->cashRegisterService->getActiveRegister($storeId, $userId);
        if (!$activeRegister) {
            return response()->json([
                'success' => false,
                'message' => 'Kasir belum dibuka.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'type'                => 'required|in:cash_in,cash_out',
            'amount'              => 'required|numeric|min:1',
            'notes'               => 'required|string|max:500',
            'expense_category_id' => 'nullable|integer|exists:expense_categories,id',
        ], [
            'type.required'   => 'Tipe kas wajib dipilih.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.min'      => 'Nominal minimal Rp 1.',
            'notes.required'  => 'Keterangan wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $expenseCategoryId = $request->filled('expense_category_id') ? $request->integer('expense_category_id') : null;

        $movement = $this->cashRegisterService->addCashMovement(
            $activeRegister,
            $userId,
            $request->type,
            (float) $request->amount,
            $request->notes,
            $expenseCategoryId
        );

        return response()->json([
            'success' => true,
            'message' => ($request->type === 'cash_in' ? 'Kas Masuk' : 'Kas Keluar') . ' berhasil dicatat.',
            'data'    => $movement,
        ]);
    }

    /**
     * Close cashier shift session & reconcile cash.
     */
    public function close(Request $request)
    {
        $storeId = session('store_id') ?: $request->integer('store_id');
        $userId  = auth()->id();

        $activeRegister = $this->cashRegisterService->getActiveRegister($storeId, $userId);
        if (!$activeRegister) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada sesi kasir aktif yang dapat ditutup.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'actual_cash'   => 'required|numeric|min:0',
            'denominations' => 'nullable|array',
            'notes'         => 'nullable|string|max:1000',
        ], [
            'actual_cash.required' => 'Uang fisik aktual wajib diisi.',
            'actual_cash.numeric'  => 'Uang fisik aktual harus berupa angka.',
            'actual_cash.min'      => 'Uang fisik aktual tidak boleh bernilai negatif.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $actualCash    = (float) $request->actual_cash;
        $denominations = $request->denominations;
        $notes         = $request->notes;

        $closedRegister = $this->cashRegisterService->closeRegister(
            $activeRegister,
            $userId,
            $actualCash,
            $denominations,
            $notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Kasir berhasil ditutup dan rekonsiliasi kas telah disimpan.',
            'data'    => $closedRegister,
        ]);
    }

    /**
     * Print thermal receipt for closed cashier register.
     */
    public function printSummary(int $id)
    {
        $register = CashRegister::with(['store', 'cashier', 'closedBy'])->findOrFail($id);

        $storeId = session('store_id') ?: $register->store_id;
        $store   = Store::find($storeId) ?? $register->store;

        return view('cash_registers.receipt', compact('register', 'store'));
    }

    /**
     * Web View: Shift Reports Index
     */
    public function index(Request $request)
    {
        $storeId = session('store_id');
        $stores  = Store::orderBy('name')->get();

        return view('cash_registers.index', compact('stores', 'storeId'));
    }

    /**
     * DataTables for shift reports
     */
    public function datatables(Request $request)
    {
        $storeId   = session('store_id') ?: $request->integer('store_id');
        $fromDate  = $request->input('from_date');
        $toDate    = $request->input('to_date');
        $status    = $request->input('status');

        $query = CashRegister::with(['cashier', 'closedBy', 'store'])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($fromDate, fn($q) => $q->whereDate('opened_at', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('opened_at', '<=', $toDate))
            ->orderByDesc('opened_at');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('opened_at', fn($r) => $r->opened_at ? $r->opened_at->format('d/m/Y H:i') : '-')
            ->editColumn('closed_at', fn($r) => $r->closed_at ? $r->closed_at->format('d/m/Y H:i') : '<span class="badge bg-success">Masih Buka</span>')
            ->addColumn('cashier_name', fn($r) => $r->cashier?->name ?? '-')
            ->editColumn('opening_cash', fn($r) => 'Rp ' . number_format($r->opening_cash, 0, ',', '.'))
            ->editColumn('total_cash_sales', fn($r) => 'Rp ' . number_format($r->total_cash_sales, 0, ',', '.'))
            ->editColumn('expected_cash', fn($r) => $r->expected_cash !== null ? 'Rp ' . number_format($r->expected_cash, 0, ',', '.') : '-')
            ->editColumn('actual_cash', fn($r) => $r->actual_cash !== null ? 'Rp ' . number_format($r->actual_cash, 0, ',', '.') : '-')
            ->editColumn('cash_difference', function ($r) {
                if ($r->cash_difference === null) return '-';
                if ($r->cash_difference == 0) {
                    return '<span class="badge bg-success">Pas (Rp 0)</span>';
                } elseif ($r->cash_difference > 0) {
                    return '<span class="badge bg-info text-dark">+Rp ' . number_format($r->cash_difference, 0, ',', '.') . '</span>';
                } else {
                    return '<span class="badge bg-danger">Rp ' . number_format($r->cash_difference, 0, ',', '.') . '</span>';
                }
            })
            ->editColumn('status', function ($r) {
                return $r->status === 'open'
                    ? '<span class="badge bg-warning text-dark">Buka</span>'
                    : '<span class="badge bg-secondary">Tutup</span>';
            })
            ->addColumn('action', function ($r) {
                $detailBtn = '<a href="' . route('cash-registers.show', $r->id) . '" class="btn btn-sm btn-outline-primary" title="Detail"><i class="bi bi-eye"></i></a>';
                $printBtn  = '<a href="' . route('cash-registers.print', $r->id) . '" target="_blank" class="btn btn-sm btn-outline-secondary ms-1" title="Cetak Struk"><i class="bi bi-printer"></i></a>';
                return $detailBtn . $printBtn;
            })
            ->rawColumns(['closed_at', 'cash_difference', 'status', 'action'])
            ->make(true);
    }

    /**
     * Show detailed view of a cash register session.
     */
    public function show(int $id)
    {
        $register = CashRegister::with(['store', 'cashier', 'closedBy', 'sales.items', 'cashTransactions.user'])
            ->findOrFail($id);

        $summary = $register->status === 'closed'
            ? [
                'opening_cash'       => $register->opening_cash,
                'total_sales_amount' => $register->total_cash_sales + $register->total_non_cash_sales,
                'cash_sales'         => $register->total_cash_sales,
                'non_cash_sales'     => $register->total_non_cash_sales,
                'cash_in'            => $register->total_cash_in,
                'cash_out'           => $register->total_cash_out,
                'refund_cash'        => $register->total_refund_cash,
                'expected_cash'      => $register->expected_cash,
                'actual_cash'        => $register->actual_cash,
                'cash_difference'    => $register->cash_difference,
            ]
            : $this->cashRegisterService->calculateSummary($register);

        return view('cash_registers.show', compact('register', 'summary'));
    }
}
