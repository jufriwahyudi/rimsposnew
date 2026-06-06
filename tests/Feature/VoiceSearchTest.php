<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RoleMaster;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoiceSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $store;
    protected $business;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FirestoreService
        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        // 1. Create Business
        $this->business = Business::create([
            'name' => 'Voice Test Biz',
            'code' => 'VTBZ',
        ]);

        // 2. Create Store
        $this->store = Store::create([
            'business_id' => $this->business->id,
            'name' => 'Voice Test Store',
            'code' => 'VTST',
            'is_active' => true,
            'business_type' => 'retail',
        ]);

        // 3. Create User
        $this->user = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'password' => bcrypt('password'),
        ]);

        // Connect user to store
        $this->user->stores()->attach($this->store->id);
    }

    public function test_voice_search_requires_parameters()
    {
        Sanctum::actingAs($this->user);

        // Test missing store_id
        $response = $this->postJson('/api/pos/voice-search', [
            'text' => 'wardah whitening'
        ]);
        $response->assertStatus(422);

        // Test missing text
        $response = $this->postJson('/api/pos/voice-search', [
            'store_id' => $this->store->id
        ]);
        $response->assertStatus(422);
    }

    public function test_voice_search_returns_parsed_query_and_suggestions()
    {
        Sanctum::actingAs($this->user);

        // Create product & variant
        $product = Product::create([
            'store_id' => $this->store->id,
            'kode_produk' => 'PRD-01',
            'nama_produk' => 'Wardah Whitening Day Cream',
        ]);

        $variant = ProductVariant::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'sku' => 'WRD-WHITE-01',
            'barcode' => '8991234567890',
            'variant_name' => '30ml',
            'harga_jual' => 45000,
            'is_active' => 'Y',
        ]);

        // Create barcode
        $variant->barcodes()->create([
            'barcode' => '8991234567890',
            'is_active' => 'Y',
        ]);

        // Call POS voice search API
        $response = $this->postJson('/api/pos/voice-search', [
            'store_id' => $this->store->id,
            'text' => 'cari wardah whitening 30ml jumlah tiga'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'parsed_query' => [
                'product_name',
                'quantity'
            ],
            'suggestions'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('wardah whitening 30ml', $data['parsed_query']['product_name']);
        $this->assertEquals(3, $data['parsed_query']['quantity']);
        $this->assertCount(1, $data['suggestions']);
        $this->assertEquals('Wardah Whitening Day Cream', $data['suggestions'][0]['name']);
        $this->assertEquals('30ml', $data['suggestions'][0]['variant']);
    }

    public function test_live_gemini_api_call()
    {
        $gemini = new \App\Services\GeminiService();
        $result = $gemini->parseVoiceCommand('cari wardah whitening 30ml jumlah tiga');
        
        \Illuminate\Support\Facades\Log::info('Live Gemini Test Result:', $result);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('product_name', $result);
        $this->assertArrayHasKey('quantity', $result);
    }
}
