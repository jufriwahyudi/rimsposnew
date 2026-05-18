<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();
            $selectedRole = $user->roles()->first();
            session(['selected_role' => ($selectedRole->role_id ?? 0)]);

            return redirect()->route('select-store.index');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ]);
    }

    public function logout(Request $request)
    {
        $selectedRole = session('selected_role');
        $userId = Auth::user()->id;

        // Hapus cache saat logout
        Cache::forget('menu_role_' . $selectedRole);
        Cache::forget('role_access_' . $selectedRole);
        Cache::forget('role_list_' . $userId);
        session()->forget('store_id');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
