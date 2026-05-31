<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::orderBy('nama_tenant')->get();
        return view('tenants.index', compact('tenants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_tenant'     => 'required|string|max:50|unique:tenants,kode_tenant',
            'nama_tenant'     => 'required|string|max:255',
            'telepon'         => 'nullable|string|max:50',
            'alamat'          => 'nullable|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'stts'            => 'required|in:Y,N',
        ]);

        $tenant = Tenant::create([
            'store_id'        => session('store_id'),
            'kode_tenant'     => $request->kode_tenant,
            'nama_tenant'     => $request->nama_tenant,
            'telepon'         => $request->telepon,
            'alamat'          => $request->alamat,
            'commission_rate' => $request->commission_rate,
            'stts'            => $request->stts,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant berhasil ditambahkan.',
            'data'    => $tenant
        ]);
    }

    public function edit(Tenant $tenant)
    {
        return response()->json($tenant);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $request->validate([
            'kode_tenant'     => 'required|string|max:50|unique:tenants,kode_tenant,' . $tenant->id,
            'nama_tenant'     => 'required|string|max:255',
            'telepon'         => 'nullable|string|max:50',
            'alamat'          => 'nullable|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'stts'            => 'required|in:Y,N',
        ]);

        $tenant->update([
            'kode_tenant'     => $request->kode_tenant,
            'nama_tenant'     => $request->nama_tenant,
            'telepon'         => $request->telepon,
            'alamat'          => $request->alamat,
            'commission_rate' => $request->commission_rate,
            'stts'            => $request->stts,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant berhasil diperbarui.'
        ]);
    }

    public function destroy(Tenant $tenant)
    {
        if ($tenant->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant tidak bisa dihapus karena masih memiliki produk.'
            ], 422);
        }

        if ($tenant->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant tidak bisa dihapus karena masih memiliki user/pegawai.'
            ], 422);
        }

        $tenant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tenant berhasil dihapus.'
        ]);
    }
}
