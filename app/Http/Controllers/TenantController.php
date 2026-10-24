<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\StorePrinter;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('printer')->orderBy('nama_tenant')->get();
        $storePrinters = StorePrinter::where('store_id', session('store_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('tenants.index', compact('tenants', 'storePrinters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_tenant'     => 'required|string|max:50|unique:tenants,kode_tenant',
            'nama_tenant'     => 'required|string|max:255',
            'printer_id'      => 'nullable|exists:store_printers,id',
            'telepon'         => 'nullable|string|max:50',
            'alamat'          => 'nullable|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'stts'            => 'required|in:Y,N',
        ]);

        $tenant = Tenant::create([
            'store_id'        => session('store_id'),
            'printer_id'      => $request->printer_id ?: null,
            'kode_tenant'     => $request->kode_tenant,
            'nama_tenant'     => $request->nama_tenant,
            'telepon'         => $request->telepon,
            'alamat'          => $request->alamat,
            'commission_rate' => $request->commission_rate,
            'stts'            => $request->stts,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant berhasil ditambahkan.',
            'data'    => $tenant
        ]);
    }

    public function edit(Tenant $tenant)
    {
        return response()->json($tenant->load('printer'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $request->validate([
            'kode_tenant'     => 'required|string|max:50|unique:tenants,kode_tenant,' . $tenant->id,
            'nama_tenant'     => 'required|string|max:255',
            'printer_id'      => 'nullable|exists:store_printers,id',
            'telepon'         => 'nullable|string|max:50',
            'alamat'          => 'nullable|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'stts'            => 'required|in:Y,N',
        ]);

        $tenant->update([
            'printer_id'      => $request->printer_id ?: null,
            'kode_tenant'     => $request->kode_tenant,
            'nama_tenant'     => $request->nama_tenant,
            'telepon'         => $request->telepon,
            'alamat'          => $request->alamat,
            'commission_rate' => $request->commission_rate,
            'stts'            => $request->stts,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant berhasil diperbarui.'
        ]);
    }

    public function destroy(Tenant $tenant)
    {
        if ($tenant->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant tidak bisa dihapus karena masih memiliki produk.'
            ], 422);
        }

        if ($tenant->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant tidak bisa dihapus karena masih memiliki user/pegawai.'
            ], 422);
        }

        $tenant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tenant berhasil dihapus.'
        ]);
    }

    /**
     * GET /settings/tenants/activity-logs
     * Menampilkan UI monitor realtime untuk aktivitas dan notifikasi tenant.
     */
    public function activityLogs(Request $request)
    {
        $rawLogs = $this->getRecentTenantLogs(200);

        $sentCount = count(array_filter($rawLogs, fn($l) => str_contains($l['type'], 'TERKIRIM')));
        $skippedCount = count(array_filter($rawLogs, fn($l) => str_contains($l['type'], 'DI-SKIP')));
        $errorCount = count(array_filter($rawLogs, fn($l) => str_contains($l['type'], 'GAGAL')));

        $stats = [
            'sent'    => $sentCount,
            'skipped' => $skippedCount,
            'error'   => $errorCount,
            'total'   => count($rawLogs),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'logs'    => $rawLogs,
                'stats'   => $stats,
            ]);
        }

        return view('tenants.logs', compact('rawLogs', 'stats'));
    }

    /**
     * Membaca dan mem-parsing log tenant terbaru dari storage/logs/laravel.log.
     */
    protected function getRecentTenantLogs(int $maxLines = 200): array
    {
        $logFile = storage_path('logs/laravel.log');
        if (! file_exists($logFile)) {
            return [];
        }

        try {
            $file = new \SplFileObject($logFile, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();

            // Scan hingga 3000 baris terakhir dari file log
            $startLine = max(0, $totalLines - 3000);
            $file->seek($startLine);

            $parsed = [];
            while (! $file->eof()) {
                $line = trim((string) $file->fgets());
                if (empty($line)) {
                    continue;
                }

                if (stripos($line, 'tenant') === false) {
                    continue;
                }

                if (preg_match('/^\[(.*?)\]\s+\w+\.(\w+):\s+(.*)$/', $line, $m)) {
                    $time = $m[1];
                    $level = strtoupper($m[2]);
                    $msg = $m[3];

                    $type = 'INFO';
                    $badge = 'bg-info';
                    $icon = 'info';

                    if (stripos($msg, 'SKIPPED') !== false) {
                        $type = 'DI-SKIP (Pelunasan Kasir)';
                        $badge = 'bg-warning text-dark';
                        $icon = 'block';
                    } elseif (stripos($msg, 'Sent tenant order') !== false) {
                        $type = 'TERKIRIM (Pesanan Baru)';
                        $badge = 'bg-success text-white';
                        $icon = 'check_circle';
                    } elseif ($level === 'ERROR' || stripos($msg, 'failed') !== false || stripos($msg, 'error') !== false) {
                        $type = 'GAGAL / ERROR';
                        $badge = 'bg-danger text-white';
                        $icon = 'error';
                    }

                    $parsed[] = [
                        'time'    => $time,
                        'level'   => $level,
                        'type'    => $type,
                        'badge'   => $badge,
                        'icon'    => $icon,
                        'message' => $msg,
                    ];
                }
            }

            return array_slice(array_reverse($parsed), 0, $maxLines);
        } catch (\Throwable $e) {
            \Log::error('Error reading tenant activity logs: ' . $e->getMessage());
            return [];
        }
    }
}
