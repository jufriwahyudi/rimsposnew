<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RoleMaster;

class RoleType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$allowedTypes): Response
    {
        // 🔒 Pastikan role dipilih
        if (! session()->has('selected_role')) {
            return redirect('/unauthorized')
                ->with('error', 'Role belum dipilih.');
        }

        $role = RoleMaster::find(session('selected_role'));

        if (! $role || $role->stts !== 'Y') {
            return redirect('/unauthorized')
                ->with('error', 'Role tidak aktif.');
        }

        // 🔍 Normalisasi ke uppercase
        $allowedTypes = array_map('strtoupper', $allowedTypes);

        if (! in_array(strtoupper($role->role_type), $allowedTypes)) {
            return redirect('/unauthorized')
                ->with('error', 'Anda tidak memiliki akses role ke halaman ini.');
        }

        return $next($request);
    }
}
