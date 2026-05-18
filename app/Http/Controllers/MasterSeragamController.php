<?php

namespace App\Http\Controllers;

use App\Models\MasterSeragam;
use App\Models\Product;
use App\Models\UkuranSeragam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterSeragamController extends Controller
{
    public function index()
    {
        $seragams = MasterSeragam::with('divisi', 'ukuranSeragam.product')->orderBy('id_divisi', 'asc')->orderBy('jk', 'asc')->orderBy('hari', 'asc')->get();
        return view('nse.seragam.index', compact('seragams'));
    }

    public function create()
    {
        $divisis = DB::connection('nsedb')
            ->table('master_divisi')
            ->where('has_anggaran', 'Y')
            ->orderBy('id')
            ->get();
        $products = Product::all();

        return view('nse.seragam.create', compact('divisis', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'pcs' => 'required|integer|min:1',
            'jenis' => 'required|in:baju,celana,lengkap,jilbab,dasi,stiker,godybag',
            'id_produk_koperasi' => 'nullable|exists:products,id',
        ]);

        $seragam = MasterSeragam::create([
            'id_divisi' => $request->id_divisi ?? 0,
            'nama' => $request->nama,
            'jk' => $request->jk ?? 'U',
            'hari' => $request->hari ?? 0,
            'jenis' => $request->jenis,
            'pcs' => $request->pcs,
            'pilih' => $request->pilih ?? 'Y',
            'wajib' => $request->wajib ?? 'N',
        ]);

        //jika id_produk_koperasi diisi, maka buatkan ukuran seragam
        if ($request->id_produk_koperasi) {
            UkuranSeragam::updateOrCreate([
                'id_seragam' => $seragam->id,
            ], [
                'id_produk_koperasi' => $request->id_produk_koperasi,
                'size' => $request->size ?? '',
                'aktif' => 'Y',
            ]);
        }

        return redirect()->route('seragam.index')->with('success', 'Data seragam berhasil disimpan');
    }

    public function edit($id)
    {
        $seragam = MasterSeragam::with('ukuranSeragam')->findOrFail($id);
        $divisis = DB::connection('nsedb')->table('master_divisi')->get();
        $products = Product::all();

        return view('nse.seragam.edit', compact('seragam', 'divisis', 'products'));
    }

    public function update(Request $request, $id)
    {
        $seragam = MasterSeragam::with('ukuranSeragam')->findOrFail($id);

        $seragam->update([
            'id_divisi' => $request->id_divisi ?? 0,
            'nama' => $request->nama,
            'jk' => $request->jk ?? 'U',
            'hari' => $request->hari ?? 0,
            'jenis' => $request->jenis,
            'pcs' => $request->pcs,
            'pilih' => $request->pilih ?? 'Y',
            'wajib' => $request->wajib ?? 'N',
        ]);

        //jika id_produk_koperasi diisi, maka buatkan atau update ukuran seragam
        if ($request->id_produk_koperasi) {
            UkuranSeragam::updateOrCreate([
                'id_seragam' => $seragam->id,
            ], [
                'id_produk_koperasi' => $request->id_produk_koperasi,
                'size' => $request->size ?? '',
                'aktif' => 'Y',
            ]);
        } else {
            //jika tidak diisi, hapus ukuran seragam yang ada
            if ($seragam->ukuranSeragam) {
                $seragam->ukuranSeragam->delete();
            }
        }

        return redirect()->route('seragam.index')->with('success', 'Data seragam diperbarui');
    }

    public function destroy($id)
    {
        // hapus ukuran seragam jika ada
        $seragam = MasterSeragam::with('ukuranSeragam')->findOrFail($id);
        if ($seragam->ukuranSeragam) {
            $seragam->ukuranSeragam->delete();
        }
        $seragam->delete();
        return back()->with('success', 'Data seragam dihapus');
    }
}
