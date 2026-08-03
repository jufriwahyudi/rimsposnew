<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Store;
use App\Models\User;
use App\Models\RoleMaster;
use App\Models\Member;
use App\Models\MemberPointHistory;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MemberControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $store;
    protected $business;
    protected $member;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FirestoreService
        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        // 1. Create Business
        $this->business = Business::create([
            'name' => 'Member Test Biz',
            'code' => 'MTB',
        ]);

        // 2. Create Store
        $this->store = Store::create([
            'business_id' => $this->business->id,
            'name' => 'Member Test Store',
            'code' => 'MTS',
            'is_active' => true,
            'business_type' => 'retail',
        ]);

        // 3. Create User
        $this->user = User::create([
            'name' => 'Store Admin',
            'email' => 'admin.membertest@example.com',
            'password' => bcrypt('password'),
        ]);

        // 4. Create RoleMaster
        $role = RoleMaster::create([
            'nama' => 'Admin',
            'role_type' => 'ADMIN',
            'stts' => 'Y',
        ]);

        // 5. Create Member
        $this->member = Member::create([
            'business_id' => $this->business->id,
            'name' => 'Test Member',
            'phone' => '08123123123',
            'email' => 'member.test@example.com',
            'total_points' => 21,
            'is_active' => true,
        ]);

        // 6. Authenticate and set sessions
        $this->actingAs($this->user);
        $this->withSession([
            'selected_role' => $role->id,
            'store_id' => $this->store->id,
        ]);
    }

    public function test_adjust_points_upward()
    {
        $payload = [
            'new_points' => 25,
            'notes' => 'Bonus manual',
        ];

        $response = $this->postJson(route('members.adjust-points', $this->member->id), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify member points
        $this->member->refresh();
        $this->assertEquals(25, $this->member->total_points);

        // Verify point history log
        $this->assertDatabaseHas('member_point_histories', [
            'member_id' => $this->member->id,
            'store_id' => $this->store->id,
            'mutation_type' => 'adjust',
            'points' => 4,
            'balance_after' => 25,
            'notes' => 'Bonus manual',
        ]);
    }

    public function test_adjust_points_downward()
    {
        $payload = [
            'new_points' => 20,
            'notes' => 'Koreksi poin',
        ];

        $response = $this->postJson(route('members.adjust-points', $this->member->id), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify member points
        $this->member->refresh();
        $this->assertEquals(20, $this->member->total_points);

        // Verify point history log
        $this->assertDatabaseHas('member_point_histories', [
            'member_id' => $this->member->id,
            'store_id' => $this->store->id,
            'mutation_type' => 'adjust',
            'points' => -1,
            'balance_after' => 20,
            'notes' => 'Koreksi poin',
        ]);
    }

    public function test_adjust_points_no_change()
    {
        $payload = [
            'new_points' => 21,
            'notes' => 'Sama saja',
        ];

        $response = $this->postJson(route('members.adjust-points', $this->member->id), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify member points remains 21
        $this->member->refresh();
        $this->assertEquals(21, $this->member->total_points);

        // Verify no history is recorded since delta is 0
        $this->assertDatabaseMissing('member_point_histories', [
            'member_id' => $this->member->id,
            'notes' => 'Sama saja',
        ]);
    }

    public function test_adjust_points_validation_negative_points()
    {
        $payload = [
            'new_points' => -5,
            'notes' => 'Negatif',
        ];

        $response = $this->postJson(route('members.adjust-points', $this->member->id), $payload);

        $response->assertStatus(422);
    }

    public function test_export_excel()
    {
        $response = $this->get(route('members.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('Daftar_Member_', $response->headers->get('content-disposition'));
    }
}
