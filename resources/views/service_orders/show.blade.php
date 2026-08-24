@extends('layouts.main.main')
@section('title', 'Detail Tiket Servis #' . $serviceOrder->order_number)

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Layanan Servis</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('service-orders.index') }}">Tiket Servis</a></li>
                    <li class="breadcrumb-item active">#{{ $serviceOrder->order_number }}</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Header Card -->
            <div class="card rounded-4 p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="fw-bold mb-0" style="color:#7c3aed">Tiket #{{ $serviceOrder->order_number }}</h4>
                            @php
                                $badgeClass = match ($serviceOrder->status) {
                                    'received'      => 'bg-secondary',
                                    'diagnosing'    => 'bg-info text-dark',
                                    'waiting_parts' => 'bg-warning text-dark',
                                    'in_progress'   => 'bg-primary',
                                    'completed'     => 'bg-success',
                                    'delivered'     => 'bg-dark',
                                    'cancelled'     => 'bg-danger',
                                    default         => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }} fs-6">{{ ucfirst($serviceOrder->status) }}</span>
                        </div>
                        <small class="text-muted">Diterima pada: {{ $serviceOrder->created_at->format('d M Y, H:i') }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('service-orders.print-ticket', $serviceOrder->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-printer"></i> Cetak Tanda Terima
                        </a>
                        <a href="{{ route('service-orders.edit', $serviceOrder->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="row g-3 bg-light rounded p-3 mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Unit / Objek Servis</small>
                        <strong class="fs-6">{{ $serviceOrder->target_name }}</strong>
                        @if ($serviceOrder->target_identifier)
                            <div class="mt-1">
                                <span class="badge bg-white text-dark border">ID/IMEI/Plat: {{ $serviceOrder->target_identifier }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Pelanggan</small>
                        <strong class="fs-6">{{ $serviceOrder->customer_name }}</strong>
                        <div class="text-muted"><i class="bi bi-telephone"></i> {{ $serviceOrder->customer_phone ?: '-' }}</div>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Keluhan / Masalah</small>
                        <div class="p-2 bg-white rounded border">{{ $serviceOrder->complaint_notes }}</div>
                    </div>
                    @if ($serviceOrder->diagnosis_notes)
                        <div class="col-12">
                            <small class="text-muted d-block">Hasil Diagnosa / Keterangan</small>
                            <div class="p-2 bg-white rounded border">{{ $serviceOrder->diagnosis_notes }}</div>
                        </div>
                    @endif
                </div>

                <!-- Items Section -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-list-check"></i> Rincian Jasa & Komponen/Sparepart</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddItem">
                        <i class="bi bi-plus"></i> Tambah Jasa / Part
                    </button>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th width="100">Tipe</th>
                                <th width="140">Staff / Teknisi</th>
                                <th width="120" class="text-end">Harga</th>
                                <th width="60" class="text-center">Qty</th>
                                <th width="130" class="text-end">Subtotal</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($serviceOrder->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->name }}</strong>
                                        @if ($item->commission_amount > 0)
                                            <br><small class="text-success"><i class="bi bi-award"></i> Komisi: Rp {{ number_format($item->commission_amount, 0, ',', '.') }} ({{ $item->staff?->name }})</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->item_type === 'service' ? 'bg-primary' : 'bg-info text-dark' }}">
                                            {{ $item->item_type === 'service' ? 'Jasa Layanan' : 'Sparepart' }}
                                        </span>
                                    </td>
                                    <td>{{ $item->staff?->name ?? '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('service-orders.destroy-item', [$serviceOrder->id, $item->id]) }}" onsubmit="return confirm('Hapus item ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada item jasa atau sparepart yang ditambahkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end">Total Biaya Servis:</th>
                                <th class="text-end fs-6 text-primary">Rp {{ number_format($serviceOrder->total_cost, 0, ',', '.') }}</th>
                                <th></th>
                            </tr>
                            @if ($serviceOrder->down_payment > 0)
                                <tr>
                                    <th colspan="5" class="text-end">Uang Muka (DP):</th>
                                    <th class="text-end text-success">- Rp {{ number_format($serviceOrder->down_payment, 0, ',', '.') }}</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="5" class="text-end">Sisa Tagihan:</th>
                                    <th class="text-end fs-6 text-danger">Rp {{ number_format($serviceOrder->remaining_payment, 0, ',', '.') }}</th>
                                    <th></th>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar / Actions -->
        <div class="col-lg-4">
            <!-- Status Update Box -->
            <div class="card rounded-4 p-3 mb-3 border">
                <h6 class="fw-bold mb-3"><i class="bi bi-arrow-repeat"></i> Update Status Pengerjaan</h6>
                <form method="POST" action="{{ route('service-orders.update-status', $serviceOrder->id) }}">
                    @csrf
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            <option value="received" {{ $serviceOrder->status == 'received' ? 'selected' : '' }}>Diterima (Received)</option>
                            <option value="diagnosing" {{ $serviceOrder->status == 'diagnosing' ? 'selected' : '' }}>Pemeriksaan / Diagnosa</option>
                            <option value="waiting_parts" {{ $serviceOrder->status == 'waiting_parts' ? 'selected' : '' }}>Menunggu Sparepart/Bahan</option>
                            <option value="in_progress" {{ $serviceOrder->status == 'in_progress' ? 'selected' : '' }}>Sedang Dikerjakan</option>
                            <option value="completed" {{ $serviceOrder->status == 'completed' ? 'selected' : '' }}>Selesai (Siap Diambil)</option>
                            <option value="delivered" {{ $serviceOrder->status == 'delivered' ? 'selected' : '' }}>Sudah Diambil / Lunas</option>
                            <option value="cancelled" {{ $serviceOrder->status == 'cancelled' ? 'selected' : '' }}>Batal (Cancelled)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle"></i> Perbarui Status
                    </button>
                </form>
            </div>

            <!-- Payment & POS Info -->
            <div class="card rounded-4 p-3 mb-3 border">
                <h6 class="fw-bold mb-3"><i class="bi bi-wallet2"></i> Status Pembayaran Kasir</h6>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Status Bayar:</span>
                    <span class="badge {{ $serviceOrder->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                        {{ ucfirst($serviceOrder->payment_status) }}
                    </span>
                </div>
                @if ($serviceOrder->sale)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">No. Invoice POS:</span>
                        <a href="{{ route('sales.show', $serviceOrder->sale_id) }}" class="fw-bold">#{{ $serviceOrder->sale->invoice_number }}</a>
                    </div>
                @else
                    <div class="alert alert-info py-2 px-3 mb-2 small">
                        Unit dapat dibayar melalui kasir POS dengan memilih <strong>"Tarik Tiket Servis"</strong> di menu POS.
                    </div>
                    <a href="{{ route('pos.index') }}" class="btn btn-success w-100">
                        <i class="bi bi-cart-check"></i> Buka Kasir POS
                    </a>
                @endif
            </div>

            <!-- Technician PIC Info -->
            <div class="card rounded-4 p-3 border">
                <h6 class="fw-bold mb-2"><i class="bi bi-person-badge"></i> Teknisi / Staff PIC</h6>
                <p class="mb-1"><strong>{{ $serviceOrder->assignedStaff?->name ?? 'Belum Ditugaskan' }}</strong></p>
                <small class="text-muted">Garansi: {{ $serviceOrder->warranty_days }} Hari</small>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Item Jasa / Part -->
    <div class="modal fade" id="modalAddItem" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('service-orders.add-item', $serviceOrder->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Tambah Item Jasa / Sparepart</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipe Item</label>
                            <select name="item_type" id="item_type" class="form-select" onchange="toggleItemType(this.value)">
                                <option value="service">Jasa / Layanan Servis</option>
                                <option value="product">Sparepart / Komponen / Aksesoris</option>
                            </select>
                        </div>

                        <!-- Pilih Produk Cepat -->
                        <div class="mb-3" id="wrapper_service_preset">
                            <label class="form-label fw-semibold">Pilih dari Master Jasa & Varian</label>
                            <select class="form-select" onchange="presetService(this)">
                                <option value="">-- Input Manual / Pilih Master Jasa --</option>
                                @foreach ($serviceVariants as $sv)
                                    <option value="{{ $sv->id }}" 
                                            data-product-id="{{ $sv->product_id }}"
                                            data-name="{{ $sv->product->nama_produk }}{{ $sv->variant_label ? ' - ' . $sv->variant_label : '' }}" 
                                            data-price="{{ $sv->harga_jual }}"
                                            data-comm-type="{{ $sv->product->default_commission_type }}"
                                            data-comm-rate="{{ $sv->product->default_commission_rate }}">
                                        {{ $sv->product->nama_produk }} {{ $sv->variant_label ? ' (' . $sv->variant_label . ')' : '' }} - Rp {{ number_format($sv->harga_jual, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="wrapper_part_preset">
                            <label class="form-label fw-semibold">Pilih dari Stok Sparepart</label>
                            <select class="form-select" onchange="presetPart(this)">
                                <option value="">-- Input Manual / Pilih Part --</option>
                                @foreach ($partVariants as $pv)
                                    <option value="{{ $pv->id }}" 
                                            data-product-id="{{ $pv->product_id }}"
                                            data-name="{{ $pv->product->nama_produk }} - {{ $pv->variant_label }}" 
                                            data-price="{{ $pv->harga_jual }}">
                                        {{ $pv->product->nama_produk }} ({{ $pv->variant_label }}) - Rp {{ number_format($pv->harga_jual, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="product_id" id="item_product_id">
                        <input type="hidden" name="product_variant_id" id="item_variant_id">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Layanan / Part <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="item_name" class="form-control" required placeholder="Contoh: Jasa Ganti LCD / LCD Screen iPhone 13">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" step="any" name="price" id="item_price" class="form-control" required value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Qty <span class="text-danger">*</span></label>
                                <input type="number" name="qty" id="item_qty" class="form-control" required value="1" min="1">
                            </div>
                        </div>

                        <!-- Komisi Teknisi -->
                        <div class="card bg-light border p-2 mb-3">
                            <label class="form-label fw-bold small text-primary mb-1"><i class="bi bi-award"></i> Komisi Staff / Teknisi Pelaksana</label>
                            <div class="mb-2">
                                <select name="staff_user_id" id="item_staff_id" class="form-select form-select-sm">
                                    <option value="">-- Pilih Staff Pelaksana (Opsional) --</option>
                                    @foreach ($staffs as $s)
                                        <option value="{{ $s->id }}" {{ $serviceOrder->assigned_staff_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <select name="commission_type" id="item_comm_type" class="form-select form-select-sm">
                                        <option value="none">Tanpa Komisi</option>
                                        <option value="percentage">Persentase (%)</option>
                                        <option value="fixed">Nominal Tetap (Rp)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <input type="number" step="any" name="commission_rate" id="item_comm_rate" class="form-control form-control-sm" placeholder="Nilai Komisi" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambahkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function toggleItemType(type) {
        if (type === 'service') {
            $('#wrapper_service_preset').removeClass('d-none');
            $('#wrapper_part_preset').addClass('d-none');
        } else {
            $('#wrapper_service_preset').addClass('d-none');
            $('#wrapper_part_preset').removeClass('d-none');
        }
    }

    function presetService(select) {
        let opt = select.options[select.selectedIndex];
        if (opt.value) {
            $('#item_product_id').val(opt.getAttribute('data-product-id'));
            $('#item_variant_id').val(opt.value);
            $('#item_name').val(opt.getAttribute('data-name'));
            $('#item_price').val(opt.getAttribute('data-price'));
            
            let cType = opt.getAttribute('data-comm-type') || 'none';
            let cRate = opt.getAttribute('data-comm-rate') || 0;
            $('#item_comm_type').val(cType);
            $('#item_comm_rate').val(cRate);
        } else {
            $('#item_product_id').val('');
            $('#item_variant_id').val('');
        }
    }

    function presetPart(select) {
        let opt = select.options[select.selectedIndex];
        if (opt.value) {
            $('#item_product_id').val(opt.getAttribute('data-product-id'));
            $('#item_variant_id').val(opt.value);
            $('#item_name').val(opt.getAttribute('data-name'));
            $('#item_price').val(opt.getAttribute('data-price'));
        }
    }
</script>
@endpush
