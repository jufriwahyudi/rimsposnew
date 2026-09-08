<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StorePrinter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StorePrinterController extends Controller
{
    /**
     * GET /api/pos/printers?store_id=N
     * Ambil daftar printer stasiun untuk toko tertentu.
     */
    public function index(Request $request)
    {
        $storeId = $request->integer('store_id');
        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $store = Store::find($storeId);
        if (!$store) {
            return response()->json(['message' => 'Toko tidak ditemukan'], 404);
        }

        $printers = StorePrinter::where('store_id', $storeId)
            ->orderBy('id', 'asc')
            ->get();

        // Jika toko FnB dengan addon_multi_printer aktif dan belum punya printer sama sekali,
        // buatkan template awal otomatis: Bar dan Dapur
        if ($printers->isEmpty() && $store->business_type === 'fnb' && $store->addon_multi_printer) {
            $defaultPrinters = [
                [
                    'store_id'        => $storeId,
                    'name'            => 'Printer Bar (Minuman)',
                    'code'            => 'bar',
                    'connection_type' => 'bluetooth',
                    'paper_size'      => '80mm',
                    'is_active'       => true,
                ],
                [
                    'store_id'        => $storeId,
                    'name'            => 'Printer Dapur (Makanan)',
                    'code'            => 'kitchen',
                    'connection_type' => 'lan',
                    'ip_address'      => '192.168.1.200',
                    'port'            => 9100,
                    'paper_size'      => '80mm',
                    'is_active'       => true,
                ],
            ];

            foreach ($defaultPrinters as $dp) {
                StorePrinter::create($dp);
            }

            $printers = StorePrinter::where('store_id', $storeId)->orderBy('id', 'asc')->get();
        }

        return response()->json([
            'success' => true,
            'data'    => $printers,
        ]);
    }

    /**
     * POST /api/pos/printers
     * Tambah printer stasiun baru (misal: "Tenant Sate").
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id'        => 'required|integer|exists:stores,id',
            'name'            => 'required|string|max:100',
            'code'            => 'nullable|string|max:50',
            'connection_type' => 'required|in:lan,bluetooth',
            'ip_address'      => 'nullable|string|max:50',
            'port'            => 'nullable|integer|min:1|max:65535',
            'mac_address'     => 'nullable|string|max:100',
            'paper_size'      => 'required|in:58mm,80mm',
            'is_active'       => 'nullable|boolean',
        ]);

        $code = $request->code ? Str::slug($request->code, '_') : Str::slug($request->name, '_');

        $printer = StorePrinter::create([
            'store_id'        => $request->store_id,
            'name'            => $request->name,
            'code'            => $code,
            'connection_type' => $request->connection_type,
            'ip_address'      => $request->ip_address,
            'port'            => $request->integer('port', 9100),
            'mac_address'     => $request->mac_address,
            'paper_size'      => $request->paper_size,
            'is_active'       => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Printer stasiun berhasil ditambahkan.',
            'data'    => $printer,
        ], 201);
    }

    /**
     * PUT /api/pos/printers/{id}
     * Update pengaturan printer stasiun.
     */
    public function update(Request $request, int $id)
    {
        $printer = StorePrinter::findOrFail($id);

        $request->validate([
            'name'            => 'sometimes|required|string|max:100',
            'code'            => 'nullable|string|max:50',
            'connection_type' => 'sometimes|required|in:lan,bluetooth',
            'ip_address'      => 'nullable|string|max:50',
            'port'            => 'nullable|integer|min:1|max:65535',
            'mac_address'     => 'nullable|string|max:100',
            'paper_size'      => 'sometimes|required|in:58mm,80mm',
            'is_active'       => 'nullable|boolean',
        ]);

        $data = $request->only([
            'name',
            'connection_type',
            'ip_address',
            'port',
            'mac_address',
            'paper_size',
        ]);

        if ($request->has('code') && $request->code) {
            $data['code'] = Str::slug($request->code, '_');
        }

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $printer->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan printer berhasil diperbarui.',
            'data'    => $printer,
        ]);
    }

    /**
     * DELETE /api/pos/printers/{id}
     * Hapus printer stasiun.
     */
    public function destroy(int $id)
    {
        $printer = StorePrinter::findOrFail($id);
        $printer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Printer berhasil dihapus.',
        ]);
    }
}
