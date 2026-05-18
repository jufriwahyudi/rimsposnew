<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SSOController extends Controller
{
    public function redirectToSSO(Request $request)
    {
        $prompt = $request->query('prompt');
        $query = http_build_query([
            'client_id'     => env('SSO_CLIENT_ID'),
            'redirect_uri'  => env('SSO_REDIRECT_URI'),
            'response_type' => 'code',
            'scope'         => '', 
            'prompt'        => $prompt ?? '',
        ]);
        // dd(env('SSO_BASE_URL') . '/oauth/authorize?' . $query);
        return redirect(env('SSO_BASE_URL') . '/custom/authorize?' . $query);
    }

    public function handleSSOCallback(Request $request)
    {
        $response = Http::asForm()->post(env('SSO_BASE_URL') . '/oauth/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => env('SSO_CLIENT_ID'),
            'client_secret' => env('SSO_CLIENT_SECRET'),
            'redirect_uri'  => env('SSO_REDIRECT_URI'),
            'code'          => $request->code,
        ]);

        $accessToken = $response->json()['access_token'] ?? null;

        if (!$accessToken) {
            return redirect('/login')->with('error', 'Token tidak ditemukan.');
        }

        // Ambil data user dari server SSO
        $userResponse = Http::withToken($accessToken)->get(env('SSO_BASE_URL') . '/api/user');

        $ssoUser = $userResponse->json();

        // Temukan atau buat user lokal berdasarkan data dari SSO
        $user = User::updateOrCreate(
            ['nik' => $ssoUser['nik']],
            [
                'name' => $ssoUser['name'],
                'email' => $ssoUser['email'],
                'id_divisi' => $ssoUser['id_divisi'] ?? null,
                'id_pegawai' => $ssoUser['id_pegawai'] ?? null,
                'password' => bcrypt(\Str::random(32)) // atau nullable
            ]
        );
        //dd($user, $ssoUser);

        Auth::guard('web')->login($user);
        $selectedRole = $user->roles()->first();
        session([
            'selected_role' => ($selectedRole->role_id ?? 0),
            'divisi_kerja' => ($user->id_divisi ?? 0)
        ]);

        return redirect()->route('dashboard')->with('success', 'Berhasil login dengan SSO.');
    }
    public function bypassSSO($id = 100)
    {
        // ==== 1. Tentukan user yang akan dipakai untuk bypass ====
        // Bisa pakai NIK tertentu atau ID user lokal
        $nikBypass = $id; // sesuaikan
        $defaultName = 'SSO Bypass User';

        // Cek apakah user sudah ada
        $user = User::where('nik', $nikBypass)->first();

        // Jika belum ada → buat user dummy
        if (!$user) {
            $user = User::create([
                'nik'         => $nikBypass,
                'name'        => $defaultName,
                'email'       => 'bypass@example.com',
                'id_divisi'   => 1,   // sesuaikan
                'id_pegawai'  => 1,   // sesuaikan
            ]);
        }

        // ==== 2. Login secara manual ====
        Auth::guard('web')->login($user);

        // ==== 3. Set session role & divisi ====
        $selectedRole = $user->roles()->first();

        session([
            'selected_role' => ($selectedRole->role_id ?? 0),
            'divisi_kerja'  => ($user->id_divisi ?? 0),
        ]);

        return redirect()->route('dashboard')->with('success', 'Bypass login berhasil.');
    }
}
