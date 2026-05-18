@extends('layouts.main.main')
@section('title', 'Purchase Orders')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Purchase Orders</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Purchase Orders</a></li>
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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Purchase Orders</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <a href="{{ route('po.create') }}" class="btn btn-success btn-sm mb-3"><i class="bi bi-plus"></i>
                        Tambah PO</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered">
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal PO</th>
                            <th>PO</th>
                            <th>Vendor</th>
                            <th class="text-center" width="10%">Subtotal</th>
                            <th class="text-center" width="10%">Pajak</th>
                            <th class="text-center" width="10%">Diskon</th>
                            <th class="text-center" width="10%">Grand Total</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                        @forelse ($pos as $index => $po)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ date('d-m-Y', strtotime($po->request_date)) }}</td>
                                <td>{{ $po->po_number }}</td>
                                <td>{{ $po->vendor->nama_vendor }}</td>
                                <td class="text-end">{{ number_format($po->subtotal) }}</td>
                                <td class="text-end">{{ number_format($po->tax_total) }}</td>
                                <td class="text-end">{{ number_format($po->discount_total) }}</td>
                                <td class="text-end fw-bold">{{ number_format($po->grand_total) }}</td>
                                <td class="text-center">{{ $po->status }}</td>
                                <td class="text-center">
                                    @if ($po->status === 'DRAFT')
                                        <div class="d-flex gap-1 justify-content-center">
                                            <form class="form-submit" method="post"
                                                action="{{ route('po.submit', $po) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-warning"><i class="bi bi-send"></i>
                                                    Submit</button>
                                            </form>
                                            <form class="form-delete" method="post"
                                                action="{{ route('po.destroy', $po) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger btn-delete"><i
                                                        class="bi bi-trash"></i>
                                                    Hapus</button>
                                            </form>
                                        </div>
                                    @endif

                                    @if ($po->status === 'SUBMITTED')
                                        {{-- Approval akan dilakukan dari aplikasi finance --}}
                                        <span class="badge bg-info text-dark">Menunggu Approval</span>
                                    @endif

                                    @if (in_array($po->status, ['APPROVED', 'PARTIAL_RECEIVED']))
                                        <a href="{{ route('gr.create', $po) }}" class="btn btn-sm btn-success"><i
                                                class="bi bi-box-arrow-in-down"></i> Terima Barang</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">Tidak ada data PO.</td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        document.querySelectorAll('.form-submit').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Apakah Anda yakin ingin mengirim PO ini untuk approval?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, kirim',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('.form-delete');
                Swal.fire({
                    title: 'Hapus PO?',
                    text: 'PO yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
