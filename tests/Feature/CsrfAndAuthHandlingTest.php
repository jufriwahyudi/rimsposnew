<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CsrfAndAuthHandlingTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $store;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::create([
            'name' => 'Test Biz',
            'code' => 'TBZ',
        ]);

        $this->store = Store::create([
            'business_id' => $business->id,
            'name'        => 'Test Store',
            'code'        => 'TST01',
            'is_active'   => true,
        ]);

        $this->user = User::create([
            'name'     => 'User Test',
            'email'    => 'test.user@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->user->stores()->attach($this->store->id);
    }

    public function test_logout_without_csrf_never_gives_419()
    {
        // 1. POST logout tanpa token / token kedaluwarsa harus sukses redirect ke /login
        $response = $this->actingAs($this->user)
            ->post('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $this->assertGuest();

        // 2. GET logout juga harus sukses redirect ke /login tanpa error 405 atau 419
        $this->actingAs($this->user);
        $getResp = $this->get('/logout');
        $getResp->assertStatus(302);
        $getResp->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_expired_token_on_login_redirects_cleanly_instead_of_419()
    {
        // Simulasikan request POST login yang melempar TokenMismatchException
        $request = \Illuminate\Http\Request::create('/login', 'POST', [
            'email' => 'test.user@example.com',
        ]);
        $request->setLaravelSession(session()->driver());

        $exception = new \Illuminate\Session\TokenMismatchException('CSRF token mismatch.');
        $response = app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request, $exception);

        // Memastikan dialihkan ke /login via GET (302) dengan session flash warning
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('login'), $response->headers->get('Location'));
        $this->assertTrue(session()->has('warning'));
    }

    public function test_http_exception_419_on_login_redirects_cleanly_with_warning()
    {
        // Di Laravel 11, TokenMismatchException dikonversi menjadi HttpException(419).
        // Uji bahwa HttpException(419) pada /login otomatis dialihkan ke /login via GET (302) dengan flash warning.
        $request = \Illuminate\Http\Request::create('/login', 'POST', [
            'email' => 'test.user@example.com',
        ]);
        $request->setLaravelSession(session()->driver());

        $exception = new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'CSRF token mismatch.');
        $response = app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request, $exception);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('login'), $response->headers->get('Location'));
        $this->assertTrue(session()->has('warning'));
    }

    public function test_refresh_csrf_endpoint()
    {
        $response = $this->getJson('/refresh-csrf');

        $response->assertStatus(200);
        $response->assertJsonStructure(['csrf_token']);
        $this->assertNotEmpty($response->json('csrf_token'));
    }
}
