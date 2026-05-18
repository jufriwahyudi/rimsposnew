@extends('layouts.main.main')
@section('title', 'Manajemen Menu')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Manajemen Menu</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Pengaturan</a></li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <div class="d-flex align-items-start">
                        <img src={{ asset('assets/images/alazca_logo.png') }} alt="Logo"
                            style="width: 35px; height: 35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Pengaturan Menu</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <a href="{{ route('menu.create') }}" class="btn btn-success btn-sm mb-3"><i class="bi bi-plus"></i>
                        Tambah Menu</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center" width="8%">Icon</th>
                                <th>Nama Menu</th>
                                <th>Route</th>
                                <th class="text-center" width="8%">Urutan</th>
                                <th class="text-center" width="8%">Status</th>
                                <th class="text-center" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($menus as $menu)
                                <tr>
                                    <td class="text-center">
                                        <div class="parent-icon">
                                            <i class="material-icons-outlined">{{ $menu->icon }}</i>
                                        </div>
                                    </td>
                                    <td>{{ $menu->nama }}</td>
                                    <td>{{ $menu->routename }}</td>
                                    <td class="text-center">{{ $menu->urutan }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $menu->stts === 'Y' ? 'bg-grd-success' : 'bg-grd-danger' }}">
                                            {{ $menu->stts === 'Y' ? 'Aktif' : 'Non Aktif' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('menu.edit', Crypt::encryptString($menu->id)) }}"
                                            class="btn btn-warning btn-sm px-2"><i class="fa fa-pencil"></i></a>
                                        <form action="{{ route('menu.destroy', $menu->id) }}" method="POST"
                                            style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm px-2"
                                                onclick="return confirm('Yakin ingin menghapus menu ini?')"><i
                                                    class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @if ($menu->children->isNotEmpty())
                                    @foreach ($menu->children as $child)
                                        <tr>
                                            <td>
                                                <svg class="fill-icon" style="width: 20px; height: 20px;">
                                                    <use
                                                        href="{{ url('assets/svg/icon-sprite.svg#fill-' . $child->icon) }}">
                                                    </use>
                                                </svg>
                                            </td>
                                            <td><i class="fa fa-angle-right"></i>&nbsp;&nbsp;&nbsp;{{ $child->nama }}</td>
                                            <!-- Indentasi untuk child menu -->
                                            <td>{{ $child->routename }}</td>
                                            <td class="text-center">{{ $child->urutan }}</td>
                                            <td class="text-center">
                                                <span
                                                    class="badge {{ $child->stts == 'Y' ? 'bg-grd-success' : 'bg-grd-danger' }}">
                                                    {{ $child->stts == 'Y' ? 'Aktif' : 'Non Aktif' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('menu.edit', Crypt::encryptString($child->id)) }}"
                                                    class="btn btn-warning btn-sm px-2"><i class="fa fa-pencil"></i></a>
                                                <form action="{{ route('menu.destroy', $child->id) }}" method="POST"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm px-2"
                                                        onclick="return confirm('Yakin ingin menghapus?')"><i
                                                            class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
