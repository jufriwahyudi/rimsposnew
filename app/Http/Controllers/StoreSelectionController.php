<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreSelectionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stores = $user->stores()->where('is_active', true)->orderBy('name')->get();

        // jika role type SUPERADMIN, langsung bisa masuk tanpa pilih toko
        $selectedRole = session('selected_role');
        if ($selectedRole) {
            $role = \App\Models\RoleMaster::find($selectedRole);
            if ($role && $role->role_type === 'SUPERADMIN') {
                session([
                    'store_id' => null,
                    'store_name' => 'Super Admin Access'
                ]);
                Tenant::set(null);
                return redirect()->route('dashboard');
            }
        }
        // Jika user hanya punya satu toko, langsung pilih otomatis
        if ($stores->count() === 1) {
            session([
                'store_id' => $stores->first()->id,
                'store_name' => $stores->first()->name
            ]);
            Tenant::set($stores->first()->id);
            return redirect()->route('dashboard');
        }

        return view('auth.select-store', compact('stores'));
    }

    public function choose(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer',
        ]);

        $user = Auth::user();
        $store = $user->stores()
            ->where('stores.id', $request->store_id)
            ->where('is_active', true)
            ->first();

        if (!$store) {
            return back()->withErrors(['store_id' => 'Toko tidak valid atau tidak memiliki akses.']);
        }

        session([
            'store_id' => $store->id,
            'store_name' => $store->name
        ]);
        Tenant::set($store->id);

        return redirect()->route('dashboard');
    }
}
