<?php

namespace App\Http\Middleware;

use App\Models\RoleUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RecoverUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        // Lewati rute SSO & auth
        if ($request->is('sso/*') || $request->is('login') || $request->is('logout')) {
            return $next($request);
        }

        if ($request->session()->missing('selected_role')) {
            $role = RoleUser::where('user_id', Auth::id())->first();
            if ($role) {
                $request->session()->put([
                    'selected_role' => $role->role_id,
                    'divisi_kerja'  => Auth::user()->id_divisi,
                ]);
            }
            // Jangan logout/redirect di middleware global; biar controller/halaman yang handle.
        }

        return $next($request);
    }
}
