<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryStock;
use App\Models\IngredientStockMovement;
use App\Models\Unit;
use App\Services\IngredientInventoryService;
use Illuminate\Http\Request;

class IngredientStockController extends Controller
{
    protected IngredientInventoryService $stockService;

    public function __construct(IngredientInventoryService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('select-store.index')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        $ingredients = Ingredient::where('store_id', $storeId)
            ->with(['baseUnit', 'conversions.purchaseUnit'])
            ->get();

        // Load stocks for each ingredient
        $stocks = [];
        foreach ($ingredients as $ingredient) {
            $warehouseStock = InventoryStock::where([
                'ingredient_id' => $ingredient->id,
                'location_type' => 'WAREHOUSE',
                'location_id'   => $storeId,
            ])->value('quantity') ?? 0.0000;

            $storeStock = InventoryStock::where([
                'ingredient_id' => $ingredient->id,
                'location_type' => 'STORE',
                'location_id'   => $storeId,
            ])->value('quantity') ?? 0.0000;

            $stocks[$ingredient->id] = [
                'warehouse' => $warehouseStock,
                'store'     => $storeStock,
            ];
        }

        // Get recent movements
        $movements = IngredientStockMovement::whereIn('ingredient_id', $ingredients->pluck('id'))
            ->with('ingredient.baseUnit')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $units = Unit::all();

        return view('ingredient_stocks.index', compact('ingredients', 'stocks', 'movements', 'units'));
    }

    public function stockIn(Request $request)
    {
        $storeId = session('store_id');
        $request->validate([
            'ingredient_id'    => 'required|exists:ingredients,id',
            'conversion_id'    => 'required|string',
            'qty_purchased'    => 'required|numeric|min:0.0001',
            'tanggal'          => 'required|date',
            'notes'            => 'nullable|string|max:255',
        ]);

        try {
            $tanggal = $request->tanggal . ' ' . now()->format('H:i:s');
            $conversionId = $request->conversion_id === 'base' ? null : (int) $request->conversion_id;

            $this->stockService->purchaseStock(
                $request->ingredient_id,
                $conversionId,
                (float) $request->qty_purchased,
                $storeId,
                null,
                $request->notes,
                $tanggal
            );

            return redirect()->route('ingredient-stocks.index')->with('success', 'Stok masuk berhasil disimpan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses stok masuk: ' . $e->getMessage());
        }
    }

    public function transferForm()
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('select-store.index')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        $ingredients = Ingredient::where('store_id', $storeId)
            ->with('baseUnit')
            ->get();

        $stocks = [];
        foreach ($ingredients as $ingredient) {
            $stocks[$ingredient->id] = InventoryStock::where([
                'ingredient_id' => $ingredient->id,
                'location_type' => 'WAREHOUSE',
                'location_id'   => $storeId,
            ])->value('quantity') ?? 0.0000;
        }

        return view('ingredient_stocks.transfer', compact('ingredients', 'stocks'));
    }

    public function transfer(Request $request)
    {
        $storeId = session('store_id');
        $request->validate([
            'ingredient_id'   => 'required|exists:ingredients,id',
            'qty_to_transfer' => 'required|numeric|min:0.0001',
            'notes'           => 'nullable|string|max:255',
        ]);

        try {
            $this->stockService->transferStock(
                $request->ingredient_id,
                (float) $request->qty_to_transfer,
                $storeId,
                $storeId,
                null,
                $request->notes
            );

            return redirect()->route('ingredient-stocks.index')->with('success', 'Transfer stok ke Toko berhasil diproses.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function adjustForm()
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('select-store.index')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        $ingredients = Ingredient::where('store_id', $storeId)
            ->with('baseUnit')
            ->get();

        $stocks = [];
        foreach ($ingredients as $ingredient) {
            $warehouseStock = InventoryStock::where([
                'ingredient_id' => $ingredient->id,
                'location_type' => 'WAREHOUSE',
                'location_id'   => $storeId,
            ])->value('quantity') ?? 0.0000;

            $storeStock = InventoryStock::where([
                'ingredient_id' => $ingredient->id,
                'location_type' => 'STORE',
                'location_id'   => $storeId,
            ])->value('quantity') ?? 0.0000;

            $stocks[$ingredient->id] = [
                'WAREHOUSE' => $warehouseStock,
                'STORE'     => $storeStock,
            ];
        }

        return view('ingredient_stocks.adjust', compact('ingredients', 'stocks'));
    }

    public function adjust(Request $request)
    {
        $storeId = session('store_id');
        $request->validate([
            'ingredient_id'   => 'required|exists:ingredients,id',
            'location_type'   => 'required|in:WAREHOUSE,STORE',
            'actual_quantity' => 'required|numeric|min:0',
            'notes'           => 'required|string|max:255',
        ]);

        try {
            $this->stockService->adjustStock(
                $request->ingredient_id,
                (float) $request->actual_quantity,
                $storeId,
                $request->location_type,
                $request->notes
            );

            return redirect()->route('ingredient-stocks.index')->with('success', 'Penyesuaian stok / opname berhasil disimpan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses penyesuaian stok: ' . $e->getMessage());
        }
    }

    public function report(Request $request)
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('select-store.index')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        $startDate = $request->input('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $selectedIngredientId = $request->input('ingredient_id');
        $selectedLocationType = $request->input('location_type');

        $ingredients = Ingredient::where('store_id', $storeId)->get();

        $query = IngredientStockMovement::whereIn('ingredient_id', $ingredients->pluck('id'))
            ->whereBetween('tanggal', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($selectedIngredientId) {
            $query->where('ingredient_id', $selectedIngredientId);
        }

        if ($selectedLocationType) {
            $query->where('location_type', $selectedLocationType);
        }

        $movements = $query->with('ingredient.baseUnit')
            ->orderBy('tanggal', 'desc')
            ->get();

        // Calculate summaries for the period
        $summary = [
            'total_purchase' => (float) $movements->where('type', 'PURCHASE')->sum('quantity_change'),
            'total_sale'     => (float) abs($movements->where('type', 'SALE')->sum('quantity_change')),
            'total_wastage'  => (float) abs($movements->where('type', 'WASTAGE')->sum('quantity_change')),
            'total_transfer' => (float) $movements->where('type', 'TRANSFER_IN')->sum('quantity_change'),
        ];

        return view('ingredient_stocks.report', compact(
            'ingredients',
            'movements',
            'startDate',
            'endDate',
            'selectedIngredientId',
            'selectedLocationType',
            'summary'
        ));
    }
}
