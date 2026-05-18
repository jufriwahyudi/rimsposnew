<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\JadwalDistribusi;
use App\Models\JadwalSeragamSiswa;
use App\Models\JadwalSesi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\NseCalonSiswa;
use App\Models\Product;
use App\Models\SeragamDistribusi;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\UkuranSeragam;
use App\Services\JournalFromCashTransactionService;
use PDF;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class NseDistribusiController extends Controller
{
    public function index(Request $request)
    {
        $akunkas = DB::connection('financedb')
            ->table('rekening')
            ->where('kasbank', 'Y')
            ->get();

        $akunkasir = DB::connection('financedb')
            ->table('a1_user')
            ->where('id', auth()->user()->id_user_finance)
            ->first()
            ->kd_akun ?? null;

        return view('nse.distribusi.index', compact('akunkas', 'akunkasir'));
    }

    public function listSiswaBelumAmbilSeragam(Request $request)
    {
        if ($request->ajax()) {
            $defaultDb = config('database.connections.mysql.database');
            $query = DB::connection('nsedb')
                ->table('biodatadiri as p')
                ->selectRaw("
                    p.id_biodatadiri,
                    p.nama_lengkap,
                    p.jk,
                    p.nik,
                    p.tempat_lahir,
                    p.tgl_lahir,
                    p.voucher_seragam,
                    p.ambil_seragam,
                    d.nama as namadivisi,
                    g.nama as namagelombang,
                    t.nama as tahunajaran,
                    u.phone as no_hp,

                    jd.tanggal as jadwal_tanggal,
                    js.jam_mulai,
                    js.jam_selesai
                ")
                ->leftJoin('tagihan_daftar_ulang as q', 'p.id_biodatadiri', '=', 'q.id_biodata')
                ->leftJoin('master_divisi as d', 'p.id_divisi', '=', 'd.id')
                ->leftJoin('master_gelombang as g', 'p.idgelombang', '=', 'g.id')
                ->leftJoin('master_tahun_ajaran as t', 'g.id_tahun_ajaran', '=', 't.id')
                ->leftJoin('users as u', 'p.iduser', '=', 'u.id')

                // JOIN KE DB DEFAULT (FULL QUALIFIED)
                ->leftJoin(DB::raw("$defaultDb.jadwal_seragam_siswa as jss"), function ($join) {
                    $join->on('jss.id_biodata', '=', 'p.id_biodatadiri');
                })
                ->leftJoin(DB::raw("$defaultDb.jadwal_distribusi as jd"), 'jd.id', '=', 'jss.jadwal_id')
                ->leftJoin(DB::raw("$defaultDb.jadwal_sesi as js"), 'js.id', '=', 'jss.sesi_id')

                ->where('q.stts_byr', 'Y')
                ->whereIn('p.voucher_seragam', ['Y', 'N'])
                ->whereIn('p.ambil_seragam', ['S', 'N']);

            // 👉 FILTER DIVISI
            if ($request->filled('divisi')) {
                $query->where('p.id_divisi', $request->divisi);
            }
            // 👉 FILTER JADWAL
            if ($request->filled('jadwal')) {
                $query->where('jd.id', $request->jadwal);
            }
            // 👉 FILTER GELOMBANG
            if ($request->filled('gelombang')) {
                $query->where('p.idgelombang', $request->gelombang);
            }
            // 👉 FILTER TAHUN AJARAN
            if ($request->filled('tahun_ajaran')) {
                $query->where('t.id', $request->tahun_ajaran);
            }
            // 👉 FILTER GENDER
            if ($request->filled('gender')) {
                $query->where('p.jk', $request->gender);
            }

            // 📊 Hitung stats berdasarkan filter yang sama
            $statsQuery = clone $query;
            $statsQuery->columns = []; // clear existing select columns
            $statsRaw = $statsQuery->select(DB::raw("
                COUNT(*) as total,
                SUM(CASE WHEN p.ambil_seragam = 'N' THEN 1 ELSE 0 END) as belum,
                SUM(CASE WHEN p.ambil_seragam = 'S' THEN 1 ELSE 0 END) as sebagian,
                SUM(CASE WHEN jd.tanggal IS NOT NULL THEN 1 ELSE 0 END) as terjadwal
            "))->first();

            $stats = [
                'total' => $statsRaw->total ?? 0,
                'belum' => $statsRaw->belum ?? 0,
                'sebagian' => $statsRaw->sebagian ?? 0,
                'terjadwal' => $statsRaw->terjadwal ?? 0,
            ];

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('tgl_lahir', function ($row) {
                    return $row->tempat_lahir . ', ' .
                        Carbon::parse($row->tgl_lahir)
                        ->locale('id')
                        ->translatedFormat('d M Y');
                })

                ->editColumn('ambil_seragam', function ($row) {
                    if ($row->ambil_seragam === 'N') {
                        return '<span class="badge-status-siswa belum"><i class="bi bi-clock"></i> Belum</span>';
                    }
                    return '<span class="badge-status-siswa sebagian"><i class="bi bi-hourglass-split"></i> Sebagian</span>';
                })

                // KOLOM JADWAL DISTRIBUSI
                ->addColumn('jadwal', function ($row) {
                    if (!$row->jadwal_tanggal) {
                        return '<span class="badge-jadwal belum"><i class="bi bi-calendar-x"></i> Belum</span>';
                    }

                    return '<span class="badge-jadwal terjadwal"><i class="bi bi-calendar-check"></i> '
                        . Carbon::parse($row->jadwal_tanggal)->translatedFormat('d M Y')
                        . '</span><br><small class="text-muted" style="font-size:0.72rem;">'
                        . substr($row->jam_mulai, 0, 5) . ' - ' . substr($row->jam_selesai, 0, 5)
                        . '</small>';
                })

                ->addColumn('aksi', function ($row) {
                    $btnJadwal = '<a href="javascript:void(0);" class="btn-action-sm jadwal pilih-jadwal me-1" data-id="' . $row->id_biodatadiri . '" data-nama="' . htmlspecialchars($row->nama_lengkap) . '"><i class="bi bi-calendar"></i> Jadwal</a>';

                    $btnPilih = '<a href="' . route('nse.distribusi.index', ['id_biodata' => $row->id_biodatadiri]) . '" class="btn-action-sm pilih"><i class="bi bi-box-seam"></i> Pilih</a>';
                    //tombol wa me ke no_hp user
                    if ($row->no_hp) {
                        $waLink = 'https://wa.me/' . preg_replace('/^0/', '62', $row->no_hp) . '?text=' . urlencode('Assalamu\'alaikum, ' . $row->nama_lengkap . '.');
                        $btnWA = '<a href="' . $waLink . '" target="_blank" class="btn-action-sm wa"><i class="bi bi-whatsapp"></i> WA</a>';
                        return '<div class="d-flex align-items-center justify-content-center flex-wrap gap-1">' . $btnJadwal . $btnPilih . $btnWA . '</div>';
                    }

                    return '<div class="d-flex align-items-center justify-content-center flex-wrap gap-1">' . $btnJadwal . $btnPilih . '</div>';
                })

                ->rawColumns(['ambil_seragam', 'jadwal', 'aksi'])
                ->with('stats', $stats)
                ->make(true);
        }

        // 🔽 DROPDOWN DIVISI
        $divisis = DB::connection('nsedb')
            ->table('master_divisi')
            ->orderBy('nama')
            ->get();

        // 🔽 DROPDOWN GELOMBANG
        $gelombangs = DB::connection('nsedb')
            ->table('master_gelombang')
            ->whereIn('stts', ['Y', 'N']) // Y-Open, N-Close, D-Deleted
            ->orderBy('nama')
            ->get();

        // 🔽 DROPDOWN TAHUN AJARAN
        $tahunAjarans = DB::connection('nsedb')
            ->table('master_tahun_ajaran')
            ->orderBy('nama', 'desc')
            ->get();

        $jadwal = JadwalDistribusi::where('is_active', 'Y')
            ->with(['sesi' => function ($q) {
                $q->withCount('peserta');
            }])
            ->orderBy('tanggal')
            ->get();

        return view('nse.distribusi.list_siswa', compact('divisis', 'jadwal', 'gelombangs', 'tahunAjarans'));
    }


    /* =====================================================
     * LOAD SISWA & TEMPLATE SERAGAM
     * ===================================================== */
    public function loadSiswa(Request $request)
    {
        $siswa = NseCalonSiswa::with('divisi')->findOrFail($request->id);

        // Generate slot jika belum ada
        if (!SeragamDistribusi::where('id_biodata', $siswa->id_biodatadiri)->exists()) {
            $this->generateDistribusi($siswa);
        }

        return response()->json([
            'siswa' => $siswa,
            'items' => $this->getItems($siswa->id_biodatadiri)
        ]);
    }

    public function searchSiswa(Request $request)
    {
        $q = trim($request->q);

        if (strlen($q) < 3) {
            return response()->json([]);
        }

        return NseCalonSiswa::where('nama_lengkap', 'like', "%{$q}%")
            ->limit(10)
            ->get([
                'id_biodatadiri',
                'nama_lengkap',
                'tgl_lahir',
                'id_divisi'
            ]);
    }

    public function searchSiswaPublic(Request $request)
    {
        $q = trim($request->q);

        if (strlen($q) < 3) {
            return response()->json([]);
        }

        return NseCalonSiswa::with(['divisi:id,nama', 'jadwalDistribusi.jadwal.sesi'])
            ->where(function ($query) use ($q) {
                $query->where('nama_lengkap', 'like', "%{$q}%")
                    ->orWhereHas('observasi', function ($q2) use ($q) {
                        $q2->where('no_va', 'like', "%{$q}%");
                    });
            })
            ->whereHas('daftarUlang', function ($q) {
                $q->where('stts_byr', 'Y');
            })
            ->whereIn('voucher_seragam', ['Y', 'N'])
            ->whereIn('ambil_seragam', ['S', 'N'])
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'id_biodatadiri' => $s->id_biodatadiri,
                'nama_lengkap'   => $s->nama_lengkap,
                'nik'            => $s->nik,
                'divisi'         => $s->divisi->nama ?? '-',
                'jadwal'         => $s->jadwalDistribusi ? [
                    'tanggal' => $s->jadwalDistribusi->jadwal->tanggal ?? null,
                    'sesi'    => $s->jadwalDistribusi->sesi ? [
                        'jam_mulai' => $s->jadwalDistribusi->sesi->jam_mulai,
                        'jam_selesai' => $s->jadwalDistribusi->sesi->jam_selesai,
                    ] : null
                ] : null
            ]);
    }


    /* =====================================================
     * SCAN BARANG (AUTO / CHOOSE)
     * ===================================================== */
    public function scanBarang(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $barcodeInput = trim($request->barcode);
            $id_biodata = $request->id_biodata;
            $biodata = NseCalonSiswa::find($id_biodata);

            $variant = ProductVariant::with(['product', 'barcodeActive'])
                ->where(function ($q) use ($barcodeInput) {
                    $q->where('sku', $barcodeInput)
                        ->orWhereHas('barcodes', function ($q2) use ($barcodeInput) {
                            $q2->where('barcode', $barcodeInput);
                        });
                })
                ->first();

            if (!$variant) {
                return response()->json(['code' => 'BARCODE_INVALID', 'message' => 'Barcode tidak dikenal'], 422);
            }

            $activeBarcode = $variant->barcodeActive?->barcode;

            // Ambil semua mapping NSE untuk produk ini
            $mappings = UkuranSeragam::with('seragam')
                ->where('id_produk_koperasi', $variant->product_id)
                ->where('aktif', 'Y')
                ->whereHas('seragam', function ($qq) use ($biodata) {
                    $qq->whereIn('id_divisi', [$biodata->id_divisi, '0'])
                        ->whereIn('jk', ['U', ($biodata->jk === 'Perempuan' ? 'P' : 'L')]);
                })
                ->get();

            if ($mappings->isEmpty()) {
                return response()->json(['code' => 'NOT_IN_NSE_PACKAGE', 'message' => 'Barang tidak termasuk paket NSE'], 422);
            }

            // 🔹 HANYA 1 → AUTO FULFILL
            if ($mappings->count() === 1) {
                $this->fulfillSlot(
                    $request->id_biodata,
                    $mappings->first()->id_seragam,
                    $variant
                );

                return response()->json([
                    'mode'  => 'auto',
                    'items' => $this->getItems($request->id_biodata)
                ]);
            }

            // 🔸 LEBIH DARI 1 → PILIH
            return response()->json([
                'mode' => 'choose',
                'variant' => [
                    'sku'     => $variant->sku,
                    'barcode' => $activeBarcode,
                    'nama_produk' => $variant->product->nama_produk,
                    'label_variant' => $variant->variant_label
                ],
                'options' => $mappings->map(fn($m) => [
                    'id_seragam' => $m->id_seragam,
                    'nama'       => $m->seragam->nama
                ])
            ]);
        });
    }

    /* =====================================================
     * KONFIRMASI HASIL PILIHAN (DARI CHOOSER)
     * ===================================================== */
    public function confirmItem(Request $request)
    {
        $variant = ProductVariant::where('barcode', $request->barcode)
            ->where('is_active', 'Y')
            ->firstOrFail();

        $this->fulfillSlot(
            $request->id_biodata,
            $request->id_seragam,
            $variant,
            $request->is_additional === 'Y'  ? true : false
        );

        return response()->json([
            'mode'  => 'auto',
            'items' => $this->getItems($request->id_biodata)
        ]);
    }

    /* =====================================================
     * DELETE / ROLLBACK ITEM
     * ===================================================== */
    public function deleteItem(Request $request, $id)
    {
        $item = SeragamDistribusi::findOrFail($request->delete_id);

        if ($item->isAdditional === 'Y') {
            // Jika ini item tambahan, langsung hapus
            $item->delete();
        } else {
            // Jika ini item reguler, rollback ke pending
            $item->update([
                'id_product_variant' => 0,
                'status'             => 'pending',
                'scanned_at'         => null
            ]);
        }

        return response()->json([
            'message' => 'Item berhasil dibatalkan',
            'items'   => $this->getItems($item->id_biodata)
        ]);
    }

    /* =====================================================
     * CHECKOUT DISTRIBUSI
     * ===================================================== */
    public function checkoutDistribusi(Request $request)
    {
        try {
            $sale = DB::transaction(function () use ($request) {
                $transactionDate = $request->transaction_date ? Carbon::parse($request->transaction_date) : now();
                $receiptName = $request->receipt_name ?? null;
                $siswa = NseCalonSiswa::findOrFail($request->id_biodata);
                $items = SeragamDistribusi::with(['productVariant'])
                    ->where('id_biodata', $request->id_biodata)
                    ->where('status', 'fulfilled')
                    ->get();

                if ($items->isEmpty()) {
                    throw new \Exception('Data distribusi tidak ditemukan');
                }
                // =========================
                // 2️⃣ VALIDASI: TIDAK BOLEH ADA PENDING
                // =========================
                // if ($items->where('status', 'pending')->count() > 0) {
                //     throw new \Exception('Masih ada seragam yang belum discan');
                // }

                // Hitung jumlah uang yang harus dibukukan
                $totalHarga = $items->sum(fn($i) => ($i->productVariant->harga_jual ?? 0) * ($i->qty ?? 1));

                // =========================
                // 2️⃣ CREATE SALE
                // =========================
                $sale = Sale::create([
                    'invoice_number' => $this->generateInvoice(),
                    'sale_date'      => \Carbon\Carbon::parse($transactionDate)->setTimeFrom(now()), //\Carbon\Carbon::parse($transactionDate)->setTimeFrom(now())
                    'sale_type'      => 'nse',

                    'customer_id'    => $siswa->id_biodatadiri,
                    'customer_name'  => $siswa->nama_lengkap,
                    'receipt_name'   => $receiptName,
                    'user_id'        => auth()->id(),

                    'subtotal'       => $totalHarga,
                    'discount_total' => 0,
                    'trans_discount' => 0,
                    'tax_total'      => 0,
                    'grand_total'    => $totalHarga,
                    'paid_amount'    => $totalHarga,
                    'change_amount'  => 0,
                    'status'         => 'paid',
                ]);
                // =========================
                // $items->where('status', 'fulfilled');
                $grouped = $items->groupBy('id_product_variant');
                // dd(json_encode($grouped, JSON_PRETTY_PRINT));
                // =========================
                $hargamodal = 0;
                foreach ($grouped as $variantId => $rows) {
                    if (!$variantId) {
                        throw new \Exception('Variant tidak valid');
                    }
                    $item = ProductVariant::with('product')->find($variantId);
                    if (!$item) {
                        throw new \Exception('Variant tidak ditemukan');
                    }
                    $qty = $rows->sum(fn($r) => $r->qty ?? 1);
                    // =========================
                    // 4️⃣ LOCK & CEK STOK
                    // =========================
                    $saleItem = SaleItem::create([
                        'sale_id'            => $sale->id,
                        'product_id'         => $item->product_id,
                        'product_variant_id' => $item->id,
                        'sku'                => $item->sku,
                        'product_name'       => $item->product->nama_produk,
                        'price'              => $item->harga_jual ?? 0,
                        'qty'                => $qty,
                        'discount_amount'    => 0,
                        'subtotal'           => $item->harga_jual * $qty,
                    ]);

                    $modal = $this->issueFIFOWithBatchLog(
                        $item->id,
                        'store',
                        $qty,
                        $saleItem,
                        'SaleNSE',
                        $transactionDate
                    );
                    $hargamodal += $modal;
                    // $saleItem->update([
                    //     'price' => $qty > 0 ? round($modal / $qty) : 0,
                    //     'subtotal' => $modal
                    // ]);

                    // update seragam_distribusi status menjadi 'completed'
                    foreach ($rows as $row) {
                        $row->update([
                            'status' => 'completed',
                            'sale_item_id' => $saleItem->id
                        ]);
                    }
                }
                // $sale->update([
                //     'subtotal'       => $hargamodal,
                //     'grand_total'    => $hargamodal,
                //     'paid_amount'    => $hargamodal,
                // ]);

                //ambil kode akun dengan query berikut select q.kdakun from biaya_du p, master_daftar_harga q where p.id_komponen=q.id and id_biodata=1454 and nama like '%UNIFORM%'
                $akun = DB::connection('nsedb')
                    ->table('biaya_du as p')
                    ->join('master_daftar_harga as q', 'p.id_komponen', '=', 'q.id')
                    ->where('p.id_biodata', $siswa->id_biodatadiri)
                    ->where('q.nama', 'like', '%UNIFORM%')
                    ->value('q.kdbeban');

                CashTransaction::create([
                    'ref_type'         => 'SaleNSE',
                    'ref_id'           => $sale->id,
                    'transaction_type' => 'nse',
                    'payment_method'   => 'cash',
                    'account_code'     => $akun ?? '52.01.15',
                    'amount'           => $hargamodal,
                    'direction'        => 'in',
                    'transaction_date' => \Carbon\Carbon::parse($transactionDate)->setTimeFrom(now()),
                    'user_id'          => auth()->id(),
                    'notes'            => 'Distribusi NSE #' . $sale->invoice_number,
                ]);

                // check apakah masih ada seragam_distribusi dengan status pending
                $pendingWajib = SeragamDistribusi::where('id_biodata', $request->id_biodata)
                    ->where('status', 'pending')
                    ->whereRelation('seragam', 'wajib', 'Y')
                    ->exists();

                $siswa->update([
                    'ambil_seragam'   => $pendingWajib ? 'S' : 'F',
                    'voucher_seragam' => $pendingWajib ? $siswa->voucher_seragam : 'S'
                ]);

                $service = new JournalFromCashTransactionService();
                $service->createForNse($sale->id);

                return $sale;
            });
            return response()->json([
                'message' => 'Transaksi berhasil',
                'invoice' => $sale->invoice_number,
                'sale_id' => $sale->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function cetakpdf($id, $method)
    {
        $cetakpdf = true;
        if ($method === 'view') {
            $cetakpdf = false;
        } elseif ($method === 'download') {
            $cetakpdf = true;
        }
        $download = false;

        $path = 'assets/images/kop_surat.jpg';
        $background = 'assets/images/background.png';

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        $typeBg = pathinfo($background, PATHINFO_EXTENSION);
        $dataBg = file_get_contents($background);
        $base64Bg = 'data:image/' . $typeBg . ';base64,' . base64_encode($dataBg);

        $sale = Sale::with(['items', 'cashier'])->findOrFail($id);
        if ($sale->sale_type != 'nse') {
            abort(404);
        }
        $biodata = NseCalonSiswa::with('divisi.divisifinance')->findOrFail($sale->customer_id);
        $noreg = DB::connection('nsedb')
            ->table('tagihan_observasi')
            ->where('id_biodata', $biodata->id_biodatadiri)
            ->value('no_va');
        $alamat = DB::connection('nsedb')
            ->table('alamat')
            ->where('id_biodata', $biodata->id_biodatadiri)
            ->first();
        $gelombang = DB::connection('nsedb')
            ->table('master_gelombang')
            ->where('id', $biodata->idgelombang)
            ->first();
        $thnajaran = DB::connection('nsedb')
            ->table('master_tahun_ajaran')
            ->where('id', $gelombang->id_tahun_ajaran)
            ->value('nama');
        // dd($thnajaran, $gelombang->id_tahun_ajaran, $biodata->id_divisi);
        $foto = DB::connection('nsedb')
            ->table('berkas_uploud')
            ->where('id_biodata', $biodata->id_biodatadiri)
            ->where('iddataupload', '7')
            ->value('namaberkas');

        $jadwal = JadwalSeragamSiswa::with(['jadwal', 'sesi'])->where('id_biodata', $sale->customer_id)->first();
        $typeFoto = pathinfo($foto, PATHINFO_EXTENSION);
        $dataFoto = file_get_contents($foto);
        $base64Foto = 'data:image/' . $typeFoto . ';base64,' . base64_encode($dataFoto);

        $seragam = SeragamDistribusi::with(['seragam', 'productVariant'])
            ->where('id_biodata', $biodata->id_biodatadiri)
            ->get();

        $data = [
            'kop_surat' => $base64,
            'background' => $base64Bg,
            'biodata' => $biodata,
            'sale' => $sale,
            'noreg' => $noreg,
            'alamat' => $alamat,
            'gelombang' => $gelombang->nama,
            'thnajaran' => $thnajaran,
            'foto' => $base64Foto,
            'seragam' => $seragam,
            'cetakpdf' => $cetakpdf,
            'jadwal' => $jadwal
        ];
        if ($cetakpdf) {
            $pdf = PDF::loadView('nse.distribusi.cetak', $data);
            $pdf->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'isRemoteEnabled' => true
                ]);
            $pdf->render();
            // $canvas = $pdf->getCanvas();
            // $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            //     $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            //     $size = 8;
            //     $y = $canvas->get_height() - 42.52;
            //     $info = 'TEMBUSAN:';
            //     $canvas->text((42.52 * 1.5), ($y - (30)), $info, $font, $size);
            //     $info = '1. Kepala Dinas Pendidikan dan Kebudayaan Kota Banda Aceh';
            //     $canvas->text((42.52 * 1.5), ($y - (20)), $info, $font, $size);
            //     $info = '2. Kepala Sekolah (KB-TK, SD, SMP Islam Al-Azhar Cairo Banda Aceh)';
            //     $canvas->text((42.52 * 1.5), ($y - (10)), $info, $font, $size);
            //     $info = '3. Arsip';
            //     $canvas->text((42.52 * 1.5), $y, $info, $font, $size);
            // });
            if ($download) {
                $namaBersih = strtoupper(Str::slug($sale->customer_name, '_')); // hasil: "a_la_s_pd_gr"
                return $pdf->download("Invoice_{$namaBersih}.pdf");
            }
            return $pdf->stream('Invoice.pdf');
        } else {
            return response()->view('nse.distribusi.cetak', $data);
        }
    }

    public function jadwalAktif(Request $request)
    {
        $biodataid = $request->siswa_id;
        $biodata = NseCalonSiswa::with('divisi.divisifinance')->findOrFail($biodataid);
        $jadwal = JadwalDistribusi::where('is_active', 'Y')
            ->where('id_divisi', $biodata->divisi->divisifinance->Id ?? null)
            ->with(['sesi' => function ($q) {
                $q->withCount('peserta');
            }])
            ->orderBy('tanggal')
            ->get();

        return response()->json($jadwal);
    }

    public function jadwalBook(Request $request)
    {
        $request->validate([
            'sesi_id'  => 'required|exists:jadwal_sesi,id',
            'siswa_id' => 'required|exists:nsedb.biodatadiri,id_biodatadiri'
        ]);

        try {
            DB::transaction(function () use ($request) {

                // Lock sesi
                $sesi = JadwalSesi::lockForUpdate()->findOrFail($request->sesi_id);

                // Cek kuota
                if ($sesi->peserta()->count() >= $sesi->kuota_sesi) {
                    throw new \Exception('Kuota sesi penuh');
                }

                // Cegah double booking siswa di jadwal yang sama
                JadwalSeragamSiswa::updateOrCreate([
                    'id_biodata' => $request->siswa_id,
                ], [
                    'jadwal_id'  => $sesi->jadwal_id,
                    'sesi_id'    => $sesi->id,
                ]);
            });

            return response()->json([
                'message' => 'Jadwal berhasil dibooking'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function exportSiswaBelumAmbilSeragam(Request $request)
    {
        $defaultDb = config('database.connections.mysql.database');

        $query = DB::connection('nsedb')
            ->table('biodatadiri as p')
            ->selectRaw("
            p.nik,
            p.nama_lengkap,
            p.jk,
            p.tempat_lahir,
            p.tgl_lahir,
            d.nama as namadivisi,
            g.nama as namagelombang,
            t.nama as tahunajaran,
            p.ambil_seragam,
            jd.tanggal as jadwal_tanggal,
            js.jam_mulai,
            js.jam_selesai
        ")
            ->leftJoin('tagihan_daftar_ulang as q', 'p.id_biodatadiri', '=', 'q.id_biodata')
            ->leftJoin('master_divisi as d', 'p.id_divisi', '=', 'd.id')
            ->leftJoin('master_gelombang as g', 'p.idgelombang', '=', 'g.id')
            ->leftJoin('master_tahun_ajaran as t', 'g.id_tahun_ajaran', '=', 't.id')

            ->leftJoin(DB::raw("$defaultDb.jadwal_seragam_siswa as jss"), 'jss.id_biodata', '=', 'p.id_biodatadiri')
            ->leftJoin(DB::raw("$defaultDb.jadwal_distribusi as jd"), 'jd.id', '=', 'jss.jadwal_id')
            ->leftJoin(DB::raw("$defaultDb.jadwal_sesi as js"), 'js.id', '=', 'jss.sesi_id')

            ->where('q.stts_byr', 'Y')
            ->whereIn('p.voucher_seragam', ['Y', 'N'])
            ->whereIn('p.ambil_seragam', ['S', 'N']);

        // 🔎 FILTER (copy dari datatables)
        if ($request->filled('divisi')) {
            $query->where('p.id_divisi', $request->divisi);
        }

        if ($request->filled('jadwal')) {
            $query->where('jd.id', $request->jadwal);
        }

        if ($request->filled('gelombang')) {
            $query->where('p.idgelombang', $request->gelombang);
        }

        if ($request->filled('tahun_ajaran')) {
            $query->where('t.id', $request->tahun_ajaran);
        }

        if ($request->filled('gender')) {
            $query->where('p.jk', $request->gender);
        }

        $data = $query->get();

        // 🎯 mapping ke format excel

        $exportData = $data->map(function ($row, $i) {
            return [
                'No' => $i + 1,
                'NIK' => "'" . $row->nik,
                'Tahun Ajaran' => $row->tahunajaran,
                'Gelombang' => $row->namagelombang,
                'Divisi' => $row->namadivisi,
                'Nama Lengkap' => $row->nama_lengkap,
                'Gender' => $row->jk,
                'TTL' => $row->tempat_lahir . ', ' . Carbon::parse($row->tgl_lahir)->translatedFormat('d M Y'),
                'Status' => $row->ambil_seragam == 'N' ? 'Belum' : 'Sebagian',
                'Jadwal' => $row->jadwal_tanggal
                    ? Carbon::parse($row->jadwal_tanggal)->translatedFormat('d M Y') . ' (' .
                    substr($row->jam_mulai, 0, 5) . '-' . substr($row->jam_selesai, 0, 5) . ')'
                    : 'Belum',
            ];
        });

        return Excel::download(
            new class($exportData) implements
                \Maatwebsite\Excel\Concerns\FromCollection,
                \Maatwebsite\Excel\Concerns\WithHeadings,
                \Maatwebsite\Excel\Concerns\WithCustomStartCell,
                \Maatwebsite\Excel\Concerns\WithEvents {

                protected $data;

                public function __construct($data)
                {
                    $this->data = $data;
                }

                public function collection()
                {
                    return $this->data;
                }

                // 👉 header mulai dari row 3
                public function startCell(): string
                {
                    return 'A3';
                }

                public function headings(): array
                {
                    return [
                        'No',
                        'NIK',
                        'Tahun Ajaran',
                        'Gelombang',
                        'Divisi',
                        'Nama Lengkap',
                        'Gender',
                        'TTL',
                        'Status',
                        'Jadwal',
                    ];
                }

                public function registerEvents(): array
                {
                    return [
                        \Maatwebsite\Excel\Events\AfterSheet::class => function ($event) {

                            $sheet = $event->sheet->getDelegate();

                            // 🏷️ Judul di row 1
                            $sheet->mergeCells('A1:J1');
                            $sheet->setCellValue('A1', 'Distribusi Seragam NSE');

                            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                            // Bold header (row 3 sekarang)
                            $sheet->getStyle('A3:J3')->getFont()->setBold(true);

                            // Auto width
                            foreach (range('A', 'J') as $col) {
                                $sheet->getColumnDimension($col)->setAutoSize(true);
                            }
                        }
                    ];
                }
            },
            'distribusi-seragam-nse.xlsx'
        );
    }

    public function finishDistribusi(Request $request)
    {
        $siswa = NseCalonSiswa::findOrFail($request->id_biodata);

        // Cek apakah masih ada seragam_distribusi dengan status pending

        $siswa->update([
            'ambil_seragam'   => 'F',
            'voucher_seragam' => 'S'
        ]);

        return response()->json([
            'message' => 'Distribusi selesai, status siswa diperbarui'
        ]);
    }

    public function keepItem(Request $request)
    {
        $id = $request->slot_id;
        $variantId = $request->variant_id;
        $item = SeragamDistribusi::findOrFail($id);

        $item->update([
            'id_product_variant' => $variantId,
        ]);

        return response()->json([
            'message' => 'Item berhasil disimpan',
            'items'   => $this->getItems($item->id_biodata)
        ]);
    }

    protected function issueFIFOWithBatchLog(
        int $variantId,
        string $posisi,
        int $qty,
        SaleItem $saleItem,
        string $refType = 'SalePos',
        \DateTimeInterface $transactionDate = null
    ) {
        $batches = StockBatch::where('product_variant_id', $variantId)
            ->where('posisi', $posisi)
            ->where('qty_sisa', '>', 0)
            ->orderBy('tanggal_masuk')
            ->lockForUpdate()
            ->get();

        $sisa = $qty;

        $modal = 0;

        foreach ($batches as $batch) {
            if ($sisa <= 0) break;

            $ambil = min($batch->qty_sisa, $sisa);

            $batch->decrement('qty_sisa', $ambil);

            // LOG FIFO DETAIL
            SaleItemBatch::create([
                'sale_item_id'  => $saleItem->id,
                'stock_batch_id' => $batch->id,
                'qty'           => $ambil,
                'cost_price'    => $batch->harga_beli,
                'sell_price'    => $saleItem->price,
            ]);

            $modal += $ambil * $batch->harga_beli;

            // OPTIONAL: movement log (kalau belum dipanggil di StockService)
            StockMovement::create([
                'product_variant_id' => $variantId,
                'stock_batch_id'     => $batch->id,
                'posisi'             => $posisi,
                'tanggal'            => \Carbon\Carbon::parse($transactionDate)->setTimeFrom(now()),
                'tipe'               => 'out',
                'direction'          => 'out',
                'qty'                => $ambil,
                'ref_type'           => $refType,
                'ref_id'             => $saleItem->id,
            ]);

            $sisa -= $ambil;
        }

        if ($sisa > 0) {
            throw new \Exception('Stok tidak mencukupi');
        }
        return $modal;
    }

    protected function generateInvoice()
    {
        return 'NSE-' . now()->format('YmdHis');
    }
    /* =====================================================
     * HELPER: FULFILL SLOT
     * ===================================================== */
    protected function fulfillSlot($idBiodata, $idSeragam, ProductVariant $variant, $isAdditional = false)
    {
        $slot = null;

        if (!$isAdditional) {
            $slot = SeragamDistribusi::where('id_biodata', $idBiodata)
                ->where('id_seragam', $idSeragam)
                ->first();

            if (!$slot) {
                abort(response()->json([
                    'code'    => 'SLOT_NOT_FOUND',
                    'message' => 'Slot seragam tidak ditemukan'
                ], 422));
            }

            if ($slot->status !== 'pending') {
                abort(response()->json([
                    'code'    => 'SLOT_ALREADY_FULFILLED',
                    'message' => 'Slot seragam sudah terpenuhi',
                    'id_biodata' => $idBiodata,
                    'id_seragam' => $idSeragam,
                    'productVariant' => $variant
                ], 422));
            }
        }
        // Pengecekan stok ulang dengan lock
        $variant = ProductVariant::where('id', $variant->id)
            ->where('is_active', 'Y')
            ->lockForUpdate()
            ->first();

        // qty tergantung kondisi
        $qtyNeeded = $isAdditional ? 1 : ($slot->qty ?? 1);

        if (!$variant || $variant->stok_store < $qtyNeeded) {
            abort(response()->json([
                'code'    => 'STOK_KURANG',
                'message' => 'Stok barang tidak mencukupi',
                'slot_id' => $slot->id ?? null,
                'variant_id' => $variant->id ?? null,
            ], 422));
        }

        if ($isAdditional) {
            // Jika ini tambahan, buat slot baru
            $slot = SeragamDistribusi::create([
                'id_biodata' => $idBiodata,
                'id_seragam' => $idSeragam,
                'qty'        => 1, // Asumsi tambahan selalu 1 pcs
                'status'     => 'pending',
                'isAdditional' => 'Y'
            ]);
        }

        $slot->update([
            'id_product_variant' => $variant->id,
            'status'             => 'fulfilled',
            'scanned_at'         => now(),
            'scanned_by'         => auth()->id()
        ]);
    }

    /* =====================================================
     * HELPER: FORMAT ITEM UNTUK VIEW
     * ===================================================== */
    protected function getItems($idBiodata)
    {
        return SeragamDistribusi::with(['seragam', 'productVariant' => function ($q) {
            $q->with(['batches' => function ($q2) {
                $q2->where('posisi', 'store')
                    ->where('qty_sisa', '>', 0)
                    ->orderBy('tanggal_masuk')
                    ->take(1);
            }]);
        }])
            ->where('id_biodata', $idBiodata)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'seragam' => [
                    'nama' => $i->seragam->nama
                ],
                'qty' => $i->qty ?? 1,
                'status' => $i->status,
                'product_variant' => $i->productVariant
            ]);
    }

    /* =====================================================
     * GENERATE SLOT AWAL
     * ===================================================== */
    protected function generateDistribusi(NseCalonSiswa $siswa)
    {
        $items = DB::connection('nsedb')
            ->table('master_seragam')
            ->whereIn('id_divisi', [$siswa->id_divisi, 0])
            ->whereIn('jk', [$siswa->jk === 'Perempuan' ? 'P' : 'L', 'U'])
            ->whereIn('wajib', ['Y', 'N'])
            ->get();

        foreach ($items as $item) {
            SeragamDistribusi::create([
                'id_biodata' => $siswa->id_biodatadiri,
                'id_seragam' => $item->id,
                'status'     => 'pending',
                'qty'        => $item->pcs
            ]);
        }
    }
}
