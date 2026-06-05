<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::orderBy('name')->get();
        $trashed = Store::onlyTrashed()->orderBy('name')->get();
        $businesses = \App\Models\Business::orderBy('name')->get();
        return view('stores.index', compact('stores', 'trashed', 'businesses'));
    }

    public function store(Request $request)
    {
        $isNewBusiness = $request->business_id === 'new';

        $rules = [
            'business_id'  => 'required',
            'name'         => 'required|string|max:255',
            'code'         => 'required|string|max:50|unique:stores,code',
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:20',
            'printer_type' => 'required|in:58mm,80mm',
            'is_active'    => 'nullable|boolean',
            'logo_data'    => 'nullable|string',
            'bussiness_type' => 'required|in:retail,fnb',
            'addon_self_service' => 'nullable|boolean',
            'addon_kds'          => 'nullable|boolean',
        ];

        if (!$isNewBusiness) {
            $rules['business_id'] = 'required|exists:businesses,id';
        } else {
            $rules['code'] .= '|unique:businesses,code';
        }

        $request->validate($rules);

        $businessId = $request->business_id;
        if ($isNewBusiness) {
            $business = \App\Models\Business::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
            ]);
            $businessId = $business->id;
        }

        $logoPath = $this->saveLogo($request->logo_data);

        $store = Store::create([
            'business_id'  => $businessId,
            'name'         => $request->name,
            'code'         => strtoupper($request->code),
            'address'      => $request->address,
            'city'         => $request->city,
            'phone'        => $request->phone,
            'printer_type' => $request->printer_type,
            'is_active'    => $request->boolean('is_active', true),
            'logo'         => $logoPath,
            'business_type' => $request->bussiness_type,
            'addon_self_service' => $request->boolean('addon_self_service', false),
            'addon_kds'          => $request->boolean('addon_kds', false),
        ]);

        Vendor::create([
            'store_id' => $store->id,
            'kode_vendor' => 'TS-'.(strtoupper($store->code ?? $store->id) . '-001'),
            'nama_vendor' => 'Tanpa Supplier',
            'telepon' => '-',
            'alamat' => '-'
        ]);

        return response()->json(['success' => true, 'message' => 'Toko berhasil ditambahkan.']);
    }

    public function edit(Store $store)
    {
        return response()->json([
            ...$store->toArray(),
            'logo_url' => $store->logo ? Storage::url($store->logo) : null,
        ]);
    }

    public function update(Request $request, Store $store)
    {
        $isNewBusiness = $request->business_id === 'new';

        $rules = [
            'business_id'  => 'required',
            'name'         => 'required|string|max:255',
            'code'         => 'required|string|max:50|unique:stores,code,' . $store->id,
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:20',
            'printer_type' => 'required|in:58mm,80mm',
            'is_active'    => 'nullable|boolean',
            'logo_data'    => 'nullable|string',
            'bussiness_type' => 'required|in:retail,fnb',
            'addon_self_service' => 'nullable|boolean',
            'addon_kds'          => 'nullable|boolean',
        ];

        if (!$isNewBusiness) {
            $rules['business_id'] = 'required|exists:businesses,id';
        } else {
            $rules['code'] .= '|unique:businesses,code';
        }

        $request->validate($rules);

        $businessId = $request->business_id;
        if ($isNewBusiness) {
            $business = \App\Models\Business::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
            ]);
            $businessId = $business->id;
        }

        $data = [
            'business_id'  => $businessId,
            'name'         => $request->name,
            'code'         => strtoupper($request->code),
            'address'      => $request->address,
            'city'         => $request->city,
            'phone'        => $request->phone,
            'printer_type' => $request->printer_type,
            'is_active'    => $request->boolean('is_active', true),
            'business_type' => $request->bussiness_type,
            'addon_self_service' => $request->boolean('addon_self_service', false),
            'addon_kds'          => $request->boolean('addon_kds', false),
        ];

        if ($request->filled('logo_data')) {
            // Hapus logo lama
            if ($store->logo) {
                Storage::delete($store->logo);
            }
            $data['logo'] = $this->saveLogo($request->logo_data);
        }

        $store->update($data);

        return response()->json(['success' => true, 'message' => 'Toko berhasil diperbarui.']);
    }

    public function destroy(Store $store)
    {
        $store->delete();
        return response()->json(['success' => true, 'message' => 'Toko berhasil dihapus (soft delete).']);
    }

    public function restore($id)
    {
        Store::onlyTrashed()->findOrFail($id)->restore();
        return response()->json(['success' => true, 'message' => 'Toko berhasil dipulihkan.']);
    }

    // ─── private helper ──────────────────────────────────────────────────────

    private function saveLogo(?string $base64): ?string
    {
        if (!$base64) return null;

        // Hapus header data URI  (data:image/png;base64,...)
        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }

        $decoded = base64_decode($base64);
        if (!$decoded) return null;

        // cek apakah folder stores sudah ada, jika tidak buat folder
        if (!Storage::disk('public')->exists('stores')) {
            Storage::disk('public')->makeDirectory('stores');
        }

        $filename = 'stores/' . Str::uuid() . '.png';
        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }
}
