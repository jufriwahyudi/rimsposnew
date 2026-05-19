<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\Tenant;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreSelected
{
    /**
     * Route yang dikecualikan dari pengecekan store_id.
     */
    protected array $except = [
        'select-store',
        'select-store/choose',
        'login',
        'logout',
        'sso/*',
        'offline',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $selectedRole = session('selected_role');
        if ($selectedRole) {
            $role = \App\Models\RoleMaster::find($selectedRole);
            if ($role && $role->role_type === 'SUPERADMIN') {
                return $next($request);
            }
        }

        if (!session()->has('store_id')) {
            return redirect()->route('select-store.index');
        }

        // Inisialisasi Tenant untuk request ini agar StoreScope bekerja
        Tenant::set(session('store_id'));

        return $next($request);
    }
}
