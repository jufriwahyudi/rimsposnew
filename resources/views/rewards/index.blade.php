@extends('layouts.main.main')
@section('title', 'Master Penukaran Hadiah')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Loyalty</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Barang Penukaran Poin</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <div class="avatar avatar-md bg-light-primary text-primary rounded-3 me-2 p-1">
                            <i class="material-icons-outlined" style="font-size:28px">card_giftcard</i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-primary">Barang Penukaran Poin (Rewards Master)</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <button class="btn btn-primary rounded-pill btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalReward">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah Barang/Voucher
                    </button>
                </div>
                <div class="card-body">
                    <table id="tbl-rewards" class="table table-bordered w-100 table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Barang / Hadiah</th>
                                <th>Poin Dibutuhkan</th>
                                <th>Tipe Hadiah</th>
                                <th>Nilai Voucher</th>
                                <th>Stok Barang</th>
                                <th>Status</th>
                                <th class="text-center" width="100">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit Reward --}}
    <div class="modal fade" id="modalReward" tabindex="-1">
        <div class="modal-dialog">
            <form id="formReward">
                @csrf
                <input type="hidden" id="reward_id" value="">
                <div class="modal-content rounded-4 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalRewardTitle">Tambah Barang Penukaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Barang / Hadiah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="reward_name" placeholder="Contoh: Tumbler Exclusive Rims" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Poin yang Dibutuhkan <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="reward_points_required" placeholder="Contoh: 50" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipe Hadiah <span class="text-danger">*</span></label>
                            <select class="form-select" id="reward_type" required onchange="toggleFormFields()">
                                <option value="physical">Barang Fisik (Tumbler, Mug, dll)</option>
                                <option value="voucher_percent">Voucher Diskon Persentase (%)</option>
                                <option value="voucher_nominal">Voucher Potongan Belanja (Rupiah)</option>
                            </select>
                        </div>
                        <div class="mb-3" id="group_value" style="display:none">
                            <label class="form-label fw-semibold" id="label_value">Nilai Diskon <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" id="prefix_value">Rp</span>
                                <input type="number" class="form-control" id="reward_value" placeholder="Contoh: 10000" min="0">
                                <span class="input-group-text" id="suffix_value" style="display:none">%</span>
                            </div>
                        </div>
                        <div class="mb-3" id="group_max_discount" style="display:none">
                            <label class="form-label fw-semibold">Batas Diskon Maksimal (Rupiah) <small class="text-muted">(Kosongkan jika tidak ada batas)</small></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="reward_max_discount" placeholder="Contoh: 15000" min="0">
                            </div>
                        </div>
                        <div class="mb-3" id="group_stock">
                            <label class="form-label fw-semibold">Jumlah Stok Fisik <small class="text-muted">(Kosongkan untuk tanpa batas / unlimited)</small></label>
                            <input type="number" class="form-control" id="reward_stock" placeholder="Contoh: 20" min="0">
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="reward_is_active" checked>
                            <label class="form-check-label fw-semibold" for="reward_is_active">Aktifkan Hadiah Ini</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-3">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const csrfToken = '{{ csrf_token() }}';
        let table;

        $(function() {
            table = $('#tbl-rewards').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: '{{ route('rewards.index') }}',
                },
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'points_required', name: 'points_required', className: 'text-end font-monospace fw-bold' },
                    { data: 'reward_type', name: 'reward_type', className: 'text-center' },
                    { data: 'value', name: 'value', className: 'text-end' },
                    { data: 'stock', name: 'stock', className: 'text-center' },
                    { data: 'is_active', name: 'is_active', className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
                ],
                order: [[0, 'asc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                drawCallback: function() {
                    bindEditButtons();
                    bindDeleteButtons();
                }
            });
        });

        function toggleFormFields() {
            const type = document.getElementById('reward_type').value;
            const groupValue = document.getElementById('group_value');
            const groupStock = document.getElementById('group_stock');
            const groupMaxDiscount = document.getElementById('group_max_discount');
            const labelValue = document.getElementById('label_value');
            const prefixValue = document.getElementById('prefix_value');
            const suffixValue = document.getElementById('suffix_value');
            const valInput = document.getElementById('reward_value');

            if (type === 'physical') {
                groupValue.style.display = 'none';
                valInput.removeAttribute('required');
                groupStock.style.display = 'block';
                groupMaxDiscount.style.display = 'none';
                document.getElementById('reward_max_discount').value = '';
            } else {
                groupValue.style.display = 'block';
                valInput.setAttribute('required', 'required');
                groupStock.style.display = 'none';

                if (type === 'voucher_percent') {
                    labelValue.textContent = 'Persentase Diskon *';
                    prefixValue.style.display = 'none';
                    suffixValue.style.display = 'block';
                    groupMaxDiscount.style.display = 'block';
                } else {
                    labelValue.textContent = 'Nominal Potongan (Rupiah) *';
                    prefixValue.style.display = 'block';
                    suffixValue.style.display = 'none';
                    groupMaxDiscount.style.display = 'none';
                    document.getElementById('reward_max_discount').value = '';
                }
            }
        }

        // Reset modal untuk tambah
        document.getElementById('modalReward').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return;
            document.getElementById('modalRewardTitle').textContent = 'Tambah Barang Penukaran';
            document.getElementById('reward_id').value = '';
            document.getElementById('formReward').reset();
            toggleFormFields();
        });

        function bindEditButtons() {
            document.querySelectorAll('.btn-edit-reward').forEach(btn => {
                btn.removeEventListener('click', handleEdit);
                btn.addEventListener('click', handleEdit);
            });
        }

        function handleEdit() {
            document.getElementById('modalRewardTitle').textContent = 'Edit Data Hadiah';
            document.getElementById('reward_id').value = this.dataset.id;
            document.getElementById('reward_name').value = this.dataset.name;
            document.getElementById('reward_points_required').value = this.dataset.points_required;
            document.getElementById('reward_type').value = this.dataset.reward_type;
            document.getElementById('reward_value').value = this.dataset.value;
            document.getElementById('reward_max_discount').value = this.dataset.max_discount || '';
            document.getElementById('reward_stock').value = this.dataset.stock;
            document.getElementById('reward_is_active').checked = this.dataset.is_active === '1';

            toggleFormFields();
            new bootstrap.Modal(document.getElementById('modalReward')).show();
        }

        function bindDeleteButtons() {
            document.querySelectorAll('.btn-delete-reward').forEach(btn => {
                btn.removeEventListener('click', handleDelete);
                btn.addEventListener('click', handleDelete);
            });
        }

        function handleDelete() {
            if (!confirm('Yakin ingin menghapus barang penukaran ini?')) return;
            
            fetch(`/settings/rewards/${this.dataset.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    table.ajax.reload(null, false);
                } else {
                    alert(data.message);
                }
            });
        }

        // Submit form reward
        document.getElementById('formReward').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('reward_id').value;
            const url = id ? `/settings/rewards/${id}` : '/settings/rewards';
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    name: document.getElementById('reward_name').value,
                    points_required: document.getElementById('reward_points_required').value,
                    reward_type: document.getElementById('reward_type').value,
                    value: document.getElementById('reward_value').value,
                    max_discount: document.getElementById('reward_max_discount').value,
                    stock: document.getElementById('reward_stock').value,
                    is_active: document.getElementById('reward_is_active').checked ? '1' : '0',
                }),
            })
            .then(r => {
                if (!r.ok) {
                    return r.json().then(err => { throw err; });
                }
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalReward'))?.hide();
                    table.ajax.reload(null, false);
                } else {
                    alert(data.message || 'Gagal menyimpan.');
                }
            })
            .catch(err => {
                alert(err.message || 'Terjadi kesalahan sistem.');
            });
        });
    </script>
@endpush
