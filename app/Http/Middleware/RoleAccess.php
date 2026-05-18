<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\MenubyRole;
use App\Models\MenuList;

class RoleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $menu = null): Response
    {
        // Ambil role ID dari user yang sedang login
        $roleId = session('selected_role');;

        // Ambil ID menu berdasarkan nama route atau nama menu yang diberikan
        $menuData = MenuList::where('routename', $menu)->first();

        if ($menuData) {
            // Cari di tabel menuby_role apakah role ini memiliki akses ke menu tersebut
            $hasAccess = MenubyRole::where('role_id', $roleId)
                ->where('menu_id', $menuData->id)
                ->exists();

            if ($hasAccess) {
                // Jika memiliki akses, lanjutkan ke halaman yang diminta
                return $next($request);
            }
        }

        // Jika tidak memiliki akses, redirect ke halaman error atau unauthorized
        return redirect('/unauthorized')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
    }
}
