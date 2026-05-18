<style>
    .search-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        max-height: 350px;
        overflow-y: auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        margin-top: 8px;
    }

    .search-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f1f1f1;
    }

    .search-item:hover,
    .search-item.active {
        background: #f8f9fa;
    }

    .search-title {
        font-weight: 600;
    }

    .search-sub {
        font-size: 12px;
        color: #888;
    }

    .btn-copy {
        border: none;
        background: #f1f1f1;
        border-radius: 6px;
        padding: 3px 6px;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-copy:hover {
        background: #e2e6ea;
    }
</style>
<!--start header-->
<header class="top-header">
    <nav class="navbar navbar-expand align-items-center gap-4">
        <div class="btn-toggle">
            <a href="javascript:;"><i class="material-icons-outlined">menu</i></a>
        </div>
        <div class="search-bar flex-grow-1">
            <div class="position-relative">
                <input class="form-control rounded-5 px-5 search-control d-lg-block d-none" type="text"
                    id="search-product" placeholder="Search produk / SKU / barcode">
                <span
                    class="material-icons-outlined position-absolute d-lg-block d-none ms-3 translate-middle-y start-0 top-50">
                    search
                </span>
                <div id="search-dropdown" class="search-dropdown d-none"></div>
            </div>
        </div>
        <ul class="navbar-nav gap-1 nav-right-links align-items-center">
            <li class="nav-item d-lg-none mobile-search-btn">
                <a class="nav-link" href="javascript:;"><i class="material-icons-outlined">search</i></a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;"
                    data-bs-toggle="dropdown">
                    <i class="material-icons-outlined">home</i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    @if (isset($roleuserlist))
                        @foreach ($roleuserlist as $row)
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:;"
                                    onclick="ROLE.setSessionRole('{{ Crypt::encryptString($row->role_id) }}')">
                                    @php echo ($row->role_id == $roleactive->id ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="material-icons-outlined">home</i>') @endphp
                                    <span class="ms-2">{{ $row->roles->nama }}</span>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </li>

            <li class="nav-item dropdown">
                <a href="javascript:;" class="dropdown-toggle dropdown-toggle-nocaret" data-bs-toggle="dropdown">
                    @if (Auth::user()?->pegawai?->foto && Auth::user()?->pegawai?->foto !== '-')
                        <img src="{{ Storage::disk('s3')->temporaryUrl(Auth::user()->pegawai->foto, now()->addMinutes(60)) }}"
                            class="rounded-circle p-1 border" width="45" height="45" alt="">
                    @else
                        <img src="{{ asset('assets/images/avatars/11.png') }}" class="rounded-circle p-1 border"
                            width="45" height="45" alt="">
                    @endif
                </a>
                <div class="dropdown-menu dropdown-user dropdown-menu-end shadow">
                    <a class="dropdown-item  gap-2 py-2" href="javascript:;">
                        <div class="text-center">
                            @if (Auth::user()?->pegawai?->foto && Auth::user()?->pegawai?->foto !== '-')
                                <img src="{{ Storage::disk('s3')->temporaryUrl(Auth::user()->pegawai->foto, now()->addMinutes(60)) }}"
                                    class="rounded-circle p-1 shadow mb-3" width="90" height="90" alt="">
                            @else
                                <img src="{{ asset('assets/images/avatars/11.png') }}"
                                    class="rounded-circle p-1 shadow mb-3" width="90" height="90" alt="">
                            @endif
                            <h5 class="user-name mb-0 fw-bold">{{ Auth::user()->name ?? '/' }}</h5>
                            <small>{{ $roleactive->nama ?? '-' }}</small>
                        </div>
                    </a>
                    {{-- <hr class="dropdown-divider">
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">person_outline</i>Profile</a>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">local_bar</i>Setting</a>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">dashboard</i>Dashboard</a>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">account_balance</i>Earning</a>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">cloud_download</i>Downloads</a>
                    <hr class="dropdown-divider"> --}}
                    <!-- <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">power_settings_new</i>Logout</a> -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2">
                            <i class="material-icons-outlined">power_settings_new</i>Logout
                        </button>
                    </form>
                </div>
            </li>
        </ul>

    </nav>
</header>
<!--end top header-->
