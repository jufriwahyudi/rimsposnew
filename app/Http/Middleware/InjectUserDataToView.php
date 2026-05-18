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

            $rolelist = Cache::remember("role_list_{$userId}", 360, fn() => RoleUser::where('user_id', $userId)->with('roles')->get());

            view()->share('menucache', $menus);
            view()->share('roleactive', $roleactive);
            view()->share('roleuserlist', $rolelist);

            // if ($user->id_divisi == 6 || $user->multidivisi === 'Y') {
            //     view()->share('listdivisi', Divisi::getActiveSchool()->get());
            // }
        }
        return $next($request);
    }
}
