<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Rekening;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers/partners.
     */
    public function index()
    {
        $customers = Customer::orderBy('name')->get();
        return view('customers.index', compact('customers'));
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'store_id' => session('store_id'),
            'name'     => $request->name,
            'phone'    => $request->phone,
            'alamat'   => $request->alamat,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mitra/Pelanggan berhasil ditambahkan.',
            'data'    => $customer
        ]);
    }

    /**
     * Show the specified customer/partner's profile, sales history, and active debts.
     */
    public function show(Customer $customer)
    {
        $sales = Sale::where('customer_id', $customer->id)
            ->orderByDesc('sale_date')
            ->get();

        $debts = Sale::where('customer_id', $customer->id)
            ->where('payment_status', 'hutang')
            ->orderBy('sale_date')
            ->get();

        $totalDebt = $debts->sum(fn($s) => $s->grand_total - $s->paid_amount);

        return view('customers.show', compact('customer', 'sales', 'debts', 'totalDebt'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
        ]);

        $customer->update([
            'name'   => $request->name,
            'phone'  => $request->phone,
            'alamat' => $request->alamat,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mitra/Pelanggan berhasil diperbarui.'
        ]);
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Mitra tidak bisa dihapus karena sudah memiliki riwayat transaksi.'
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mitra/Pelanggan berhasil dihapus.'
        ]);
    }

    /**
     * Display the collective debt settlement page.
     * AJAX requests fetch customer's unpaid debts.
     */
    public function debtsIndex(Request $request)
    {
        if ($request->ajax()) {
            $customerId = $request->integer('customer_id');
            $debts = Sale::where('customer_id', $customerId)
                ->where('payment_status', 'hutang')
                ->orderBy('sale_date')
                ->get();

            $formattedDebts = $debts->map(function ($sale) {
                return [
                   'id' => $sale->id,
                   'invoice_number' => $sale->invoice_number,
                   'sale_date' => $sale->sale_date->format('d-m-Y H:i'),
                   'grand_total' => $sale->grand_total,
                   'paid_amount' => $sale->paid_amount,
                   'remaining' => $sale->grand_total - $sale->paid_amount,
                ];
            });

            return response()->json([
                'debts' => $formattedDebts,
                'total_debt' => $debts->sum(fn($s) => $s->grand_total - $s->paid_amount),
            ]);
        }

        $customers = Customer::orderBy('name')->get();
        $akunkas = Rekening::where('store_id', session('store_id'))->get();
        $akunkasir = 0; // Default cash account code representation

        return view('customers.debts', compact('customers', 'akunkas', 'akunkasir'));
    }

    /**
     * Process collective debt settlement using FIFO logic.
     */
    public function payCollective(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,transfer',
            'akun_bank'      => 'required_if:payment_method,transfer',
            'bukti_bayar'    => 'nullable|image|max:2048',
        ]);

        $customerId    = $request->customer_id;
        $paymentAmount = (float) $request->amount;
        $paymentMethod = $request->payment_method;
        $akunBank      = $request->akun_bank;

        $buktiBayarPath = null;
        if ($request->hasFile('bukti_bayar') && $request->file('bukti_bayar')->isValid()) {
            $buktiBayarPath = $request->file('bukti_bayar')->store('bukti_bayar', 'public');
        }

        try {
            $paidSales = DB::transaction(function () use ($customerId, $paymentAmount, $paymentMethod, $akunBank, $buktiBayarPath) {
                $debts = Sale::where('customer_id', $customerId)
                    ->where('payment_status', 'hutang')
                    ->orderBy('sale_date', 'asc')
                    ->get();

                $remainingPayment = $paymentAmount;
                $processedSales = [];

                foreach ($debts as $sale) {
                    if ($remainingPayment <= 0) {
                        break;
                    }

                    $alreadyPaid = (float) $sale->paid_amount;
                    $remainingDebt = $sale->grand_total - $alreadyPaid;

                    if ($remainingDebt <= 0) {
                        continue;
                    }

                    $payForThisInvoice = min($remainingPayment, $remainingDebt);

                    CashTransaction::create([
                        'store_id'         => session('store_id'),
                        'ref_type'         => 'SaleDebt',
                        'ref_id'           => $sale->id,
                        'transaction_type' => 'sale',
                        'payment_method'   => $paymentMethod,
                        'account_code'     => $paymentMethod === 'transfer' ? ($akunBank ?? 0) : 0,
                        'amount'           => $payForThisInvoice,
                        'direction'        => 'in',
                        'transaction_date' => now(),
                        'user_id'          => auth()->id(),
                        'notes'            => 'Pelunasan Kolektif Hutang #' . $sale->invoice_number,
                        'bukti_bayar'      => $buktiBayarPath,
                    ]);

                    $newPaidTotal = $alreadyPaid + $payForThisInvoice;
                    $isLunas = ($sale->grand_total - $newPaidTotal) <= 0.01;

                    $sale->update([
                        'paid_amount'    => $isLunas ? $sale->grand_total : $newPaidTotal,
                        'payment_status' => $isLunas ? 'lunas' : 'hutang',
                    ]);

                    $processedSales[] = [
                        'invoice' => $sale->invoice_number,
                        'amount'  => $payForThisInvoice,
                        'status'  => $isLunas ? 'Lunas' : 'Cicilan',
                    ];

                    $remainingPayment -= $payForThisInvoice;
                }

                return $processedSales;
            });

            if (empty($paidSales)) {
                return redirect()->back()->with('error', 'Tidak ditemukan tagihan hutang aktif untuk mitra ini.');
            }

            $msg = "Pelunasan berhasil diproses:<br>";
            foreach ($paidSales as $p) {
                $msg .= "- <b>{$p['invoice']}</b>: Rp " . number_format($p['amount'], 0, ',', '.') . " ({$p['status']})<br>";
            }

            return redirect()->back()->with('success', $msg);

        } catch (\Exception $e) {
            if ($buktiBayarPath) {
                Storage::disk('public')->delete($buktiBayarPath);
            }
            return redirect()->back()->with('error', 'Gagal memproses pelunasan kolektif: ' . $e->getMessage());
        }
    }
}
