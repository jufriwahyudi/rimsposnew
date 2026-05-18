<?php

namespace App\Http\Controllers;

use App\Models\RoleMaster;
use App\Models\RoleUser;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ManageUserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles.roles', 'stores'])->orderBy('name')->get();
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        $roles  = RoleMaster::where('stts', 'Y')->orderBy('nama')->get();

        return view('users.index', compact('users', 'stores', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'role_id'  => 'nullable|exists:role_master,id',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->filled('role_id')) {
            RoleUser::create([
                'user_id' => $user->id,
                'role_id' => $request->role_id,
            ]);
        }

        if ($request->filled('store_ids')) {
            $user->stores()->sync($request->store_ids);
        }

        return response()->json(['success' => true, 'message' => 'User berhasil ditambahkan.']);
    }

    public function edit(User $user)
    {
        $user->load(['roles', 'stores']);
        $currentRoleId = $user->roles->first()?->role_id;
        $currentStoreIds = $user->stores->pluck('id')->toArray();

        return response()->json([
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'role_id'         => $currentRoleId,
            'store_ids'       => $currentStoreIds,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'password'    => ['nullable', Password::min(8)],
            'role_id'     => 'nullable|exists:role_master,id',
            'store_ids'   => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            ...$request->filled('password')
                ? ['password' => Hash::make($request->password)]
                : [],
        ]);

        // Sync role (satu user, satu role aktif)
        RoleUser::where('user_id', $user->id)->delete();
        if ($request->filled('role_id')) {
            RoleUser::create([
                'user_id' => $user->id,
                'role_id' => $request->role_id,
            ]);
        }

        // Sync stores
        $user->stores()->sync($request->store_ids ?? []);

        // Bersihkan cache role user
        Cache::forget('role_list_' . $user->id);

        return response()->json(['success' => true, 'message' => 'User berhasil diupdate.']);
    }

    public function destroy(User $user)
    {
        Cache::forget('role_list_' . $user->id);
        $user->stores()->detach();
        RoleUser::where('user_id', $user->id)->delete();
        $user->delete();

        return response()->json(['success' => true, 'message' => 'User berhasil dihapus.']);
    }
}
