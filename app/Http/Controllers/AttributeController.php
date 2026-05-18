<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index()
    {
        $storeId = session('store_id');
        $attributes = Attribute::where('store_id', $storeId)->orderBy('nama')->get();
        return view('pengaturan.attributes.index', compact('attributes'));
    }

    public function store(Request $request)
    {
        $storeId = session('store_id');
        $request->validate([
            'kode' => 'required|alpha_dash|unique:attributes,kode',
            'nama' => 'required|string|max:100',
        ]);

        Attribute::create([
            'store_id' => $storeId,
            'kode' => strtolower($request->kode),
            'nama' => $request->nama,
        ]);

        return back()->with('success', 'Attribute berhasil ditambahkan');
    }

    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'kode' => 'required|alpha_dash|unique:attributes,kode,' . $attribute->id,
            'nama' => 'required|string|max:100',
        ]);

        $attribute->update([
            'kode' => strtolower($request->kode),
            'nama' => $request->nama,
        ]);

        return back()->with('success', 'Attribute berhasil diperbarui');
    }

    public function destroy(Attribute $attribute)
    {
        if ($attribute->values()->exists()) {
            return back()->with('error', 'Attribute masih memiliki nilai');
        }

        $attribute->delete();
        return back()->with('success', 'Attribute berhasil dihapus');
    }
}
