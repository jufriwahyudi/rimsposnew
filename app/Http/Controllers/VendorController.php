<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::orderBy('nama_vendor')->get();
        return view('vendors.index', compact('vendors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_vendor' => 'required|string|max:50|unique:vendors,kode_vendor',
            'nama_vendor' => 'required|string|max:255',
            'telepon'     => 'nullable|string|max:50',
            'alamat'      => 'nullable|string',
        ]);

        $vendor = Vendor::create([
            'store_id'    => session('store_id'),
            'kode_vendor' => $request->kode_vendor,
            'nama_vendor' => $request->nama_vendor,
            'telepon'     => $request->telepon,
            'alamat'      => $request->alamat,
        ]);

        return response()->json(['success' => true, 'message' => 'Vendor berhasil ditambahkan.', 'data' => $vendor]);
    }

    public function edit(Vendor $vendor)
    {
        return response()->json($vendor);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'kode_vendor' => 'required|string|max:50|unique:vendors,kode_vendor,' . $vendor->id,
            'nama_vendor' => 'required|string|max:255',
            'telepon'     => 'nullable|string|max:50',
            'alamat'      => 'nullable|string',
        ]);

        $vendor->update([
            'kode_vendor' => $request->kode_vendor,
            'nama_vendor' => $request->nama_vendor,
            'telepon'     => $request->telepon,
            'alamat'      => $request->alamat,
        ]);

        return response()->json(['success' => true, 'message' => 'Vendor berhasil diperbarui.']);
    }

    public function destroy(Vendor $vendor)
    {
        if ($vendor->purchaseOrders()->exists()) {
            return response()->json(['success' => false, 'message' => 'Vendor tidak bisa dihapus karena sudah memiliki transaksi Purchase Order.'], 422);
        }

        $vendor->delete();

        return response()->json(['success' => true, 'message' => 'Vendor berhasil dihapus.']);
    }
}
