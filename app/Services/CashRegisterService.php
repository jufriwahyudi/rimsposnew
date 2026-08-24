<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Rekening;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashRegisterService
{
    /**
     * Get the active open register for a given store and optional user.
     */
    public function getActiveRegister(int $storeId, ?int $userId = null): ?CashRegister
    {
        $query = CashRegister::with(['cashier', 'store'])
            ->where('store_id', $storeId)
            ->where('status', 'open');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->latest('opened_at')->first();
    }

    /**
     * Check if a store requires cash register and if so, whether the user has an open register.
     */
    public function isRegisterOpen(int $storeId, ?int $userId = null): bool
    {
        $store = Store::find($storeId);
        if (!$store || !$store->enable_cash_register) {
            return true; // Not required, so treat as open
        }

        return $this->getActiveRegister($storeId, $userId) !== null;
    }

    /**
     * Open a new cashier shift session.
     */
    public function openRegister(int $storeId, int $userId, float $openingCash, ?string $notes = null): CashRegister
    {
        // Check if there is already an open register for this cashier in this store
        $existing = $this->getActiveRegister($storeId, $userId);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($storeId, $userId, $openingCash, $notes) {
            $register = CashRegister::create([
                'store_id'     => $storeId,
                'user_id'      => $userId,
                'opened_at'    => Carbon::now(),
                'opening_cash' => $openingCash,
                'notes'        => $notes,
                'status'       => 'open',
            ]);

            return $register;
        });
    }

    /**
     * Calculate live financial summary for a cashier shift session.
     */
    public function calculateSummary(CashRegister $cashRegister): array
    {
        $regId = $cashRegister->id;

        // 1. Sales linked to this register
        $sales = Sale::where('cash_register_id', $regId)
            ->whereNotIn('status', ['cancelled', 'void'])
            ->get();

        $totalSalesCount = $sales->count();
        $totalSalesAmount = (float) $sales->sum('grand_total');

        // Cash sales amount from CashTransactions linked to this register
        $cashSales = (float) CashTransaction::where('cash_register_id', $regId)
            ->where('transaction_type', 'sale')
            ->where('payment_method', 'cash')
            ->where('direction', 'in')
            ->sum('amount');

        // Non-cash sales (transfer / QRIS) from CashTransactions
        $nonCashSales = (float) CashTransaction::where('cash_register_id', $regId)
            ->where('transaction_type', 'sale')
            ->where('payment_method', '!=', 'cash')
            ->where('direction', 'in')
            ->sum('amount');

        // Total Debt (Hutang)
        $debtSales = (float) $sales->where('payment_status', 'hutang')->sum('grand_total');

        // 2. Cash In (Petty cash / Kas Masuk manual non-penjualan)
        $cashIn = (float) CashTransaction::where('cash_register_id', $regId)
            ->where('direction', 'in')
            ->whereNotIn('transaction_type', ['sale'])
            ->sum('amount');

        // 3. Cash Out (Petty cash / Biaya Kasir / Kas Keluar manual non-refund)
        $cashOut = (float) CashTransaction::where('cash_register_id', $regId)
            ->where('direction', 'out')
            ->whereNotIn('transaction_type', ['refund'])
            ->sum('amount');

        // 4. Cash Refund
        $refundCash = (float) CashTransaction::where('cash_register_id', $regId)
            ->where('direction', 'out')
            ->where('transaction_type', 'refund')
            ->sum('amount');

        // Expected cash in drawer = Kas Awal + Penjualan Tunai + Kas Masuk - Kas Keluar - Refund Tunai
        $expectedCash = $cashRegister->opening_cash + $cashSales + $cashIn - $cashOut - $refundCash;

        return [
            'opening_cash'         => (float) $cashRegister->opening_cash,
            'total_sales_count'    => $totalSalesCount,
            'total_sales_amount'   => $totalSalesAmount,
            'cash_sales'           => $cashSales,
            'non_cash_sales'       => $nonCashSales,
            'debt_sales'           => $debtSales,
            'cash_in'              => $cashIn,
            'cash_out'             => $cashOut,
            'refund_cash'          => $refundCash,
            'expected_cash'        => $expectedCash,
            'opened_at'            => $cashRegister->opened_at,
            'cashier_name'         => $cashRegister->cashier?->name ?? 'Kasir',
            'store_name'           => $cashRegister->store?->name ?? 'Toko',
        ];
    }

    /**
     * Add manual cash in / out movement (Petty cash) during active shift.
     * If $expenseCategoryId is provided for cash_out, an Expense & ExpensePayment record will also be created.
     */
    public function addCashMovement(
        CashRegister $cashRegister,
        int $userId,
        string $type,
        float $amount,
        ?string $notes = null,
        ?int $expenseCategoryId = null
    ): CashTransaction {
        return DB::transaction(function () use ($cashRegister, $userId, $type, $amount, $notes, $expenseCategoryId) {
            $direction = ($type === 'cash_in') ? 'in' : 'out';
            $refType = 'CashRegister';
            $refId = $cashRegister->id;

            // Jika tipe kas_keluar dan memiliki kategori beban, sinkronkan ke modul Expense (Beban Operasional)
            if ($type === 'cash_out' && $expenseCategoryId) {
                $expense = Expense::create([
                    'store_id'            => $cashRegister->store_id,
                    'expense_category_id' => $expenseCategoryId,
                    'transaction_date'    => Carbon::now()->toDateString(),
                    'amount'              => $amount,
                    'paid_amount'         => $amount,
                    'payment_status'      => 'lunas',
                    'payment_method'      => 'cash',
                    'description'         => $notes ?: 'Pengeluaran Kasir (Petty Cash)',
                    'notes'               => 'Dikeluarkan dari kasir (Shift #' . $cashRegister->id . ')',
                    'user_id'             => $userId,
                ]);

                ExpensePayment::create([
                    'expense_id'     => $expense->id,
                    'payment_date'   => Carbon::now()->toDateString(),
                    'amount'         => $amount,
                    'payment_method' => 'cash',
                    'notes'          => 'Pembayaran Kasir Petty Cash',
                    'user_id'        => $userId,
                ]);

                $refType = 'Expense';
                $refId = $expense->id;
            }

            return CashTransaction::create([
                'store_id'         => $cashRegister->store_id,
                'ref_type'         => $refType,
                'ref_id'           => $refId,
                'transaction_type' => $type,
                'payment_method'   => 'cash',
                'account_code'     => 0,
                'amount'           => $amount,
                'direction'        => $direction,
                'transaction_date' => Carbon::now(),
                'user_id'          => $userId,
                'cash_register_id' => $cashRegister->id,
                'notes'            => $notes ?: ($type === 'cash_in' ? 'Kas Masuk Kasir' : 'Kas Keluar Kasir'),
            ]);
        });
    }

    /**
     * Close the cashier register session and perform cash reconciliation.
     */
    public function closeRegister(
        CashRegister $cashRegister,
        int $closedByUserId,
        float $actualCash,
        ?array $denominations = null,
        ?string $notes = null
    ): CashRegister {
        return DB::transaction(function () use ($cashRegister, $closedByUserId, $actualCash, $denominations, $notes) {
            $summary = $this->calculateSummary($cashRegister);
            $expectedCash = $summary['expected_cash'];
            $difference = $actualCash - $expectedCash;

            $cashRegister->update([
                'closed_by'            => $closedByUserId,
                'closed_at'            => Carbon::now(),
                'total_cash_sales'     => $summary['cash_sales'],
                'total_non_cash_sales' => $summary['non_cash_sales'],
                'total_refund_cash'    => $summary['refund_cash'],
                'total_cash_in'        => $summary['cash_in'],
                'total_cash_out'       => $summary['cash_out'],
                'expected_cash'        => $expectedCash,
                'actual_cash'          => $actualCash,
                'cash_difference'      => $difference,
                'denominations'        => $denominations,
                'notes'                => $notes,
                'status'               => 'closed',
            ]);

            return $cashRegister->fresh(['cashier', 'closedBy', 'store']);
        });
    }

    /**
     * Evaluate shift freshness to detect stale / unclosed shifts from previous days.
     */
    public function evaluateShiftFreshness(CashRegister $cashRegister): array
    {
        $openedAt = Carbon::parse($cashRegister->opened_at);
        $now = Carbon::now();
        $durationHours = round($openedAt->diffInMinutes($now, true) / 60, 1);
        $isDifferentDay = !$openedAt->isSameDay($now);

        // Stale if opened on a different calendar day AND duration >= 14 hours, or duration >= 20 hours
        $isStale = ($isDifferentDay && $durationHours >= 14) || ($durationHours >= 20);

        // Hard close requirement if duration >= 24 hours
        $mustClose = $durationHours >= 24;

        $formattedOpenedAt = $openedAt->translatedFormat('d M Y, H:i');

        $message = null;
        if ($isStale) {
            if ($mustClose) {
                $message = "Shift kasir ini sudah aktif selama {$durationHours} jam (sejak {$formattedOpenedAt}). Anda wajib menutup shift terlebih dahulu sebelum melanjutkan.";
            } else {
                $message = "Shift kasir ini dibuka pada {$formattedOpenedAt} ({$durationHours} jam lalu). Apakah Anda ingin menutup shift kemarin terlebih dahulu?";
            }
        }

        return [
            'is_stale'            => $isStale,
            'must_close'          => $mustClose,
            'duration_hours'      => $durationHours,
            'opened_at'           => $openedAt->toISOString(),
            'opened_at_formatted' => $formattedOpenedAt,
            'message'             => $message,
        ];
    }

    /**
     * Get complete shift report data for printing (financial summary + sold menu items).
     */
    public function getShiftReportData(CashRegister $cashRegister): array
    {
        $summary = $this->calculateSummary($cashRegister);
        $regId = $cashRegister->id;

        // Non-cash payment method breakdown (e.g. QRIS DKRIUK, BCA, dll)
        $nonCashBreakdown = CashTransaction::with('rekening')
            ->where('cash_register_id', $regId)
            ->where('transaction_type', 'sale')
            ->where('payment_method', '!=', 'cash')
            ->where('direction', 'in')
            ->select('account_code', 'payment_method', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('account_code', 'payment_method')
            ->get()
            ->map(function ($row) {
                $bankName = $row->rekening?->bank_rek ?? $row->rekening?->bank_name ?? '';
                $accName  = $row->rekening?->nama_rek ?? $row->rekening?->account_name ?? '';
                $name     = trim($bankName . ' ' . $accName);
                if (empty($name)) {
                    $name = strtoupper($row->payment_method);
                }
                return [
                    'name'   => $name,
                    'amount' => (float) $row->total_amount,
                ];
            })
            ->toArray();

        // Transaction counts
        $completedSalesCount = Sale::where('cash_register_id', $regId)
            ->whereIn('status', ['completed', 'paid'])
            ->count();

        $unpaidSalesCount = Sale::where('cash_register_id', $regId)
            ->whereIn('status', ['hold', 'pending', 'unpaid'])
            ->count();

        // Menu sales list (items sold in this shift)
        $saleIds = Sale::where('cash_register_id', $regId)
            ->whereNotIn('status', ['cancelled', 'void'])
            ->pluck('id');

        $menuSales = SaleItem::whereIn('sale_id', $saleIds)
            ->select('product_name', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'qty'  => (float) $item->total_qty,
                ];
            })
            ->toArray();

        $totalMenuQty = array_sum(array_column($menuSales, 'qty'));

        $store = $cashRegister->store;

        return [
            'store' => [
                'name'    => $store?->name ?? 'Toko',
                'address' => $store?->address ?? '',
                'phone'   => $store?->phone ?? '',
            ],
            'shift' => [
                'id'           => $cashRegister->id,
                'cashier_name' => $cashRegister->cashier?->name ?? 'Kasir',
                'opened_at'    => $cashRegister->opened_at ? Carbon::parse($cashRegister->opened_at)->translatedFormat('d M Y, H:i') : '-',
                'closed_at'    => $cashRegister->closed_at ? Carbon::parse($cashRegister->closed_at)->translatedFormat('d M Y, H:i') : Carbon::now()->translatedFormat('d M Y, H:i'),
                'is_closed'    => $cashRegister->status === 'closed',
            ],
            'financial' => [
                'opening_cash'        => (float) $cashRegister->opening_cash,
                'cash_sales'          => (float) $summary['cash_sales'],
                'non_cash_sales'      => (float) $summary['non_cash_sales'],
                'non_cash_breakdown'  => $nonCashBreakdown,
                'cash_in'             => (float) $summary['cash_in'],
                'cash_out'            => (float) $summary['cash_out'],
                'refund_cash'         => (float) $summary['refund_cash'],
                'total_received'      => (float) ($summary['cash_sales'] + $summary['non_cash_sales']),
                'final_cash_balance'  => (float) $summary['expected_cash'],
                'completed_sales'     => $completedSalesCount,
                'unpaid_sales'        => $unpaidSalesCount,
            ],
            'menu_sales' => [
                'items'     => $menuSales,
                'total_qty' => $totalMenuQty,
            ],
        ];
    }
}
