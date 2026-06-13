<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\RoleMaster;
use App\Models\Member;
use App\Models\RewardItem;
use App\Models\MemberRedemption;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LaporanPenukaranPoinTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $role;
    protected $store;
    protected $member;
    protected $reward;
    protected $redemption;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a Business
        $business = \App\Models\Business::create([
            'name' => 'Biz Test Laporan Poin',
            'code' => 'BTLP01',
        ]);

        // 2. Create a Store
        $this->store = Store::create([
            'business_id' => $business->id,
            'name' => 'Toko Laporan Poin',
            'code' => 'TLP01',
            'is_active' => 1,
        ]);
        \App\Support\Tenant::set($this->store->id);

        // 3. Create User
        $this->user = User::create([
            'name' => 'Admin Test Laporan Poin',
            'email' => 'admin.poin@example.com',
            'password' => bcrypt('password'),
            'store_id' => $this->store->id,
        ]);

        // 4. Create RoleMaster
        $this->role = RoleMaster::create([
            'nama' => 'Admin Laporan Poin',
            'role_type' => 'ADMIN',
            'stts' => 'Y',
        ]);

        // 5. Authenticate user
        $this->actingAs($this->user);
        $this->withSession([
            'selected_role' => $this->role->id,
            'store_id' => $this->store->id,
            'store_name' => $this->store->name,
        ]);

        // 6. Create Member
        $this->member = Member::create([
            'business_id' => $business->id,
            'name' => 'Budi Member Poin',
            'phone' => '08777777777',
            'email' => 'budi@poin.com',
            'total_points' => 200,
            'is_active' => true,
        ]);

        // 7. Create Reward Item
        $this->reward = RewardItem::create([
            'business_id' => $business->id,
            'name' => 'Tumbler Premium',
            'points_required' => 50,
            'reward_type' => 'physical',
            'stock' => 10,
            'is_active' => true,
        ]);

        // 8. Create Member Redemption Log
        $this->redemption = MemberRedemption::create([
            'member_id' => $this->member->id,
            'reward_item_id' => $this->reward->id,
            'store_id' => $this->store->id,
            'points_spent' => 50,
            'voucher_code' => null,
            'is_used' => false,
            'created_at' => now(),
        ]);
    }

    public function test_laporan_penukaran_poin_page_renders_successfully()
    {
        $response = $this->get(route('laporan.penukaran-poin'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Penukaran Poin Member');
    }

    public function test_get_laporan_penukaran_poin_data_returns_table()
    {
        $response = $this->post(route('laporan.penukaran-poin.data'), [
            'mulai' => now()->startOfMonth()->toDateString(),
            'akhir' => now()->toDateString(),
        ], [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $response->assertSee('Budi Member Poin');
        $response->assertSee('Tumbler Premium');
        $response->assertSee('Fisik');
        $response->assertSee('-50');
    }

    public function test_export_laporan_penukaran_poin_excel()
    {
        $response = $this->get(route('laporan.penukaran-poin.export', [
            'mulai' => now()->startOfMonth()->toDateString(),
            'akhir' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
