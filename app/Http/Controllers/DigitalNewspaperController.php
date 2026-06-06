<?php

namespace App\Http\Controllers;

use App\Models\DigitalNewspaper;
use Illuminate\Http\Request;

class DigitalNewspaperController extends Controller
{
    /**
     * Display a listing of the digital newspapers.
     */
    public function index()
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('dashboard')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        $newspapers = DigitalNewspaper::where('store_id', $storeId)
            ->orderBy('report_date', 'desc')
            ->paginate(15);

        return view('newspaper.index', compact('newspapers'));
    }

    /**
     * Display the specified digital newspaper.
     */
    public function show($id)
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('dashboard')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        $newspaper = DigitalNewspaper::where('store_id', $storeId)
            ->findOrFail($id);

        return view('newspaper.show', compact('newspaper'));
    }
}
