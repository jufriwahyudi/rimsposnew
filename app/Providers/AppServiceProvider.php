<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use App\Models\MenuList;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $roleid = session('selected_role');
            $menus = MenuList::whereHas('roles', function ($q) use ($roleid) {
                $q->where('role_id', $roleid);
            })
                ->where('id_parent', 0)
                ->where('stts', 'Y')
                ->orderBy('urutan')
                ->with([
                    'children' => function ($q) use ($roleid) {
                        $q->where('stts', 'Y')
                            ->whereHas('roles', function ($q2) use ($roleid) {
                                $q2->where('role_id', $roleid);
                            })
                            ->orderBy('urutan');
                    }
                ])
                ->get();
            $view->with('menucache', $menus);
        });
    }
}
