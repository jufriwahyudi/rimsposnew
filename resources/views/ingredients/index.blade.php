@extends('layouts.main.main')
@section('title', 'Bahan Baku (Raw Material)')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stok</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bahan Baku</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            @if (session('success'))
                <div class="alert alert-success border-0 bg-light-success alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                        <div class="fs-3 text-success"><span class="material-icons-outlined">check_circle</span></div>
                        <div class="ms-3">
                            <div class="text-success">{{ session('success') }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="ajax" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger border-0 bg-light-danger alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                        <div class="fs-3 text-danger"><span class="material-icons-outlined">error</span></div>
                        <div class="ms-3">
                            <div class="text-danger">{{ $errors->first() }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="ajax" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo" style="width:35px;height:35px;" class="me-2">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Bahan Baku (Raw Material)</h5>
                            <small class="text-muted">Master data bahan mentah untuk resep produk</small>
                        </div>
                    </div>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalIngredient" onclick="resetForm()">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah Bahan
                    </button>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle w-100 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center text-nowrap">#</th>
                                    <th class="text-nowrap" style="min-width: 100px;">SKU</th>
                                    <th class="text-nowrap" style="min-width: 160px;">Nama Bahan Baku</th>
                                    <th class="text-center text-nowrap" style="min-width: 110px;">Satuan Dasar</th>
                                    <th class="text-end text-nowrap" style="min-width: 140px;">Estimasi HPP / Satuan</th>
                                    <th class="text-nowrap" style="min-width: 220px;">Konversi Satuan Pembelian</th>
                                    <th class="text-center text-nowrap" style="min-width: 170px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ingredients as $i => $item)
                                    <tr>
                                        <td class="text-center fw-bold">{{ $i + 1 }}</td>
                                        <td class="text-nowrap"><span class="badge bg-secondary">{{ $item->sku }}</span></td>
                                        <td class="fw-bold">{{ $item->name }}</td>
                                        <td class="text-center"><span class="badge bg-info text-dark">{{ $item->baseUnit?->symbol }}</span></td>
                                        <td class="text-end text-nowrap fw-semibold">Rp {{ number_format($item->cost_per_unit, 2, ',', '.') }}</td>
                                        <td>
                                            @forelse($item->conversions as $conv)
                                                <div class="d-flex justify-content-between align-items-center mb-1 bg-light p-1 rounded border">
                                                    <small class="fw-medium">1 {{ $conv->purchaseUnit?->symbol }} ({{ $conv->code }}) = {{ number_format($conv->conversion_factor, 0) }} {{ $item->baseUnit?->symbol }}</small>
                                                    <form action="{{ route('ingredients.conversions.destroy', $conv->id) }}" method="POST" class="ms-2" onsubmit="return confirm('Hapus konversi ini?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-link text-danger p-0 m-0" style="line-height:0" title="Hapus Konversi">
                                                            <i class="material-icons-outlined" style="font-size:14px">close</i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @empty
                                                <small class="text-muted fst-italic">Belum ada konversi pembelian</small>
                                            @endforelse
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <div class="d-inline-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-info text-white btn-conv-modal" 
                                                    data-id="{{ $item->id }}" 
                                                    data-name="{{ $item->name }}" 
                                                    data-base="{{ $item->baseUnit?->symbol }}"
                                                    data-bs-toggle="modal" data-bs-target="#modalConversion"
                                                    title="Atur Konversi">
                                                    <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">sync</i> Konversi
                                                </button>
                                                <button class="btn btn-sm btn-warning" 
                                                    data-id="{{ $item->id }}"
                                                    data-sku="{{ $item->sku }}"
                                                    data-name="{{ $item->name }}"
                                                    data-base_unit_id="{{ $item->base_unit_id }}"
                                                    data-cost_per_unit="{{ $item->cost_per_unit }}"
                                                    onclick="editIngredient(this)"
                                                    title="Edit Bahan">
                                                    <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">edit</i>
                                                </button>
                                                <form action="{{ route('ingredients.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus bahan baku ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus Bahan">
                                                        <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">delete</i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada data bahan baku.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Add/Edit Ingredient --}}
    <div class="modal fade" id="modalIngredient" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('ingredients.store') }}" method="POST" id="formIngredient">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah Bahan Baku</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">SKU / Kode Bahan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sku" id="sku" placeholder="Contoh: AYM-RAW" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Bahan Baku <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="name" placeholder="Contoh: Ayam Potong Mentah" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Satuan Dasar (Base Unit) <span class="text-danger">*</span></label>
                            <select class="form-select" name="base_unit_id" id="base_unit_id" required>
                                <option value="" disabled selected>-- Pilih Satuan --</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Satuan terkecil yang digunakan saat resep dikurangi (misal: Pcs, Gram, Ml)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estimasi Harga Beli per Satuan Dasar (HPP) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" class="form-control" name="cost_per_unit" id="cost_per_unit" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Add Conversion --}}
    <div class="modal fade" id="modalConversion" tabindex="-1">
        <div class="modal-dialog">
            <form action="" method="POST" id="formConversion">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Atur Konversi Satuan Pembelian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Bahan Baku</label>
                            <input type="text" class="form-control bg-light" id="conv_ingredient_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Satuan Pembelian (Supplier Unit) <span class="text-danger">*</span></label>
                            <select class="form-select" name="purchase_unit_id" required>
                                <option value="" disabled selected>-- Pilih Satuan --</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Satuan saat membeli dari supplier (misal: Pack, Box, Karung)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode / Identitas Satuan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" placeholder="Contoh: Pack9, Pack10, Pack12" required>
                            <small class="text-muted d-block mt-1">Gunakan kode pengenal (misal: Pack9, Pack10) untuk membedakan isi porsi per Pack.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Faktor Konversi ke Satuan Dasar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" id="conv_formula_prefix">1 Satuan =</span>
                                <input type="number" step="0.0001" class="form-control" name="conversion_factor" placeholder="Contoh: 9" required>
                                <span class="input-group-text" id="conv_formula_suffix">Pcs</span>
                            </div>
                            <small class="text-muted d-block mt-1">Contoh: jika 1 Pack berisi 9 Pcs ayam, isi nilai faktor dengan 9</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Konversi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function resetForm() {
            document.getElementById('formIngredient').action = "{{ route('ingredients.store') }}";
            document.getElementById('formMethod').value = "POST";
            document.getElementById('modalTitle').innerText = "Tambah Bahan Baku";
            document.getElementById('sku').value = "";
            document.getElementById('name').value = "";
            document.getElementById('base_unit_id').value = "";
            document.getElementById('cost_per_unit').value = "";
        }

        function editIngredient(btn) {
            const id = btn.getAttribute('data-id');
            const sku = btn.getAttribute('data-sku');
            const name = btn.getAttribute('data-name');
            const baseUnitId = btn.getAttribute('data-base_unit_id');
            const cost = btn.getAttribute('data-cost_per_unit');

            document.getElementById('formIngredient').action = "/ingredients/" + id;
            document.getElementById('formMethod').value = "PUT";
            document.getElementById('modalTitle').innerText = "Edit Bahan Baku";
            document.getElementById('sku').value = sku;
            document.getElementById('name').value = name;
            document.getElementById('base_unit_id').value = baseUnitId;
            document.getElementById('cost_per_unit').value = cost;

            const modal = new bootstrap.Modal(document.getElementById('modalIngredient'));
            modal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const convButtons = document.querySelectorAll('.btn-conv-modal');
            convButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const baseSymbol = this.getAttribute('data-base');

                    document.getElementById('conv_ingredient_name').value = name;
                    document.getElementById('conv_formula_suffix').innerText = baseSymbol;
                    document.getElementById('formConversion').action = "/ingredients/" + id + "/conversions";
                });
            });
        });
    </script>
@endsection
