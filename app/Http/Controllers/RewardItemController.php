<?php

namespace App\Http\Controllers;

use App\Models\RewardItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RewardItemController extends Controller
{
    public function index(Request $request)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        if ($request->ajax()) {
            $rewards = RewardItem::where('business_id', $businessId)->orderBy('name');
            return DataTables::of($rewards)
                ->addColumn('action', function ($reward) {
                    return '
                        <button class="btn btn-sm btn-warning btn-edit-reward" data-id="' . $reward->id . '"
                            data-name="' . htmlspecialchars($reward->name) . '"
                            data-points_required="' . $reward->points_required . '"
                            data-reward_type="' . $reward->reward_type . '"
                            data-value="' . (float)$reward->value . '"
                            data-max_discount="' . ($reward->max_discount !== null ? (float)$reward->max_discount : '') . '"
                            data-stock="' . ($reward->stock ?? '') . '"
                            data-is_active="' . ($reward->is_active ? '1' : '0') . '">
                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">edit</i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-delete-reward" data-id="' . $reward->id . '">
                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">delete</i>
                        </button>
                    ';
                })
                ->editColumn('reward_type', function ($reward) {
                    return match ($reward->reward_type) {
                        'physical' => '<span class="badge bg-primary">Barang Fisik</span>',
                        'voucher_percent' => '<span class="badge bg-success">Voucher Diskon (%)</span>',
                        'voucher_nominal' => '<span class="badge bg-info">Voucher Potongan (Rp)</span>',
                        default => $reward->reward_type
                    };
                })
                ->editColumn('value', function ($reward) {
                    if ($reward->reward_type === 'voucher_percent') {
                        $val = (float)$reward->value . ' %';
                        if ($reward->max_discount > 0) {
                            $val .= ' (Maks Rp ' . number_format($reward->max_discount, 0, ',', '.') . ')';
                        }
                        return $val;
                    } elseif ($reward->reward_type === 'voucher_nominal') {
                        return 'Rp ' . number_format($reward->value, 0, ',', '.');
                    }
                    return '-';
                })
                ->editColumn('stock', function ($reward) {
                    if ($reward->reward_type === 'physical') {
                        return $reward->stock !== null ? number_format($reward->stock) : '∞';
                    }
                    return '∞';
                })
                ->editColumn('is_active', function ($reward) {
                    return $reward->is_active
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Non-aktif</span>';
                })
                ->rawColumns(['action', 'reward_type', 'is_active'])
                ->make(true);
        }

        return view('rewards.index');
    }

    public function store(Request $request)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        $request->validate([
            'name' => 'required|string|max:150',
            'points_required' => 'required|integer|min:1',
            'reward_type' => 'required|in:physical,voucher_percent,voucher_nominal',
            'value' => 'nullable|required_if:reward_type,voucher_percent,voucher_nominal|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        RewardItem::create([
            'business_id' => $businessId,
            'name' => $request->name,
            'points_required' => $request->points_required,
            'reward_type' => $request->reward_type,
            'value' => $request->value,
            'max_discount' => $request->reward_type === 'voucher_percent' ? $request->max_discount : null,
            'stock' => $request->reward_type === 'physical' ? $request->stock : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Barang penukaran berhasil ditambahkan.',
        ]);
    }

    public function update(Request $request, $id)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        $reward = RewardItem::where('business_id', $businessId)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:150',
            'points_required' => 'required|integer|min:1',
            'reward_type' => 'required|in:physical,voucher_percent,voucher_nominal',
            'value' => 'nullable|required_if:reward_type,voucher_percent,voucher_nominal|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $reward->update([
            'name' => $request->name,
            'points_required' => $request->points_required,
            'reward_type' => $request->reward_type,
            'value' => $request->value,
            'max_discount' => $request->reward_type === 'voucher_percent' ? $request->max_discount : null,
            'stock' => $request->reward_type === 'physical' ? $request->stock : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Barang penukaran berhasil diperbarui.',
        ]);
    }

    public function destroy($id)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        $reward = RewardItem::where('business_id', $businessId)->findOrFail($id);
        $reward->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang penukaran berhasil dihapus.',
        ]);
    }
}
