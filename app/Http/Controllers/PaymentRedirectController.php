<?php

namespace App\Http\Controllers;

use App\Models\QrisTransaction;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class PaymentRedirectController extends Controller
{
    protected MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    public function finish(Request $request)
    {
        $orderId = $request->query('order_id');
        $qrisTx = null;
        if ($orderId) {
            $qrisTx = $this->midtransService->checkStatus($orderId);
        }

        return view('payment.finish', [
            'orderId' => $orderId,
            'qrisTx'  => $qrisTx,
            'status'  => 'success',
            'title'   => 'Pembayaran Berhasil',
        ]);
    }

    public function unfinish(Request $request)
    {
        $orderId = $request->query('order_id');
        return view('payment.unfinish', [
            'orderId' => $orderId,
            'status'  => 'pending',
            'title'   => 'Pembayaran Belum Selesai',
        ]);
    }

    public function error(Request $request)
    {
        $orderId = $request->query('order_id');
        return view('payment.error', [
            'orderId' => $orderId,
            'status'  => 'error',
            'title'   => 'Pembayaran Gagal',
        ]);
    }
}
