<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyAuditController;
use App\Http\Controllers\FrontlinerDepositController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\MenuListController;
use App\Http\Controllers\NseDistribusiController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\SettingAppController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockOpnamePeriodController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MasterSeragamController;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreSelectionController;
use App\Services\JournalFromCashTransactionService;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/tes', function () {
    return response()->json([
        'store' => [
            'name' => 'RAKAN MART',
            'address' => 'Jl. Tgk Chik Di Tiro, Aceh',
            'phone' => '0812-3456-7890',
        ],
        'transaction' => [
            'invoice' => 'TRX-20260121-0001',
            'date' => now()->format('d/m/Y H:i'),
            'cashier' => 'Admin',
        ],
        'items' => [
            [
                'name' => 'Kopi Arabika Gayo 250gr',
                'qty' => 2,
                'price' => 35000,
            ],
            [
                'name' => 'Teh Hijau Premium',
                'qty' => 1,
                'price' => 18000,
            ],
        ],
        'summary' => [
            'subtotal' => 88000,
            'discount' => 8000,
            'total' => 80000,
            'paid' => 100000,
            'change' => 20000,
        ]
    ]);
});

// Redirect ke SSO
Route::get('/sso/login', [SSOController::class, 'redirectToSSO'])->name('sso.login');
// Callback dari SSO
Route::get('/sso/callback', [SSOController::class, 'handleSSOCallback']);
Route::get('/sso/bypass/{id}', [SSOController::class, 'bypassSSO']);

// Pilih toko setelah login (butuh auth, tapi sebelum store.selected)
Route::middleware(['auth'])->group(function () {
    Route::get('/select-store', [StoreSelectionController::class, 'index'])->name('select-store.index');
    Route::post('/select-store/choose', [StoreSelectionController::class, 'choose'])->name('select-store.choose');
});

