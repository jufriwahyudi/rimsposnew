<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Rekening;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use App\Services\CashRegisterService;
use App\Services\Printer\EscPosReceiptService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CashRegisterReportTest extends TestCase
{
    use DatabaseTransactions;

    protected CashRegisterService $service;
    protected Store $store;
    protected User $user;
    protected CashRegister $register;
    protected Rekening $rekening;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CashRegisterService();

        $this->store = Store::create([
            'name' => 'DKRIUK FRIED CHICKEN',
            'code' => 'DKR-' . rand(100, 999),
            'address' => 'Jln Iskandar Muda No 8 Alun Alun Sigli',
            'phone' => '08123456789',
            'is_active' => true,
            'enable_cash_register' => true,
        ]);

        $this->user = User::create([
            'name' => 'Nora',
            'username' => 'nora_' . rand(100, 999),
            'email' => 'nora_' . rand(100, 999) . '@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->rekening = Rekening::create([
            'store_id' => $this->store->id,
            'bank_rek' => 'QRIS',
            'nama_rek' => 'DKRIUK',
            'no_rek'   => '1234567890',
        ]);

        // Open Register with Opening Cash Rp 500.000
        $this->register = $this->service->openRegister(
            $this->store->id,
            $this->user->id,
            500000.0,
            'Shift Pagi'
        );
    }

    public function test_get_shift_report_data_calculates_financials_and_menu_sales_accurately()
    {
        // 1. Create Sale 1 (Cash: 677.000)
        $sale1 = Sale::create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'cash_register_id' => $this->register->id,
            'invoice_number' => 'INV-CASH-001',
            'sale_date' => now(),
            'subtotal' => 677000,
            'grand_total' => 677000,
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);

        // Items for Sale 1
        SaleItem::create([
            'sale_id' => $sale1->id,
            'sku' => 'SKU-001',
            'product_name' => 'AYAM DADA ORI/HOT',
            'price' => 15000,
            'qty' => 12,
            'subtotal' => 180000,
        ]);
        SaleItem::create([
            'sale_id' => $sale1->id,
            'sku' => 'SKU-002',
            'product_name' => 'KULIT',
            'price' => 10000,
            'qty' => 18,
            'subtotal' => 180000,
        ]);

        // Record CashTransaction for Sale 1
        CashTransaction::create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'cash_register_id' => $this->register->id,
            'ref_type' => 'Sale',
            'ref_id' => $sale1->id,
            'transaction_type' => 'sale',
            'payment_method' => 'cash',
            'account_code' => 'cash',
            'amount' => 677000,
            'direction' => 'in',
            'transaction_date' => now(),
        ]);

        // 2. Create Sale 2 (Non-Cash / QRIS: 42.000)
        $sale2 = Sale::create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'cash_register_id' => $this->register->id,
            'invoice_number' => 'INV-QRIS-002',
            'sale_date' => now(),
            'subtotal' => 42000,
            'grand_total' => 42000,
            'payment_method' => 'transfer',
            'status' => 'paid',
        ]);

        // Items for Sale 2
        SaleItem::create([
            'sale_id' => $sale2->id,
            'sku' => 'SKU-003',
            'product_name' => 'AIR MINERAL',
            'price' => 5000,
            'qty' => 3,
            'subtotal' => 15000,
        ]);
        SaleItem::create([
            'sale_id' => $sale2->id,
            'sku' => 'SKU-004',
            'product_name' => 'NASI',
            'price' => 5000,
            'qty' => 13,
            'subtotal' => 65000,
        ]);

        // Record CashTransaction for Sale 2
        CashTransaction::create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'cash_register_id' => $this->register->id,
            'ref_type' => 'Sale',
            'ref_id' => $sale2->id,
            'transaction_type' => 'sale',
            'payment_method' => 'transfer',
            'account_code' => $this->rekening->id,
            'amount' => 42000,
            'direction' => 'in',
            'transaction_date' => now(),
        ]);

        // Fetch report data
        $reportData = $this->service->getShiftReportData($this->register);

        // Verify Header & Shift Data
        $this->assertEquals('DKRIUK FRIED CHICKEN', $reportData['store']['name']);
        $this->assertEquals('Nora', $reportData['shift']['cashier_name']);

        // Verify Financials
        $this->assertEquals(500000.0, $reportData['financial']['opening_cash']);
        $this->assertEquals(677000.0, $reportData['financial']['cash_sales']);
        $this->assertEquals(42000.0, $reportData['financial']['non_cash_sales']);
        $this->assertEquals(719000.0, $reportData['financial']['total_received']);
        $this->assertEquals(1177000.0, $reportData['financial']['final_cash_balance']); // 500k + 677k
        $this->assertEquals(2, $reportData['financial']['completed_sales']);
        $this->assertEquals(0, $reportData['financial']['unpaid_sales']);

        // Verify Non-Cash Breakdown
        $this->assertNotEmpty($reportData['financial']['non_cash_breakdown']);
        $this->assertStringContainsString('QRIS', $reportData['financial']['non_cash_breakdown'][0]['name']);
        $this->assertEquals(42000.0, $reportData['financial']['non_cash_breakdown'][0]['amount']);

        // Verify Menu Sales Breakdown (12 + 18 + 3 + 13 = 46 items)
        $this->assertEquals(46.0, $reportData['menu_sales']['total_qty']);
        $itemNames = array_column($reportData['menu_sales']['items'], 'name');
        $this->assertContains('AYAM DADA ORI/HOT', $itemNames);
        $this->assertContains('KULIT', $itemNames);
        $this->assertContains('AIR MINERAL', $itemNames);
        $this->assertContains('NASI', $itemNames);
    }

    public function test_escpos_full_shift_report_generates_valid_base64_payload()
    {
        $reportData = $this->service->getShiftReportData($this->register);

        $escposService = new EscPosReceiptService('58mm');
        $b64 = $escposService->fullShiftReportBase64($reportData);

        $this->assertNotEmpty($b64);
        $decoded = base64_decode($b64);
        $this->assertNotEmpty($decoded);
        $this->assertStringContainsString('LAPORAN TUTUP KASIR', $decoded);
        $this->assertStringContainsString('TRANSAKSI PENJUALAN', $decoded);
        $this->assertStringContainsString('PENJUALAN MENU', $decoded);
    }

    public function test_api_receipt_report_endpoint()
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ])->getJson('/api/pos/cash-register/receipt-report?store_id=' . $this->store->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'report_data',
                'base64',
            ]);
    }
}
