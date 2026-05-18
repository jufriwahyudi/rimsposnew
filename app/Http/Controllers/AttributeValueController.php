<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index(Request $request)
    {
        $attributes = Attribute::orderBy('nama')->get();
        $attributeId = $request->attribute_id;

        $values = AttributeValue::when($attributeId, function ($q) use ($attributeId) {
            $q->where('attribute_id', $attributeId);
        })->orderByDesc('id')->get();

        return view('pengaturan.attributes.nilai', compact(
            'attributes',
            'values',
            'attributeId'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'attribute_id' => 'required|exists:attributes,id',
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:100',
        ]);

        AttributeValue::create([
            'attribute_id' => $request->attribute_id,
            'kode' => strtoupper($request->kode),
            'nama' => $request->nama,
        ]);

        return back()->with('success', 'Nilai attribute ditambahkan');
    }

    public function update(Request $request, AttributeValue $attributeValue)
    {
        $request->validate([
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:100',
        ]);

        $attributeValue->update([
            'kode' => strtoupper($request->kode),
            'nama' => $request->nama,
        ]);

        return back()->with('success', 'Nilai attribute diperbarui');
    }

    public function destroy(AttributeValue $attributeValue)
    {
        $attributeValue->delete();
        return back()->with('success', 'Nilai attribute dihapus');
    }
}
