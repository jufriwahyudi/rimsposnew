<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RoleMaster;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use DatabaseTransactions;

    protected $store;
    protected $business;
    protected $user;
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        $this->business = Business::create([
            'name' => 'Expense Biz',
            'code' => 'EXBIZ',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'Expense Test Store',
            'code'                 => 'EXTS',
            'is_active'            => true,
            'business_type'        => 'retail',
            'enable_cash_register' => false,
        ]);

        $this->user = User::create([
            'name'     => 'Finance Manager',
            'email'    => 'finance@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->attach($this->store->id);

        $this->role = RoleMaster::create([
            'store_id'              => $this->store->id,
            'nama'                  => 'Finance Admin',
            'role_type'             => 'ADMIN',
            'can_access_all_divisi' => 'Y',
            'stts'                  => 'Y',
        ]);

        $this->actingAs($this->user);
        $this->withSession([
            'store_id'      => $this->store->id,
            'selected_role' => $this->role->id,
        ]);
        \App\Support\Tenant::set($this->store->id);
    }

    public function test_expense_category_crud()
    {
        // 1. Create Category
        $response = $this->postJson(route('expense-categories.store'), [
            'name'        => 'Biaya Utilitas & Listrik',
            'description' => 'Pembayaran tagihan listrik PLN dan PDAM',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $category = ExpenseCategory::where('name', 'Biaya Utilitas & Listrik')->first();
        $this->assertNotNull($category);
        $this->assertEquals($this->store->id, $category->store_id);

        // 2. Update Category
        $updateRes = $this->putJson(route('expense-categories.update', $category->id), [
            'name'        => 'Biaya Listrik & Air Bulanan',
            'description' => 'Tagihan bulanan operasional',
            'is_active'   => true,
        ]);
        $updateRes->assertStatus(200);
        $updateRes->assertJson(['success' => true]);

        $category->refresh();
        $this->assertEquals('Biaya Listrik & Air Bulanan', $category->name);

        // 3. Delete Category
        $deleteRes = $this->deleteJson(route('expense-categories.destroy', $category->id));
        $deleteRes->assertStatus(200);
        $deleteRes->assertJson(['success' => true]);

        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
    }

    public function test_expense_recording_with_partial_and_installment_payment()
    {
        $category = ExpenseCategory::create([
            'store_id'    => $this->store->id,
            'name'        => 'Pemeliharaan Toko',
            'description' => 'Perbaikan fasilitas toko',
            'is_active'   => true,
        ]);

        // 1. Catat Expense baru Rp 500.000 dengan bayar di awal sebagian (Rp 200.000)
        $expenseRes = $this->postJson(route('expenses.store'), [
            'expense_category_id' => $category->id,
            'transaction_date'    => now()->toDateString(),
            'amount'              => 500000,
            'paid_amount'         => 200000,
            'description'         => 'Renovasi Meja Kasir',
            'payment_method'      => 'cash',
            'notes'               => 'DP 200rb, pelunasan minggu depan',
        ]);

        $expenseRes->assertStatus(200);
        $expenseRes->assertJson(['success' => true]);

        $this->assertDatabaseHas('expenses', [
            'store_id'            => $this->store->id,
            'expense_category_id' => $category->id,
            'amount'              => 500000,
            'paid_amount'         => 200000,
            'payment_status'      => 'sebagian',
        ]);

        $expense = Expense::where('store_id', $this->store->id)
            ->where('expense_category_id', $category->id)
            ->first();
        $this->assertNotNull($expense);

        // Verifikasi pembayaran DP tercatat di expense_payments
        $this->assertDatabaseHas('expense_payments', [
            'expense_id'     => $expense->id,
            'amount'         => 200000,
            'payment_method' => 'cash',
        ]);

        // 2. Bayar cicilan pelunasan Rp 300.000 via transfer
        $payRes = $this->postJson(route('expenses.pay', $expense->id), [
            'payment_date'   => now()->toDateString(),
            'amount'         => 300000,
            'payment_method' => 'transfer',
            'notes'          => 'Pelunasan sisa tagihan renovasi',
        ]);

        $payRes->assertStatus(200);
        $payRes->assertJson(['success' => true]);

        // Verifikasi status expense menjadi lunas
        $expense->refresh();
        $this->assertEquals(500000, $expense->paid_amount);
        $this->assertEquals(0, $expense->remaining_amount);
        $this->assertEquals('lunas', $expense->payment_status);

        // 3. Verifikasi show detail endpoint
        $showRes = $this->getJson(route('expenses.show', $expense->id));
        $showRes->assertStatus(200);
        $this->assertEquals('lunas', $showRes->json('payment_status'));
        $this->assertCount(2, $showRes->json('payments'));
    }

    public function test_expense_datatables_endpoint_returns_json_records()
    {
        $category = ExpenseCategory::create([
            'store_id'  => $this->store->id,
            'name'      => 'Logistik',
            'is_active' => true,
        ]);

        Expense::create([
            'store_id'            => $this->store->id,
            'expense_category_id' => $category->id,
            'transaction_date'    => now()->toDateString(),
            'amount'              => 75000,
            'paid_amount'         => 75000,
            'payment_status'      => 'lunas',
            'description'         => 'Beli Kertas Struk POS',
            'payment_method'      => 'cash',
            'user_id'             => $this->user->id,
        ]);

        $response = $this->getJson(route('expenses.datatables'));
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('recordsTotal'));
    }
}