Route::middleware(['auth', 'store.selected', 'injectUserData'])->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stock-out', [DashboardController::class, 'stockOut'])->name('dashboard.stockout');
    // Menu Management
    Route::resource('menu', MenuListController::class);
    // Manage Store
    Route::resource('stores', StoreController::class)->except(['create', 'show']);
    Route::post('/stores/{id}/restore', [StoreController::class, 'restore'])->name('stores.restore');

    // Manage User
    Route::controller(ManageUserController::class)->prefix('manage-users')->name('manage-users.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
    });

    // Role Management
    Route::controller(SettingAppController::class)->group(function () {
        Route::get('/role', 'index')->name('role.index');
        Route::post('/roles', 'store')->name('role.store');
        Route::get('/roles/view/{id}', 'showrole')->name('role.show');

        Route::get('/pegawai/search', 'search')->name('pegawai.search');


        Route::delete('/roles/delete/{id}', 'destroyrole')->name('role.delete');
        Route::post('/role/set-session', 'setSessionRole')->name('role.setsession');
        // Route::post('/role/set-session-divisi', 'setSessionDivisi')->name('role.setsessiondivisi');

        Route::post('/roleuser/store', 'storeroleuser')->name('roleuser.store');
        Route::post('/menuuser/store', 'storemenuuser')->name('menuuser.store');
    });
    Route::prefix('settings')->group(function () {
        Route::resource('attributes', AttributeController::class)->except(['create', 'edit', 'show']);
        Route::resource('expense-categories', ExpenseCategoryController::class)->except(['create', 'show']);
    });
    // routes/web.php
    Route::prefix('settings/attribute-nilai')->group(function () {
        Route::get('/', [AttributeValueController::class, 'index'])->name('attribute-nilai.index');
        Route::post('/', [AttributeValueController::class, 'store'])->name('attribute-nilai.store');
        Route::put('/{attributeValue}', [AttributeValueController::class, 'update'])->name('attribute-nilai.update');
        Route::delete('/{attributeValue}', [AttributeValueController::class, 'destroy'])->name('attribute-nilai.destroy');
    });
    Route::get('/produk/datatables', [ProdukController::class, 'datatables'])->name('produk.datatables');
    Route::resource('produk', ProdukController::class);

    // Biaya Operasional
    Route::get('/expenses/datatables', [ExpenseController::class, 'datatables'])->name('expenses.datatables');
    Route::resource('expenses', ExpenseController::class)->except(['create', 'show']);

    Route::get('produk/{product}/variants/{variant}', [ProdukController::class, 'showVariantDetail'])->name('produk.variants.detail');
    Route::delete('produk/variants/{variant}', [ProdukController::class, 'destroyVariant'])->name('produk.variants.destroy');
    Route::put('/produk/variant/update-harga', [ProdukController::class, 'updateHarga'])->name('produk.variants.updateHarga');
    Route::post('/produk/variant/store', [ProdukController::class, 'storeVariant'])->name('produk.variants.store');
    Route::get('/barcode/image/{barcode}', [ProdukController::class, 'barcodeImage'])->name('barcode.image');
    Route::get('/barcode/download/{barcode}', [ProdukController::class, 'barcodeDownload'])->name('barcode.download');
    Route::get('/barcode/label/40x30/{variant}/{isShowPrice?}', [ProdukController::class, 'downloadLabel40x30'])->name('barcode.label.40x30');
    Route::get('/produk/variants/{variant}/generate-barcode', [ProdukController::class, 'generateBarcode'])->name('produk.variant.generate-barcode');
    Route::get('/product/search', [ProdukController::class, 'searchProduct'])->name('produk.search');
    Route::post('/produk/barcode/add/{variant}', [ProdukController::class, 'addBarcodeToVariant'])->name('produk.barcode.add');
    Route::post('/produk/barcode/toggle/{barcode}', [ProdukController::class, 'toggleBarcodeStatus'])->name('produk.barcode.toggle');
    Route::delete('/produk/barcode/{barcode}', [ProdukController::class, 'deleteBarcode'])->name('produk.barcode.delete');


    // purchase orders
    Route::middleware('role.type:ADMIN,WAREHOUSE')->prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('po.index');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('po.create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('po.store');

        Route::post('/{po}/submit', [PurchaseOrderController::class, 'submit'])->name('po.submit');
        Route::delete('/{po}', [PurchaseOrderController::class, 'destroy'])->name('po.destroy');
        // Route::post('/{po}/approve', [PurchaseOrderController::class, 'approve'])->name('po.approve');
        // Route::post('/{po}/reject', [PurchaseOrderController::class, 'reject'])->name('po.reject');
    });
    Route::middleware('role.type:ADMIN,WAREHOUSE')->prefix('goods-receipts')->group(function () {
        Route::get('/{po}', [GoodsReceiptController::class, 'create'])->name('gr.create');
        Route::post('/', [GoodsReceiptController::class, 'store'])->name('gr.store');
        Route::delete('/{gr}', [GoodsReceiptController::class, 'destroy'])->name('gr.destroy');
        Route::get('/{gr}/download-barcodes', [GoodsReceiptController::class, 'downloadBarcodes'])->name('gr.downloadBarcodes');
    });




    // Stock Transfer Routes
    Route::prefix('stock-transfers')->group(function () {
        Route::get('/', [StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('/create', [StockTransferController::class, 'create'])->name('stock-transfers.create')->middleware('role.type:STORE,ADMIN');
        Route::post('/', [StockTransferController::class, 'store'])->name('stock-transfers.store');
        Route::get('/{stockTransfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');

        Route::put('{stockTransfer}/update', [StockTransferController::class, 'updateStatus'])->name('stock-transfers.update-status');

        Route::put('{stockTransfer}/approve', [StockTransferController::class, 'approve'])->name('stock-transfers.approve');
        Route::put('{stockTransfer}/reject', [StockTransferController::class, 'reject'])->name('stock-transfers.reject');
        Route::put('{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');

        Route::put('{stockTransfer}/receive', [StockTransferController::class, 'receive'])->name('stock-transfers.receive');
        Route::put('{stockTransfer}/rollback', [StockTransferController::class, 'rollback'])->name('stock-transfers.rollback');
    });

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/sales', [PosController::class, 'sales'])->name('pos.sales');
    Route::get('/sales/datatables', [PosController::class, 'datatable'])->name('sales.datatables');
    Route::get('/sales/{sale}', [PosController::class, 'show'])->name('sales.show');
    Route::post('/sales/{sale}/void', [PosController::class, 'void'])->name('sales.void');
    Route::post('/sales/{sale}/refund', [PosController::class, 'refund'])->name('sales.refund');
    Route::post('/sales/{sale}/exchange', [PosController::class, 'exchange'])->name('sales.exchange');
    Route::post('/sales/{sale}/print', [PosController::class, 'printThermal'])->name('sales.print-thermal');
    Route::get('/datasales/receiptdata/{id}', [PosController::class, 'printReceipt'])->name('sales.print-receipt');
    Route::get('/sales/{id}/receipt', [PosController::class, 'showReceipt'])->name('sales.receipt');


    Route::prefix('nse/distribusi')->group(function () {
        Route::get('/', [NseDistribusiController::class, 'index'])->name('nse.distribusi.index');
        Route::get('/list', [NseDistribusiController::class, 'listSiswaBelumAmbilSeragam'])->name('nse.distribusi.list-siswa');
        Route::post('/load-siswa', [NseDistribusiController::class, 'loadSiswa']);
        Route::post('/search-siswa', [NseDistribusiController::class, 'searchSiswa']);
        Route::post('/scan-barang', [NseDistribusiController::class, 'scanBarang']);
        Route::post('/confirm-item', [NseDistribusiController::class, 'confirmItem']);
        Route::post('/keep-item', [NseDistribusiController::class, 'keepItem'])->name('nse.distribusi.keep-item');
        Route::delete('/delete-item/{id}', [NseDistribusiController::class, 'deleteItem']);
        Route::post('/checkout', [NseDistribusiController::class, 'checkoutDistribusi']);
        Route::get('/cetak/{id}/{method}', [NseDistribusiController::class, 'cetakpdf'])->name('nse.distribusi.cetak');
        Route::post('/finish', [NseDistribusiController::class, 'finishDistribusi'])->name('nse.distribusi.finish');

        Route::post('/jadwal/aktif', [NseDistribusiController::class, 'jadwalAktif'])->name('nse.distribusi.jadwal-aktif');
        Route::post('/jadwal/book', [NseDistribusiController::class, 'jadwalBook'])->name('nse.distribusi.jadwal-book');
        Route::get('/export_excel', [NseDistribusiController::class, 'exportSiswaBelumAmbilSeragam']);
    });
    Route::resource('nse/seragam', MasterSeragamController::class);


    Route::resource('stock-opname-periods', StockOpnamePeriodController::class);
    Route::post('stock-opname-periods/{stockOpnamePeriod}/close', [StockOpnamePeriodController::class, 'close'])->name('stock-opname-periods.close');
    Route::post('stock-opname-periods/{stockOpnamePeriod}/open', [StockOpnamePeriodController::class, 'open'])->name('stock-opname-periods.open');
    Route::get('stock-opnames/create/{period}', [StockOpnameController::class, 'create'])->name('stock-opnames.create');
    Route::post('stock-opnames/{period}', [StockOpnameController::class, 'store'])->name('stock-opnames.store');
    Route::get('stock-opnames/{stockOpname}/edit', [StockOpnameController::class, 'edit'])->name('stock-opnames.edit');
    Route::put('stock-opnames/{stockOpnameItem}', [StockOpnameController::class, 'update'])->name('stock-opnames.update');
    Route::get('stock-opnames/{stockOpname}', [StockOpnameController::class, 'show'])->name('stock-opnames.show');
    Route::post('stock-opnames/{stockOpname}/approve', [StockOpnameController::class, 'approve'])->name('stock-opnames.approve');
    Route::post('stock-opnames/{stockOpname}/cancel', [StockOpnameController::class, 'cancel'])->name('stock-opnames.cancel');

    Route::prefix('stock-adjustments')->group(function () {
        Route::get('/', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::get('/hist_posted', [StockAdjustmentController::class, 'historyPosted'])->name('stock-adjustments.history-posted');
        Route::get('{stockAdjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
        Route::post('{stockAdjustment}/post', [StockAdjustmentController::class, 'post'])->name('stock-adjustments.post');
        Route::put('{stockAdjustmentItem}/update-item-cost', [StockAdjustmentController::class, 'updateItemCost'])->name('stock-adjustments.update-item-cost');
    });

    Route::prefix('audits')->name('audits.')->group(function () {
        Route::get('/', [DailyAuditController::class, 'index'])->name('index');
        Route::get('/create', [DailyAuditController::class, 'create'])->name('create');
        Route::post('/', [DailyAuditController::class, 'store'])->name('store');
        Route::get('/{dailyAudit}', [DailyAuditController::class, 'show'])->name('show');
    });

    #LAPORAN
    Route::get('/laporan/penjualan', [LaporanController::class, 'penjualan'])->name('laporan.penjualan');
    Route::post('/laporan/penjualan/data', [LaporanController::class, 'getpenjualan'])->name('laporanpenjualan.getpenjualan');
    Route::get('/laporan/penjualan/export', [LaporanController::class, 'exportPenjualan'])->name('laporanpenjualan.export');

    Route::get('/laporan/penjualanNSE', [LaporanController::class, 'penjualanNSE'])->name('laporan.penjualanNSE');
    Route::post('/laporan/penjualanNSE/data', [LaporanController::class, 'getpenjualanNSE'])->name('laporanpenjualanNSE.getpenjualanNSE');
    Route::get('/laporan/penjualanNSE/export', [LaporanController::class, 'exportPenjualanNSE'])->name('laporanpenjualanNSE.export');

    Route::get('/laporan/stok', [LaporanController::class, 'stok'])->name('laporan.stok');
    Route::post('/laporan/stok', [LaporanController::class, 'searchstock'])->name('laporan.stok.search');

    Route::get('/laporan/pembelian', [LaporanController::class, 'pembelian'])->name('laporan.pembelian');
    Route::get('/laporan/harian', [LaporanController::class, 'harian'])->name('laporan.harian');
    Route::post('/laporan/harian/data', [LaporanController::class, 'getharian'])->name('laporan.harian.data');
    Route::get('/laporan/harian/export', [LaporanController::class, 'exportHarian'])->name('laporan.harian.export');
    Route::get('/laporan/penerimaan-kas', [LaporanController::class, 'penerimaanKasFrontliner'])->name('laporan.penerimaan_kas');
    Route::post('/laporan/penerimaan-kas/data', [LaporanController::class, 'getPenerimaanKas'])->name('laporan.penerimaan_kas.data');
    Route::get('/laporan/penerimaan-kas/export', [LaporanController::class, 'exportPenerimaanKas'])->name('laporan.penerimaan_kas.export');
    Route::get('/laporan/neraca_lajur', [LaporanController::class, 'neraca_lajur'])->name('laporan.neraca_lajur');
    Route::get('/laporan/laba_rugi', [LaporanController::class, 'laba_rugi'])->name('laporan.laba_rugi');

    Route::get('/laporan/biaya-operasional', [LaporanController::class, 'biayaOperasional'])->name('laporan.biaya_operasional');
    Route::post('/laporan/biaya-operasional/data', [LaporanController::class, 'getBiayaOperasional'])->name('laporan.biaya_operasional.data');
    Route::get('/laporan/biaya-operasional/export', [LaporanController::class, 'exportBiayaOperasional'])->name('laporan.biaya_operasional.export');

    #export excel laporan stok
    Route::get('/laporan-stok/excel', [LaporanController::class, 'exportExcel'])
        ->name('laporan.stok.excel');

    #export pdf penerimaan kas
    Route::get('/cetakkaspdf/{tanggal}/{user_id?}', [LaporanController::class, 'cetakPenerimaanKas'])->name('laporan.cetak_penerimaan_kas');

    #JADWAL
    Route::get('/jadwal/index', [JadwalController::class, 'index']);
    Route::get('/jadwal/list', [JadwalController::class, 'list'])->name('jadwal.list');
    Route::post('/jadwal/store', [JadwalController::class, 'store'])->name('jadwal.store');


    Route::get('/frontliner', [FrontlinerDepositController::class, 'index'])->name('frontliner.index');
    Route::get('/frontliner/setoran/data', [FrontlinerDepositController::class, 'getDataSetoran'])->name('frontliner.setoran.data');
    Route::get('/frontliner/setoran/tambah', [FrontlinerDepositController::class, 'create'])->name('frontliner.create');
    Route::post('/frontliner/setoran', [FrontlinerDepositController::class, 'store'])->name('frontliner.store');
    Route::get('/frontliner/detail/{id}/index', [FrontlinerDepositController::class, 'detail'])->name('frontliner.detail');
    Route::post('/frontliner/setoran/hapus', [FrontlinerDepositController::class, 'destroy'])->name('frontliner.hapus');
    Route::post('/frontliner/setoran/batalpengajuan', [FrontlinerDepositController::class, 'batalPengajuan'])->name('frontliner.hapus.approval');
    Route::get('/frontliner/edit/{id}', [FrontlinerDepositController::class, 'edit'])->name('frontliner.edit');
    Route::post('/frontliner/update/{id}', [FrontlinerDepositController::class, 'update'])->name('frontliner.update');
});

// pemilihan jadwal distribusi NSE untuk orang tua/wali siswa
Route::get('/jadwalnse/{id?}', function ($id = null) {
    $siswa = null;
    if ($id) {
        $siswa = \App\Models\NseCalonSiswa::with(['divisi:id,nama', 'jadwalDistribusi.jadwal.sesi'])
            ->whereHas('daftarUlang', fn($q) => $q->where('stts_byr', 'Y'))
            ->whereIn('voucher_seragam', ['Y', 'N'])
            ->whereIn('ambil_seragam', ['S', 'N'])
            ->where('id_biodatadiri', $id)
            ->first();
        if ($siswa) {
            $siswa = [
                'id_biodatadiri' => $siswa->id_biodatadiri,
                'nama_lengkap'   => $siswa->nama_lengkap,
                'nik'            => $siswa->nik,
                'divisi'         => $siswa->divisi->nama ?? '-',
                'jadwal'         => $siswa->jadwalDistribusi ? [
                    'tanggal' => $siswa->jadwalDistribusi->jadwal->tanggal ?? null,
                    'sesi'    => $siswa->jadwalDistribusi->sesi ? [
                        'jam_mulai'   => $siswa->jadwalDistribusi->sesi->jam_mulai,
                        'jam_selesai' => $siswa->jadwalDistribusi->sesi->jam_selesai,
                    ] : null
                ] : null
            ];
        }
    }
    return view('nse.otm.index', compact('siswa'));
})->name('nse.jadwal');
Route::post('/jadwalnse/search', [NseDistribusiController::class, 'searchSiswaPublic'])->name('nse.otm.search');
Route::post('/jadwalnse/aktif', [NseDistribusiController::class, 'jadwalAktif'])->name('nse.distribusi.jadwal.aktif');
Route::post('/jadwalnse/book', [NseDistribusiController::class, 'jadwalBook'])->name('nse.distribusi.jadwal.book');


Route::middleware('auth')->get('/unauthorized', function () {
    return view('unauthorized');
})->name('unauthorized');

Route::get('/test-service', function () {
    $service = new JournalFromCashTransactionService();
    $service->createForExchange(101);

    return 'OK';
});
