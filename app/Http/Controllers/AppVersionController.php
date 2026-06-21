<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    /**
     * Show settings form for App Version updates.
     */
    public function index()
    {
        $appVersion = AppVersion::first();
        if (!$appVersion) {
            $appVersion = new AppVersion([
                'version' => '1.0.0',
                'build_number' => 1,
                'download_url' => '',
                'mandatory' => false,
            ]);
        }

        return view('pengaturan.app-version.index', compact('appVersion'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'version' => 'required|string|max:20',
            'build_number' => 'required|integer|min:1',
            'download_url' => 'nullable|string|max:2048',
            'apk_file' => 'nullable|file|max:100000', // max 100MB
            'mandatory' => 'boolean',
        ]);

        $appVersion = AppVersion::first();

        if (!$request->filled('download_url') && !$request->hasFile('apk_file')) {
            if (!$appVersion || empty($appVersion->download_url)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masukkan URL Unduhan atau unggah berkas APK.',
                ], 422);
            }
        }

        $downloadUrl = $request->input('download_url');

        if ($request->hasFile('apk_file')) {
            $file = $request->file('apk_file');
            
            // Generate clean filename
            $filename = 'rimspos_' . str_replace('.', '_', $request->input('version')) . '_b' . $request->input('build_number') . '.apk';
            
            // Store file in public storage disk, under 'app-releases' folder
            $path = $file->storeAs('app-releases', $filename, 'public');
            
            // Generate public URL
            $downloadUrl = asset('storage/' . $path);
        }

        if (!$appVersion) {
            $appVersion = new AppVersion();
        }

        $appVersion->fill([
            'version' => $request->input('version'),
            'build_number' => $request->input('build_number'),
            'download_url' => $downloadUrl ?? $appVersion->download_url ?? '',
            'mandatory' => $request->boolean('mandatory'),
        ]);

        $appVersion->save();

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi update APK berhasil disimpan.',
        ]);
    }

    /**
     * Public API endpoint for checking update status.
     */
    public function latestApi()
    {
        $appVersion = AppVersion::first();
        if (!$appVersion) {
            return response()->json([
                'version' => '1.0.0',
                'build_number' => 1,
                'download_url' => '',
                'mandatory' => false,
            ]);
        }

        return response()->json([
            'version' => $appVersion->version,
            'build_number' => $appVersion->build_number,
            'download_url' => $appVersion->download_url,
            'mandatory' => $appVersion->mandatory,
        ]);
    }
}
