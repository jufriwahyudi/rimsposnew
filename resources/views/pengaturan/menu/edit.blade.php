@extends('layouts.main.main')
@section('title', 'Edit Menu')

@section('breadcrumb')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Manajemen Menu</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Edit Menu</a></li>
            </ol>
        </nav>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card rounded-4 p-2">
            <div class="card-header d-flex justify-content-between">
                <h5>Form Edit Menu</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('menu.update', $menu->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('pengaturan.menu.form')
                    <a href="{{ route('menu.index') }}" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function setParentMenu() {
        const jnsmenu = $("#jnsmenu").val();
        if (jnsmenu === 'menu') {
            $("#id_parent").val(0);
            $("#id_parent option:not[value='0']").prop('disabled', true);
        } else {
            // Nonaktifkan pilihan "0" (Tanpa Parent)
            $("#id_parent option[value='0']").prop('disabled', true);

            // Jika dropdown saat ini berisi 0, set ulang ke pilihan pertama yang valid
            if ($("#id_parent").val() === "0") {
                const firstValidOption = $("#id_parent option:not([value='0'])").first().val();
                $("#id_parent").val(firstValidOption);
            }
        }
    }
</script>
@endpush