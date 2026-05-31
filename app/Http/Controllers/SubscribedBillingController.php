<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreSubscription;
use App\Models\SubscribedInvoice;
use App\Models\SubscribedPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubscribedBillingController extends Controller
{
    /**
     * Halaman utama SaaS Billing – daftar seluruh toko + info langganan.
     */
    public function index()
    {
        $stores = Store::with('subscription')
            ->orderBy('name')
            ->get();

        // Summary cards
        $totalStores     = $stores->count();
        $expiredStores   = $stores->filter(fn($s) => $s->subscription && $s->subscription->isExpired())->count();
        $graceStores     = $stores->filter(fn($s) => $s->subscription && $s->subscription->isInGracePeriod())->count();
        $lifetimeStores  = $stores->filter(fn($s) => $s->subscription && $s->subscription->package_type === 'lifetime')->count();
        $unpaidInvoices  = SubscribedInvoice::where('status', 'unpaid')->sum('billing_amount');

        return view('subscribed-billing.index', compact(
            'stores',
            'totalStores',
            'expiredStores',
            'graceStores',
            'lifetimeStores',
            'unpaidInvoices'
        ));
    }

    /**
     * Halaman detail billing toko (langganan, invoice, pembayaran).
     */
    public function show(Store $store)
    {
        $store->load(['subscription', 'invoices.payments']);

        $invoices = $store->invoices()->orderByDesc('created_at')->get();

        return view('subscribed-billing.show', compact('store', 'invoices'));
    }

    /**
     * Update konfigurasi langganan toko.
     */
    public function updateSubscription(Request $request, Store $store)
    {
        $request->validate([
            'package_type'   => 'required|in:lifetime,monthly,yearly',
            'billing_amount' => 'required|numeric|min:0',
            'start_date'     => 'nullable|required_if:package_type,monthly,yearly|date',
            'end_date'       => 'nullable|required_if:package_type,monthly,yearly|date|after_or_equal:start_date',
        ]);

        $data = [
            'package_type'   => $request->package_type,
            'billing_amount' => $request->billing_amount,
            'start_date'     => $request->package_type === 'lifetime' ? null : $request->start_date,
            'end_date'       => $request->package_type === 'lifetime' ? null : $request->end_date,
        ];

        StoreSubscription::updateOrCreate(
            ['store_id' => $store->id],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Paket langganan berhasil diperbarui.',
        ]);
    }

    /**
     * Buat invoice baru untuk toko.
     */
    public function storeInvoice(Request $request)
    {
        $request->validate([
            'store_id'       => 'required|exists:stores,id',
            'billing_amount' => 'required|numeric|min:1',
            'period_start'   => 'required|date',
            'period_end'     => 'required|date|after_or_equal:period_start',
            'due_date'       => 'required|date',
        ]);

        $invoice = SubscribedInvoice::create([
            'store_id'       => $request->store_id,
            'invoice_number' => SubscribedInvoice::generateInvoiceNumber(),
            'billing_amount' => $request->billing_amount,
            'period_start'   => $request->period_start,
            'period_end'     => $request->period_end,
            'due_date'       => $request->due_date,
            'status'         => 'unpaid',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice berhasil dibuat: ' . $invoice->invoice_number,
            'data'    => $invoice,
        ]);
    }

    /**
     * Catat pembayaran untuk invoice.
     * Jika lunas, otomatis perpanjang masa aktif toko.
     */
    public function storePayment(Request $request)
    {
        $request->validate([
            'subscribed_invoice_id' => 'required|exists:subscribed_invoices,id',
            'payment_date'          => 'required|date',
            'amount'                => 'required|numeric|min:1',
            'payment_method'        => 'required|string|max:50',
            'payment_proof'         => 'nullable|file|image|max:2048',
            'notes'                 => 'nullable|string|max:500',
        ]);

        $invoice = SubscribedInvoice::findOrFail($request->subscribed_invoice_id);

        // Simpan bukti bayar jika ada
        $proofPath = null;
        if ($request->hasFile('payment_proof') && $request->file('payment_proof')->isValid()) {
            $proofPath = $request->file('payment_proof')->store('subscription_proofs', 'public');
        }

        DB::transaction(function () use ($request, $invoice, $proofPath) {
            // Buat record pembayaran
            SubscribedPayment::create([
                'subscribed_invoice_id' => $invoice->id,
                'payment_date'          => $request->payment_date,
                'amount'                => $request->amount,
                'payment_method'        => $request->payment_method,
                'payment_proof'         => $proofPath,
                'notes'                 => $request->notes,
            ]);

            // Cek apakah invoice sudah lunas
            $totalPaid = $invoice->payments()->sum('amount') + $request->amount;

            if ($totalPaid >= $invoice->billing_amount) {
                $invoice->update(['status' => 'paid']);

                // Perpanjang masa aktif toko sesuai periode invoice
                StoreSubscription::updateOrCreate(
                    ['store_id' => $invoice->store_id],
                    [
                        'start_date' => $invoice->period_start,
                        'end_date'   => $invoice->period_end,
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dicatat.',
        ]);
    }
}
