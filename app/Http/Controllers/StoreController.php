<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\MenubyRole;
use App\Models\MenuList;
use App\Models\Rekening;
use App\Models\RoleMaster;
use App\Models\RoleUser;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::with(['business', 'rekenings', 'tenants'])->withCount(['users'])->orderBy('name')->get();
        $trashed = Store::onlyTrashed()->orderBy('name')->get();
        $businesses = Business::orderBy('name')->get();
        $roles = RoleMaster::where('stts', 'Y')->with('store')->orderBy('nama')->get();

        return view('stores.index', compact('stores', 'trashed', 'businesses', 'roles'));
    }

    public function store(Request $request)
    {
        $isNewBusiness = $request->business_id === 'new';

        $rules = [
            'business_id'          => 'required',
            'name'                 => 'required|string|max:255',
            'code'                 => 'required|string|max:50|unique:stores,code',
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string|max:100',
            'phone'                => 'nullable|string|max:20',
            'printer_type'         => 'required|in:58mm,80mm,pdf',
            'is_active'            => 'nullable|boolean',
            'logo_data'            => 'nullable|string',
            'bussiness_type'       => 'required|in:retail,fnb',
            'addon_self_service'   => 'nullable|boolean',
            'addon_kds'            => 'nullable|boolean',
            'enable_cash_register' => 'nullable|boolean',

            // Onboarding options
            'create_rekening'       => 'nullable|boolean',
            'create_default_tenant' => 'nullable|boolean',
            'create_user'           => 'nullable|boolean',
        ];

        if ($request->boolean('create_user')) {
            $rules['user_name']        = 'required|string|max:255';
            $rules['user_email']       = 'required|email|unique:users,email';
            $rules['user_password']    = ['required', Password::min(8)];
            $rules['user_role_mode']   = 'required|in:existing,new';
            if ($request->user_role_mode === 'existing') {
                $rules['existing_role_id'] = 'required|exists:role_master,id';
            } else {
                $rules['new_role_name']    = 'required|string|max:200';
                $rules['new_role_type']    = 'required|in:STORE,ADMIN,WAREHOUSE,STELLING';
                $rules['copy_role_from']   = 'nullable|exists:role_master,id';
                $rules['menu_preset']      = 'nullable|in:cashier,admin_store,warehouse,kitchen';
            }
        }

        if (!$isNewBusiness) {
            $rules['business_id'] = 'required|exists:businesses,id';
        } else {
            $rules['code'] .= '|unique:businesses,code';
        }

        $request->validate($rules);

        return DB::transaction(function () use ($request, $isNewBusiness) {
            $businessId = $request->business_id;
            if ($isNewBusiness) {
                $business = Business::create([
                    'name' => $request->name,
                    'code' => strtoupper($request->code),
                ]);
                $businessId = $business->id;
            }

            $logoPath = $this->saveLogo($request->logo_data);

            $store = Store::create([
                'business_id'          => $businessId,
                'name'                 => $request->name,
                'code'                 => strtoupper($request->code),
                'address'              => $request->address,
                'city'                 => $request->city,
                'phone'                => $request->phone,
                'printer_type'         => $request->printer_type,
                'is_active'            => $request->boolean('is_active', true),
                'logo'                 => $logoPath,
                'business_type'        => $request->bussiness_type,
                'addon_self_service'   => $request->boolean('addon_self_service', false),
                'addon_kds'            => $request->boolean('addon_kds', false),
                'enable_cash_register' => $request->boolean('enable_cash_register', false),
            ]);

            // 1. Auto-create Supplier Default
            Vendor::create([
                'store_id'    => $store->id,
                'kode_vendor' => 'TS-' . (strtoupper($store->code ?? $store->id) . '-001'),
                'nama_vendor' => 'Tanpa Supplier',
                'telepon'     => '-',
                'alamat'      => '-',
            ]);

            // 2. Auto-create Rekening Kas Utama (Cash Drawer)
            if ($request->boolean('create_rekening', true)) {
                Rekening::create([
                    'store_id' => $store->id,
                    'no_rek'   => '1001',
                    'nama_rek' => 'Kas Toko ' . $store->name,
                    'bank_rek' => 'KAS / TUNAI',
                ]);
            }

            // 3. Auto-create Default Tenant if FnB
            $defaultTenant = null;
            if ($store->business_type === 'fnb' && $request->boolean('create_default_tenant', true)) {
                $defaultTenant = Tenant::create([
                    'store_id'        => $store->id,
                    'kode_tenant'     => 'TNT-' . strtoupper($store->code) . '-01',
                    'nama_tenant'     => 'Dapur Utama',
                    'telepon'         => '-',
                    'alamat'          => '-',
                    'commission_rate' => 0,
                    'stts'            => 'Y',
                ]);
            }

            // 4. Provision User & Role if requested
            if ($request->boolean('create_user')) {
                $roleId = null;

                if ($request->user_role_mode === 'existing') {
                    $roleId = $request->existing_role_id;
                } else {
                    // Create new role dedicated for this store
                    $newRole = RoleMaster::create([
                        'store_id'  => $store->id,
                        'nama'      => $request->new_role_name,
                        'role_type' => $request->new_role_type,
                        'stts'      => 'Y',
                    ]);
                    $roleId = $newRole->id;

                    // Assign menu permissions (copy or preset)
                    if ($request->filled('copy_role_from')) {
                        $sourceMenus = MenubyRole::where('role_id', $request->copy_role_from)->get();
                        foreach ($sourceMenus as $m) {
                            MenubyRole::create([
                                'role_id' => $newRole->id,
                                'menu_id' => $m->menu_id,
                            ]);
                        }
                    } else {
                        $this->applyRoleMenuPreset($newRole->id, $request->menu_preset ?? 'cashier');
                    }
                }

                $user = User::create([
                    'name'      => $request->user_name,
                    'email'     => $request->user_email,
                    'password'  => Hash::make($request->user_password),
                    'tenant_id' => ($request->new_role_type === 'STELLING' && $defaultTenant) ? $defaultTenant->id : null,
                ]);

                // Assign to role
                if ($roleId) {
                    RoleUser::create([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                    ]);
                }

                // Assign to store
                $user->stores()->attach($store->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Toko baru dan pengaturan onboarding berhasil dibuat.',
            ]);
        });
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
            'business_id'          => 'required',
            'name'                 => 'required|string|max:255',
            'code'                 => 'required|string|max:50|unique:stores,code,' . $store->id,
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string|max:100',
            'phone'                => 'nullable|string|max:20',
            'printer_type'         => 'required|in:58mm,80mm,pdf',
            'is_active'            => 'nullable|boolean',
            'logo_data'            => 'nullable|string',
            'bussiness_type'       => 'required|in:retail,fnb',
            'addon_self_service'   => 'nullable|boolean',
            'addon_kds'            => 'nullable|boolean',
            'enable_cash_register' => 'nullable|boolean',
        ];

        if (!$isNewBusiness) {
            $rules['business_id'] = 'required|exists:businesses,id';
        } else {
            $rules['code'] .= '|unique:businesses,code';
        }

        $request->validate($rules);

        $businessId = $request->business_id;
        if ($isNewBusiness) {
            $business = Business::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
            ]);
            $businessId = $business->id;
        }

        $data = [
            'business_id'          => $businessId,
            'name'                 => $request->name,
            'code'                 => strtoupper($request->code),
            'address'              => $request->address,
            'city'                 => $request->city,
            'phone'                => $request->phone,
            'printer_type'         => $request->printer_type,
            'is_active'            => $request->boolean('is_active', true),
            'business_type'        => $request->bussiness_type,
            'addon_self_service'   => $request->boolean('addon_self_service', false),
            'addon_kds'            => $request->boolean('addon_kds', false),
            'enable_cash_register' => $request->boolean('enable_cash_register', false),
        ];

        if ($request->filled('logo_data')) {
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

    /**
     * GET /stores/{store}/summary
     * Detail & summary overview for Quick Store Management Hub.
     */
    public function summary(Store $store)
    {
        $store->load(['business']);

        $users = User::whereHas('stores', function ($q) use ($store) {
            $q->where('stores.id', $store->id);
        })->with(['roles.roles', 'tenant'])->get();

        $roles = RoleMaster::where('stts', 'Y')
            ->where(function ($q) use ($store) {
                $q->where('store_id', $store->id)
                  ->orWhereNull('store_id');
            })->get();

        $rekenings = Rekening::where('store_id', $store->id)->get();
        $tenants = Tenant::where('store_id', $store->id)->get();

        return response()->json([
            'store'     => $store,
            'users'     => $users,
            'roles'     => $roles,
            'rekenings' => $rekenings,
            'tenants'   => $tenants,
        ]);
    }

    /**
     * POST /stores/{store}/quick-user
     * Add a user directly assigned to this store.
     */
    public function quickUser(Request $request, Store $store)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => ['required', Password::min(8)],
            'role_id'   => 'nullable|exists:role_master,id',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'tenant_id' => $request->tenant_id,
        ]);

        if ($request->filled('role_id')) {
            RoleUser::create([
                'user_id' => $user->id,
                'role_id' => $request->role_id,
            ]);
        }

        $user->stores()->attach($store->id);

        return response()->json(['success' => true, 'message' => 'User berhasil ditambahkan ke toko ' . $store->name]);
    }

    /**
     * POST /stores/{store}/quick-role
     * Create a role specific to this store with cloned permissions or preset.
     */
    public function quickRole(Request $request, Store $store)
    {
        $request->validate([
            'nama_role'       => 'required|string|max:200',
            'jenis_role'      => 'required|in:STORE,ADMIN,WAREHOUSE,SUPERADMIN,STELLING',
            'copy_role_from'  => 'nullable|exists:role_master,id',
            'menu_preset'     => 'nullable|in:cashier,admin_store,warehouse,kitchen',
        ]);

        $role = RoleMaster::create([
            'store_id'  => $store->id,
            'nama'      => $request->nama_role,
            'role_type' => $request->jenis_role,
            'stts'      => 'Y',
        ]);

        if ($request->filled('copy_role_from')) {
            $sourceMenus = MenubyRole::where('role_id', $request->copy_role_from)->get();
            foreach ($sourceMenus as $m) {
                MenubyRole::create([
                    'role_id' => $role->id,
                    'menu_id' => $m->menu_id,
                ]);
            }
        } else {
            $this->applyRoleMenuPreset($role->id, $request->menu_preset ?? 'cashier');
        }

        return response()->json(['success' => true, 'message' => 'Role berhasil dibuat untuk toko ' . $store->name]);
    }

    // ─── private helper ──────────────────────────────────────────────────────

    private function applyRoleMenuPreset(int $roleId, string $preset)
    {
        $routeNames = [];

        switch ($preset) {
            case 'cashier':
                // Cashier: POS, Data Penjualan, Shift Kasir, Pelanggan
                $routeNames = ['pos.index', 'pos.sales', 'cash-registers.index', 'customers.index'];
                break;

            case 'admin_store':
                // Admin Toko: POS, Produk, Stok, Laporan, Shift, Pelanggan
                $routeNames = [
                    'dashboard', 'pos.index', 'pos.sales', 'cash-registers.index',
                    'produk.index', 'attributes.index', 'attribute-nilai.index',
                    'stock-adjustments.index', 'stock-opname-periods.index',
                    'laporan.penjualan', 'laporan.stok', 'laporan.harian', 'laporan.penerimaan_kas',
                    'customers.index', 'tenants.index'
                ];
                break;

            case 'warehouse':
                // Warehouse: PO, Stock Opname, Penyesuaian, Bahan Baku, Resep
                $routeNames = [
                    'po.index', 'stock-opname-periods.index', 'stock-adjustments.index',
                    'ingredients.index', 'ingredient-stocks.index', 'recipes.index', 'laporan.stok'
                ];
                break;

            case 'kitchen':
                // Kitchen / Dapur: Bahan Baku, Resep
                $routeNames = ['ingredients.index', 'recipes.index', 'ingredient-stocks.index'];
                break;
        }

        $menus = MenuList::where(function ($q) use ($routeNames) {
            $q->whereIn('routename', $routeNames);
        })->get();

        $menuIds = [];
        foreach ($menus as $m) {
            $menuIds[] = $m->id;
            if ($m->id_parent && $m->id_parent > 0) {
                $menuIds[] = $m->id_parent;
            }
        }
        $menuIds = array_unique($menuIds);

        foreach ($menuIds as $mId) {
            MenubyRole::firstOrCreate([
                'role_id' => $roleId,
                'menu_id' => $mId,
            ]);
        }
    }

    private function saveLogo(?string $base64): ?string
    {
        if (!$base64) return null;

        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }

        $decoded = base64_decode($base64);
        if (!$decoded) return null;

        if (!Storage::disk('public')->exists('stores')) {
            Storage::disk('public')->makeDirectory('stores');
        }

        $filename = 'stores/' . Str::uuid() . '.png';
        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }
}
