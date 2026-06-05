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
        ]);

        // Tambahkan recover ke group web (SETELAH StartSession dkk)
        $middleware->appendToGroup('web', [
            RecoverUserSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
