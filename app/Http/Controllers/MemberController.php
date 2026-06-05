<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Store;
use App\Models\PointSetting;
use App\Models\MemberPointHistory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MemberController extends Controller
{
    /**
     * Display a listing of members.
     */
    public function index(Request $request)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        if ($request->ajax()) {
            $members = Member::where('business_id', $businessId)->orderBy('name');
            return DataTables::of($members)
                ->addColumn('action', function ($member) {
                    return '
                        <a href="' . route('members.history', $member->id) . '" class="btn btn-sm btn-info text-white" title="Riwayat Poin">
                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">history</i>
                        </a>
                        <button class="btn btn-sm btn-warning btn-edit-member" data-id="' . $member->id . '"
                            data-name="' . htmlspecialchars($member->name) . '"
                            data-phone="' . htmlspecialchars($member->phone) . '"
                            data-email="' . htmlspecialchars($member->email) . '"
                            data-birth_date="' . ($member->birth_date ? $member->birth_date->format('Y-m-d') : '') . '">
                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">edit</i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-delete-member" data-id="' . $member->id . '">
                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">delete</i>
                        </button>
                    ';
                })
                ->editColumn('birth_date', function ($member) {
                    return $member->birth_date ? $member->birth_date->format('d M Y') : '-';
                })
                ->editColumn('total_points', function ($member) {
                    return number_format($member->total_points);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('members.index');
    }

    /**
     * Store a newly created member in storage.
     */
    public function store(Request $request)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'birth_date' => 'nullable|date',
        ]);

        // Check if member already exists with the same phone in this business
        $exist = Member::where('business_id', $businessId)
            ->where('phone', $request->phone)
            ->exists();

        if ($exist) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor handphone member sudah terdaftar.',
            ], 422);
        }

        $member = Member::create([
            'business_id' => $businessId,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'birth_date' => $request->birth_date,
            'total_points' => 0,
        ]);

        // Award Welcome Points if configured and settings are active
        $settings = PointSetting::where('business_id', $businessId)->whereNull('store_id')->first();
        if ($settings && $settings->welcome_points > 0 && $settings->is_active) {
            $member->increment('total_points', $settings->welcome_points);
            
            MemberPointHistory::create([
                'member_id' => $member->id,
                'store_id' => $storeId,
                'sale_id' => null,
                'mutation_type' => 'adjust',
                'points' => $settings->welcome_points,
                'balance_after' => $member->total_points,
                'notes' => 'Bonus pendaftaran member baru (Welcome Points)',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil ditambahkan.',
        ]);
    }

    /**
     * Update the specified member in storage.
     */
    public function update(Request $request, $id)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'birth_date' => 'nullable|date',
        ]);

        // Check unique phone number per business
        $exist = Member::where('business_id', $businessId)
            ->where('phone', $request->phone)
            ->where('id', '!=', $id)
            ->exists();

        if ($exist) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor handphone sudah digunakan oleh member lain.',
            ], 422);
        }

        $member = Member::where('business_id', $businessId)->findOrFail($id);
        $member->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'birth_date' => $request->birth_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data member berhasil diubah.',
        ]);
    }

    /**
     * Remove the specified member from storage.
     */
    public function destroy($id)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        $member = Member::where('business_id', $businessId)->findOrFail($id);
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil dihapus.',
        ]);
    }

    /**
     * View member's point mutation history.
     */
    public function history(Request $request, $id)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        $member = Member::where('business_id', $businessId)->findOrFail($id);

        if ($request->ajax()) {
            $histories = MemberPointHistory::with(['store', 'sale'])
                ->where('member_id', $member->id)
                ->orderBy('created_at', 'desc');

            return DataTables::of($histories)
                ->editColumn('created_at', function ($h) {
                    return $h->created_at->format('d M Y H:i:s');
                })
                ->editColumn('store_name', function ($h) {
                    return $h->store->name ?? '-';
                })
                ->editColumn('sale_invoice', function ($h) {
                    if ($h->sale) {
                        return '<a href="' . route('sales.show', $h->sale_id) . '" target="_blank">' . $h->sale->invoice_number . '</a>';
                    }
                    return '-';
                })
                ->editColumn('points', function ($h) {
                    $color = $h->points > 0 ? 'text-success' : 'text-danger';
                    $prefix = $h->points > 0 ? '+' : '';
                    return '<span class="fw-bold ' . $color . '">' . $prefix . $h->points . '</span>';
                })
                ->rawColumns(['sale_invoice', 'points'])
                ->make(true);
        }

        return view('members.history', compact('member'));
    }
}
