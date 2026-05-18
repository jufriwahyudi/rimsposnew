<?php

namespace App\Http\Controllers;

use App\Models\StockOpnamePeriod;
use Illuminate\Http\Request;

class StockOpnamePeriodController extends Controller
{
    public function index()
    {
        $periods = StockOpnamePeriod::latest('period_date')->paginate(15);
        return view('stock-opname-periods.index', compact('periods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'period_date' => 'required|date|unique:stock_opname_periods,period_date',
            'description' => 'nullable|string|max:255'
        ]);

        StockOpnamePeriod::create([
            'code' => 'OPN-' . date('Ym', strtotime($request->period_date)),
            'period_date' => $request->period_date,
            'description' => $request->description,
            'created_by' => auth()->id()
        ]);

        return redirect()->route('stock-opname-periods.index')->with('success', 'Stock Opname Periode berhasil dibuat.');
    }

    public function show(StockOpnamePeriod $stockOpnamePeriod)
    {
        $opnames = $stockOpnamePeriod->opnames()->get();
        return view('stock-opname-periods.show', compact('stockOpnamePeriod', 'opnames'));
    }

    public function close(StockOpnamePeriod $stockOpnamePeriod)
    {
        $stockOpnamePeriod->update([
            'status' => 'CLOSED',
            'closed_by' => auth()->id(),
            'closed_at' => now()
        ]);

        return back();
    }
    public function open(StockOpnamePeriod $stockOpnamePeriod)
    {
        // cek apakah ada periode lain yang sudah terbuka sebelumnya
        $existingOpenPeriod = StockOpnamePeriod::where('status', 'OPEN')->first();
        if ($existingOpenPeriod) {
            return back()->with(['error' => 'Tidak dapat membuka periode. Periode lain masih dalam status OPEN.']);
        }
        $stockOpnamePeriod->update([
            'status' => 'OPEN',
            'closed_by' => null,
            'closed_at' => null
        ]);

        return back();
    }
}
