<?php

namespace App\Http\Middleware;

use App\Models\Divisi;
use App\Models\MenuList;
use App\Models\RoleMaster;
use App\Models\RoleUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class InjectUserDataToView
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lewatkan seluruh route yang diawali dengan 'sso/'
        if (
            $request->is('login') ||
            $request->is('logout') ||
            $request->is('/') ||
            $request->is('offline') ||
            $request->is('sso/login') ||
            $request->is('sso/callback')
        ) {
            return $next($request);
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Session telah berakhir, silakan login kembali.');
        }

        $selectedRole = session('selected_role');
        $userId = $user->id;

        if ($selectedRole) {
            $menus = Cache::remember("menu_role_{$selectedRole}", 360, function () use ($selectedRole) {
                return MenuList::where('id_parent', 0)
                    ->whereHas('roles', fn($q) => $q->where('role_id', $selectedRole))
                    ->orderBy('urutan')
                    ->with(['children' => fn($q) => $q->whereHas('roles', fn($r) => $r->where('role_id', $selectedRole))])
                    ->get();
            });

            $roleactive = Cache::remember("role_access_{$selectedRole}", 360, fn() => RoleMaster::find($selectedRole));

            // Dynamic Injection: If user is NOT a SUPERADMIN but has more than 1 active store, inject the "Laporan Konsolidasi" menu.
            $isSuperAdmin = $roleactive && strtoupper($roleactive->role_type) === 'SUPERADMIN';
            if (!$isSuperAdmin && $user->stores()->where('is_active', true)->count() > 1) {
                // Clone the collection to prevent altering the cached memory directly
                $menus = clone $menus;

                $laporanMenu = $menus->first(fn($m) => strtolower($m->nama) === 'laporan');
                if ($laporanMenu) {
                    $laporanMenu = clone $laporanMenu;
                    $laporanMenu->setRelation('children', clone $laporanMenu->children);
                    
                    // Replace in $menus
                    $menus = $menus->map(fn($m) => strtolower($m->nama) === 'laporan' ? $laporanMenu : $m);
                    
                    $hasConsolidated = $laporanMenu->children->contains(fn($child) => $child->routename === 'superadmin.consolidated-reports');
                    if (!$hasConsolidated) {
                        $consolidatedMenu = MenuList::where('routename', 'superadmin.consolidated-reports')->first();
                        if ($consolidatedMenu) {
                            $laporanMenu->children->push($consolidatedMenu);
                        }
                    }
                } else {
                    $parentLaporan = MenuList::where('nama', 'Laporan')->where('id_parent', 0)->first();
                    if ($parentLaporan) {
                        $parentLaporan = clone $parentLaporan;
                        $consolidatedMenu = MenuList::where('routename', 'superadmin.consolidated-reports')->first();
                        if ($consolidatedMenu) {
                            $parentLaporan->setRelation('children', collect([$consolidatedMenu]));
                            $menus->push($parentLaporan);
                        }
                    }
                }
            }

            $rolelist = Cache::remember("role_list_{$userId}", 360, fn() => RoleUser::where('user_id', $userId)->with('roles')->get());

            view()->share('menucache', $menus);
            view()->share('roleactive', $roleactive);
            view()->share('roleuserlist', $rolelist);
            view()->share('storelist', $user->stores);
        }

        return $next($request);
    }
}
