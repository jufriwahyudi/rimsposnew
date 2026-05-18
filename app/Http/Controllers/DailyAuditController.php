<?php

namespace App\Http\Controllers;

use App\Models\DailyAudit;
use App\Models\DailyAuditDetail;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyAuditController extends Controller
{
    public function index()
    {
        $audits = DailyAudit::orderByDesc('audit_date')->paginate(15);
        return view('audits.index', compact('audits'));
    }

    public function show(DailyAudit $dailyAudit)
    {
        $dailyAudit->load('details');
        return view('audits.show', compact('dailyAudit'));
    }

    public function create()
    {
        return view('audits.create');
    }

    public function store(Request $request)
    {
        $date = Carbon::parse($request->audit_date)->startOfDay();

        DB::transaction(function () use ($date) {

            // Contoh perhitungan (sesuaikan dengan struktur tabelmu)
            $totalSales = DB::table('sales')
                ->whereDate('created_at', $date)
                ->where('status', 'paid')
                ->sum('grand_total');

            $cashIn = DB::table('cash_transactions')
                ->whereDate('created_at', $date)
                ->where('direction', 'in')
                ->sum('amount');

            $cashOut = DB::table('cash_transactions')
                ->whereDate('created_at', $date)
                ->where('direction', 'out')
                ->sum('amount');

            $cashDiff = $totalSales - ($cashIn - $cashOut);

            $status = $cashDiff == 0 ? 'OK' : 'ERROR';

            $audit = DailyAudit::create([
                'audit_date' => $date,
                'total_sales' => $totalSales,
                'total_cash_in' => $cashIn,
                'total_cash_out' => $cashOut,
                'cash_difference' => $cashDiff,
                'status' => $status,
                'created_by' => auth()->id()
            ]);

            if ($cashDiff != 0) {
                DailyAuditDetail::create([
                    'daily_audit_id' => $audit->id,
                    'reference_type' => 'CASH',
                    'issue_type' => 'CASH_MISMATCH',
                    'description' => 'Total penjualan tidak sama dengan uang masuk',
                    'expected_value' => $totalSales,
                    'actual_value' => $cashIn - $cashOut
                ]);
            }

            // =====================
            // AUDIT STOK BATCH
            // =====================
            $batches = StockBatch::all();

            $stockDiffValue = 0;
            $hasError = false;

            foreach ($batches as $batch) {

                $movementQty = StockMovement::where('stock_batch_id', $batch->id)
                    ->whereDate('created_at', $date)
                    ->selectRaw("
                        SUM(
                            CASE
                                WHEN direction = 'IN' THEN qty
                                ELSE -qty
                            END
                        ) as total
                    ")
                    ->value('total');

                $expectedQty = $batch->qty_awal + $movementQty;
                $actualQty = $batch->qty_sisa;

                if ($expectedQty != $actualQty) {

                    $diffQty = $actualQty - $expectedQty;
                    $diffValue = $diffQty * $batch->harga_beli;

                    $stockDiffValue += abs($diffValue);
                    $hasError = true;

                    DailyAuditDetail::create([
                        'daily_audit_id' => $audit->id,
                        'reference_type' => 'BATCH',
                        'reference_id' => $batch->id,
                        'issue_type' => 'STOCK_MISMATCH',
                        'description' => "Selisih stok batch #{$batch->id}",
                        'expected_value' => $expectedQty,
                        'actual_value' => $actualQty,
                    ]);
                }
            }

            $audit->update([
                'stock_difference_value' => $stockDiffValue,
                'status' => $hasError ? 'ERROR' : $audit->status
            ]);
        });

        return redirect()->route('audits.index')->with('success', 'Audit harian berhasil dibuat');
    }
}
