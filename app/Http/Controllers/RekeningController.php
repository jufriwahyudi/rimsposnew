<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use Illuminate\Http\Request;

class RekeningController extends Controller
{
    public function index()
    {
        $rekenings = Rekening::orderBy('bank_rek')->orderBy('nama_rek')->get();
        return view('rekening.index', compact('rekenings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_rek'   => 'required|string|max:50',
            'nama_rek' => 'required|string|max:100',
            'bank_rek' => 'required|string|max:100',
        ]);

        $rekening = Rekening::create([
            'store_id' => session('store_id'),
            'no_rek'   => $request->no_rek,
            'nama_rek' => $request->nama_rek,
            'bank_rek' => $request->bank_rek,
        ]);

        return response()->json(['success' => true, 'message' => 'Rekening berhasil ditambahkan.', 'data' => $rekening]);
    }

    public function edit(Rekening $rekening)
    {
        return response()->json($rekening);
    }

    public function update(Request $request, Rekening $rekening)
    {
        $request->validate([
            'no_rek'   => 'required|string|max:50',
            'nama_rek' => 'required|string|max:100',
            'bank_rek' => 'required|string|max:100',
        ]);

        $rekening->update([
            'no_rek'   => $request->no_rek,
            'nama_rek' => $request->nama_rek,
            'bank_rek' => $request->bank_rek,
        ]);

        return response()->json(['success' => true, 'message' => 'Rekening berhasil diperbarui.']);
    }

    public function destroy(Rekening $rekening)
    {
        if ($rekening->cashTransactions()->exists()) {
            return response()->json(['success' => false, 'message' => 'Rekening tidak bisa dihapus karena sudah digunakan dalam transaksi.'], 422);
        }

        $rekening->delete();

        return response()->json(['success' => true, 'message' => 'Rekening berhasil dihapus.']);
    }
}
