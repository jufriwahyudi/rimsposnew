<!--start sidebar-->
<aside class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div class="logo-icon">
            <img src="{{ asset('assets/images/alazca_logo.png') }}" class="logo-img" alt="">
        </div>
        <div class="logo-name flex-grow-1">
            <h5 class="mb-0">{{ config('app.name') }}</h5>
        </div>
        <div class="sidebar-close">
            <span class="material-icons-outlined">close</span>
        </div>
    </div>
    <div class="sidebar-nav">
        {{-- Tambahkan Nama Toko Aktif --}}
        <div class="store-name text-center py-3 mx-3 my-2 rounded-3"
            style="background: linear-gradient(90deg, #7c3aed 0%, #4f46e5 100%); color: #fff; border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 6px 18px rgba(79,70,229,0.12);">
            <h6 class="mb-0 fw-bold text-white">{{ session('store_name', 'No Store Selected') }}</h6>
            <small class="d-block text-white-50">{{ session('store_id') ? 'Toko aktif' : 'Global Oversight' }}</small>
        </div>
        <!--navigation-->
        <ul class="metismenu" id="sidenav">
            @if (isset($menucache))
                @foreach ($menucache as $menu)
                    @php
                        $hasChild = '';
                        if ($menu->children->count() > 0) {
                            $hasChild = 'class=has-arrow';
                        }
                    @endphp
                    <li>
                        <a {{ $menu->routename === '#' ? 'href=javascript:;' : 'href=' . route($menu->routename) . '' }}
                            {{ $hasChild }}>
                            <div class="parent-icon"><i class="material-icons-outlined">{{ $menu->icon }}</i></div>
                            <div class="menu-title">{{ $menu->nama }}</div>
                        </a>
                        @if ($menu->children->isNotEmpty())
                            <ul>
                                @foreach ($menu->children as $child)
                                    <li><a
                                            href="{{ $child->routename === '#' ? 'javascript:void(0);' : route($child->routename) }}"><i
                                                class="material-icons-outlined">arrow_right</i> {{ $child->nama }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            @endif
            @if(session('role_name') === 'SUPERADMIN' || session('role_name') === 'ADMIN' || (auth()->check() && auth()->user()->role === 'SUPERADMIN'))
            <li>
                <a href="{{ route('settings.app-version') }}">
                    <div class="parent-icon"><i class="material-icons-outlined">system_update_alt</i></div>
                    <div class="menu-title">Update APK Kasir</div>
                </a>
            </li>
            @endif
        </ul>
        <!--end navigation-->
    </div>
</aside>
<!--end sidebar-->
