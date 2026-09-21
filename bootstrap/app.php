<?php

use App\Http\Middleware\CheckStoreSubscription;
use App\Http\Middleware\EnsureStoreSelected;
use App\Http\Middleware\InjectUserDataToView;
use App\Http\Middleware\RecoverUserSession;
use App\Http\Middleware\RoleType;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'recoverSession'   => RecoverUserSession::class,
            'injectUserData'   => InjectUserDataToView::class,
            'role.type'        => RoleType::class,
            'store.selected'   => EnsureStoreSelected::class,
            'check.subscription' => CheckStoreSubscription::class,
            'addon'            => \App\Http\Middleware\EnsureAddonEnabled::class,
            'api.key'          => \App\Http\Middleware\ValidateStoreApiKey::class,
        ]);

        // Tambahkan recover ke group web (SETELAH StartSession dkk)
        $middleware->appendToGroup('web', [
            RecoverUserSession::class,
        ]);

        // Kecualikan logout dari validasi CSRF agar tidak pernah 419 saat sesi kedaluwarsa
        $middleware->validateCsrfTokens(except: [
            'logout',
            'logout/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Callback terpusat untuk menangani CSRF token mismatch / sesi kedaluwarsa (HTTP 419)
        $handleCsrfExpiration = function (\Illuminate\Http\Request $request) {
            // 1. Request AJAX / JSON / API
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message'    => 'Sesi Anda telah kedaluwarsa. Silakan muat ulang halaman.',
                    'csrf_token' => csrf_token(),
                ], 419);
            }

            // 2. Request ke /logout
            if ($request->is('logout') || $request->routeIs('logout')) {
                if (auth()->check()) {
                    auth()->logout();
                }
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login');
            }

            // 3. Request ke /login
            if ($request->is('login') || $request->routeIs('login')) {
                $request->session()->regenerateToken();
                return redirect()->route('login')
                    ->withInput($request->only('email'))
                    ->with('warning', 'Sesi formulir login telah kedaluwarsa. Token keamanan telah diperbarui otomatis, silakan tekan Masuk kembali.');
            }

            // 4. Request form lainnya: regenerasi token & redirect kembali tanpa layar 419 buntu
            $request->session()->regenerateToken();

            if (auth()->check()) {
                return redirect()->back()
                    ->withInput($request->except(['_token', 'password', 'password_confirmation']))
                    ->with('warning', 'Sesi formulir Anda telah disegarkan otomatis karena kedaluwarsa. Silakan kirim ulang formulir.');
            }

            return redirect()->route('login')
                ->with('warning', 'Sesi Anda telah kedaluwarsa. Silakan login kembali.');
        };

        // Di Laravel 11, TokenMismatchException diubah menjadi HttpException(419) oleh prepareException()
        // sebelum renderViaCallbacks dijalankan. Karena itu tangani HttpException (kode 419).
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) use ($handleCsrfExpiration) {
            if ($e->getStatusCode() === 419 || $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException) {
                return $handleCsrfExpiration($request);
            }
        });

        // Tangani juga TokenMismatchException langsung jika dipanggil dari unit test atau pemanggil langsung
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) use ($handleCsrfExpiration) {
            return $handleCsrfExpiration($request);
        });
    })->create();
