<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class FrontlinerDepositController extends Controller
{
    protected $id_user_finance;
    protected $kd_akun;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->id_user_finance = auth()->user()->id_user_finance ?? null;
            $this->kd_akun = DB::connection('financedb')->table('a1_user')
                ->where('id', $this->id_user_finance)
                ->value('kd_akun');

            if (!$this->kd_akun) {
                abort(403, 'Akun keuangan tidak ditemukan untuk user ini.');
            }
            return $next($request);
        });
    }
    public function index()
    {
        return view('frontliner.index');
    }
    public function getDataSetoran(Request $request)
    {
        $query = DB::connection('financedb')->table('view_setoran_kasir')
            ->select([
                'Id',
                'tanggal',
                'namakasir',
                'ketdebet',
                'ketkredit',
                'amount',
                'stts'
            ])
            ->where('user_kasir', $this->id_user_finance);

        return DataTables::of($query)
            ->addIndexColumn() // <--- ini untuk kolom No
            ->addColumn('view', function ($row) {
                return $this->optionsetorankasir($row->Id, $row->stts);
            })
            ->rawColumns(['view'])
            ->make(true);
    }
    private function optionsetorankasir($id, $stts)
    {
        $encId = Crypt::encryptString($id);

        $html = '
        <div class="dropdown">
            <span class="bx bx-dots-horizontal-rounded fs-4 dropdown-toggle cursor-pointer"
                data-bs-toggle="dropdown" aria-expanded="false">
            </span>

            <ul class="dropdown-menu dropdown-menu-start">

                <li>
                    <a class="dropdown-item" href="' . url('frontliner/detail/' . $encId . '/index') . '">
                        <i class="bx bx-show me-1"></i> Detail Setoran
                    </a>
                </li>';

        if ($stts === '0') {
            $html .= '
            <li>
                <a class="dropdown-item" href="' . url('frontliner/edit/' . $encId) . '">
                    <i class="bx bx-edit me-1"></i> Edit Setoran
                </a>
            </li>

            <li>
                <a class="dropdown-item text-danger" href="javascript:void(0);" 
                    onclick="hapusPengajuan(\'' . $encId . '\')">
                    <i class="bx bx-trash me-1"></i> Hapus Pengajuan
                </a>
            </li>';
        }

        $html .= '
            </ul>
        </div>';

        return $html;
    }
    public function create()
    {
        $tanggal = date('Y-m-d');
        $unit = '0101'; // Unit default, bisa disesuaikan dengan kebutuhan

        // Saldo Frontliner (posisi_akun_harian)
        $saldo = DB::connection('financedb')->table('posisi_akun_harian')
            ->where('tanggal', '<=', $tanggal)
            ->where('kd_akun', $this->kd_akun)
            ->where('unit', $unit)
            ->orderBy('tanggal', 'desc')
            ->first();

        // Akun kas untuk pilihan setoran
        $kas = DB::connection('financedb')->table('rekening')
            ->where('kode_sub', '11.01')
            ->get();

        // Detail transaksi jurnal
        $detailtrx = DB::connection('financedb')->table('jurnal')
            ->select(
                'jurnal.*',
                'master_jenis_trx.nama as nama_trx'
            )
            ->join('master_jenis_trx', 'jurnal.jns_trx', '=', 'master_jenis_trx.Id')
            ->whereDate('jurnal.tanggal', $saldo->tanggal ?? $tanggal)
            ->where('jurnal.user_input', $this->id_user_finance)
            ->where('jurnal.stts', '1')
            ->where('jurnal.post', 'D')
            ->where('jurnal.kode', $this->kd_akun)
            ->get();

        return view('frontliner.tambah', [
            'title' => 'Setoran Frontliner',
            'data_title' => 'Tambah Setoran',
            'tanggal' => $tanggal,
            'saldo' => $saldo,
            'kas' => $kas,
            'detailtrx' => $detailtrx
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tgltrx' => 'required|date',
            'akundebet' => 'required',
            'amount' => 'required|numeric|min:1',
            'ket' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'msg' => $validator->errors()->first()]);
        }

        $data = [
            'tanggal' => $request->tgltrx,
            'user_kasir' => $this->id_user_finance,
            'akun_debet' => $request->akundebet,
            'akun_kredit' => $this->kd_akun,
            'amount' => $request->amount,
            'catatan' => $request->ket,
            'created_at' => now(),
        ];

        $insert = DB::connection('financedb')->table('setoran_kasir')->insert($data);

        if ($insert) {
            return response()->json(['status' => true, 'msg' => 'Data setoran berhasil disimpan']);
        }

        return response()->json(['status' => false, 'msg' => 'Data setoran gagal disimpan']);
    }

    public function edit($id)
    {
        $decId = Crypt::decryptString($id);

        $data = DB::connection('financedb')->table('setoran_kasir')
            ->where('Id', $decId)
            ->first();

        if (!$data) {
            abort(404, 'Data setoran tidak ditemukan');
        }

        // Akun kas untuk pilihan setoran
        $kas = DB::connection('financedb')->table('rekening')
            ->where('kode_sub', '11.01')
            ->get();

        $saldo = DB::connection('financedb')->table('posisi_akun_harian')
            ->where('tanggal', '<=', $data->tanggal)
            ->where('kd_akun', $this->kd_akun)
            ->where('unit', '0101')
            ->orderBy('tanggal', 'desc')
            ->first();

        return view('frontliner.edit', [
            'title' => 'Edit Setoran Frontliner',
            'data_title' => 'Edit Setoran',
            'data' => $data,
            'kas' => $kas,
            'saldo' => $saldo
        ]);
    }
    public function update(Request $request, $id)
    {
        $decId = Crypt::decryptString($id);

        $validator = Validator::make($request->all(), [
            'tgltrx' => 'required|date',
            'akundebet' => 'required',
            'amount' => 'required|numeric|min:1',
            'ket' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'msg' => $validator->errors()->first()]);
        }

        $data = [
            'tanggal' => $request->tgltrx,
            'akun_debet' => $request->akundebet,
            'amount' => $request->amount,
            'catatan' => $request->ket,
        ];

        $update = DB::connection('financedb')->table('setoran_kasir')
            ->where('Id', $decId)
            ->where('user_kasir', $this->id_user_finance)
            ->update($data);

        if ($update) {
            return response()->json(['status' => true, 'msg' => 'Data setoran berhasil diperbarui']);
        }

        return response()->json(['status' => false, 'msg' => 'Data setoran gagal diperbarui']);
    }
    public function detail($id)
    {
        $decId = Crypt::decryptString($id);

        $data = DB::connection('financedb')->table('view_setoran_kasir')
            ->where('Id', $decId)
            ->first();

        if (!$data) {
            abort(404, 'Data setoran tidak ditemukan');
        }
        // dd($data);
        if ($data->stts === '1') {
            $data->voucher = DB::connection('financedb')->table('view_voucher')->where('Id', $data->nojurnal)->first();
            $data->jurnal = DB::connection('financedb')->select("select p.kode,q.nama,p.post,p.amount from jurnal p, rekening q where p.kode=q.kode and p.ref='" . $data->nojurnal . "'");
        }
        return view('frontliner.detail', [
            'title' => 'Detail Setoran Frontliner',
            'detail' => $data
        ]);
    }
    public function destroy(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $delete = DB::connection('financedb')->table('setoran_kasir')
            ->where('Id', $id)
            ->where('user_kasir', $this->id_user_finance)
            ->delete();

        if ($delete) {
            return response()->json(['status' => true, 'msg' => 'Data setoran berhasil dihapus']);
        }

        return response()->json(['status' => false, 'msg' => 'Data setoran gagal dihapus']);
    }
    public function batalPengajuan(Request $request)
    {
        try {
            // Ambil dan decrypt ID
            $idref = Crypt::decryptString($request->idref);

            // Ambil data setoran
            $data = DB::table('setoran_kasir')->where('Id', $idref)->first();

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data setoran tidak ditemukan'
                ], 404);
            }

            // Update voucher
            DB::table('voucher')
                ->where('Id', $data->nojurnal)
                ->update([
                    'stts' => '2'
                ]);

            // Update setoran kasir
            $update = DB::table('setoran_kasir')
                ->where('Id', $idref)
                ->update([
                    'nojurnal' => 0,
                    'userapv' => 0,
                    'stts' => '0'
                ]);

            if ($update) {
                return response()->json([
                    'status' => true,
                    'message' => 'Approval setoran berhasil dibatalkan'
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Approval setoran gagal diproses'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
