@extends('layouts.main.main')
@section('title', 'Purchase Orders')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Purchase Orders</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('po.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
                    </li>
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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Buat Purchase Orders</h5>
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

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('po.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Tanggal Pengajuan</label>
                                <input type="date" name="request_date" class="form-control" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Tanggal Kebutuhan</label>
                                <input type="date" name="expected_date" class="form-control" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Vendor</label>
                                <select name="vendor_id" class="form-control" required>
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Deskripsi</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <hr>

                        <h5>Item Pembelian</h5>

                        <table class="table table-bordered align-middle" id="items-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="32%">Produk</th>
                                    <th width="18%" class="text-center">Satuan Beli</th>
                                    <th width="12%" class="text-center">Qty Order</th>
                                    <th width="18%" class="text-center">Harga Beli Satuan</th>
                                    <th width="15%" class="text-center">Subtotal</th>
                                    <th width="5%" class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>

                        <button type="button" class="btn btn-sm btn-secondary" onclick="openProductModal()">+ Tambah
                            Item</button>

                        <hr>

                        <div class="row mt-3">
                            <div class="col-md-4 offset-md-8">
                                <label>Total</label>
                                <input type="text" id="totalitems" class="form-control text-right" readonly>
                            </div>
                            <!-- Tax and discount can be added here -->
                            <div class="col-md-4 offset-md-8 mt-2">
                                <label>Diskon</label>
                                <input type="text" id="discount_total" name="discount_total"
                                    class="form-control text-right" value="0" oninput="calculateTotal()">
                            </div>
                            <div class="col-md-4 offset-md-8 mt-2">
                                <label>Pajak (10%)</label>
                                <input type="text" id="tax_total" name="tax_total" class="form-control text-right"
                                    value="0" oninput="calculateTotal()">
                            </div>
                            <div class="col-md-4 offset-md-8 mt-2">
                                <label>Grand Total</label>
                                <input type="text" id="grand_total" class="form-control text-right" readonly>
                            </div>
                        </div>

                        <hr>

                        <a href="{{ route('po.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i>
                            Kembali</a>
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan PO</button>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <!-- Search -->
                    <input type="text" id="productSearch" class="form-control mb-3" placeholder="Cari produk...">

                    <!-- Product List -->
                    <div id="productList" class="list-group"></div>

                    <hr>

                    <!-- Variant Multi-Select -->
                    <div id="variantSection" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0" id="variantProductName"></h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnBackToProducts">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </button>
                        </div>

                        <input type="text" id="variantSearch" class="form-control mb-2" placeholder="Cari varian...">

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                            <label class="form-check-label fw-semibold" for="checkAll">Pilih Semua</label>
                        </div>

                        <div id="variantList" class="d-flex flex-column gap-2"></div>

                        <button class="btn btn-primary mt-3 w-100" id="btnSelectVariants" disabled>
                            <i class="bi bi-check-lg me-1"></i> Tambahkan Varian Terpilih (<span
                                id="selectedCount">0</span>)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        let rowIndex = 0;
        const variants = [];

        function addRow(product = null, variant = null) {
            let tbody = document.querySelector('#items-table tbody');
            let rIdx = rowIndex;

            let baseUnit = (product && product.base_unit) ? product.base_unit : 'Pcs';
            let units = (product && product.units) ? product.units : [];

            let unitOptions = `<option value="" data-id="" data-name="${baseUnit}" data-multiplier="1">${baseUnit} (Satuan Dasar)</option>`;
            let defaultMultiplier = 1;
            let defaultUnitName = baseUnit;
            let defaultUnitId = '';

            units.forEach(u => {
                let isDef = u.is_default_purchase ? 'selected' : '';
                if (u.is_default_purchase) {
                    defaultMultiplier = u.multiplier;
                    defaultUnitName = u.name;
                    defaultUnitId = u.id;
                }
                unitOptions += `<option value="${u.id}" data-id="${u.id}" data-name="${u.name}" data-multiplier="${u.multiplier}" ${isDef}>${u.name} (Isi ${u.multiplier} ${baseUnit})</option>`;
            });

            let variantLabel = variant 
                ? `${variant.variant_name || variant.sku}<br><small class="text-muted">${product ? product.nama_produk : ''} (${variant.sku})</small>`
                : 'Belum dipilih';

            let row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="hidden" name="items[${rIdx}][variant_id]" class="variant-input" value="${variant ? variant.id : ''}">
                    <input type="hidden" name="items[${rIdx}][unit_id]" class="unit-id-input" value="${defaultUnitId}">
                    <input type="hidden" name="items[${rIdx}][unit_name]" class="unit-name-input" value="${defaultUnitName}">
                    <input type="hidden" name="items[${rIdx}][unit_multiplier]" class="unit-multiplier-input" value="${defaultMultiplier}">

                    <div class="selected-variant ${variant ? 'text-primary fw-semibold' : 'text-muted'}">
                        ${variantLabel}
                    </div>
                </td>

                <td>
                    <select class="form-select form-select-sm unit-select" onchange="onUnitChange(this, ${rIdx})">
                        ${unitOptions}
                    </select>
                </td>

                <td>
                    <input type="number" step="any" name="items[${rIdx}][qty]"
                        class="form-control form-control-sm text-end qty"
                        min="0.01" value="1"
                        oninput="calculateRow(${rIdx})">
                    <small class="text-muted d-block mt-1 base-qty-info" id="base_qty_info_${rIdx}" style="font-size: 10.5px;">
                        ${defaultMultiplier > 1 ? `= ${defaultMultiplier} ${baseUnit}` : ''}
                    </small>
                </td>

                <td>
                    <input type="number" step="any" name="items[${rIdx}][price]"
                        class="form-control form-control-sm text-end price"
                        min="0"
                        value="0"
                        oninput="calculateRow(${rIdx})">
                </td>

                <td>
                    <input type="text" class="form-control form-control-sm text-end subtotal"
                        id="subtotal_${rIdx}"
                        readonly value="0">
                </td>

                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
                </td>
            `;

            tbody.appendChild(row);
            rowIndex++;

            return row;
        }

        function onUnitChange(selectEl, rIdx) {
            let row = selectEl.closest('tr');
            let selectedOption = selectEl.options[selectEl.selectedIndex];

            let unitId = selectedOption.dataset.id || '';
            let unitName = selectedOption.dataset.name || '';
            let multiplier = parseInt(selectedOption.dataset.multiplier) || 1;

            row.querySelector('.unit-id-input').value = unitId;
            row.querySelector('.unit-name-input').value = unitName;
            row.querySelector('.unit-multiplier-input').value = multiplier;

            calculateRow(rIdx);
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            calculateTotal();
        }

        function calculateRow(index) {
            let row = document.querySelectorAll('#items-table tbody tr')[index];
            if (!row) return;

            let qty = parseFloat(row.querySelector('.qty').value) || 0;
            let price = parseFloat(row.querySelector('.price').value) || 0;
            let multiplier = parseInt(row.querySelector('.unit-multiplier-input').value) || 1;
            let baseUnitInfo = row.querySelector('.base-qty-info');

            let subtotal = qty * price;
            let subtotalEl = document.getElementById(`subtotal_${index}`);
            if (subtotalEl) {
                subtotalEl.value = formatNumber(subtotal);
            }

            if (baseUnitInfo) {
                let opt = row.querySelector('.unit-select')?.options[0];
                let baseUnitName = opt ? (opt.dataset.name || 'unit') : 'unit';
                if (multiplier > 1) {
                    let totalBase = Math.round(qty * multiplier);
                    baseUnitInfo.textContent = `= ${totalBase} ${baseUnitName}`;
                } else {
                    baseUnitInfo.textContent = '';
                }
            }

            calculateTotal();
        }

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.subtotal').forEach(el => {
                total += parseNumber(el.value);
            });
            document.getElementById('totalitems').value = formatNumber(total);
            var discount = parseNumber(document.getElementById('discount_total').value);
            var tax = parseNumber(document.getElementById('tax_total').value);
            document.getElementById('grand_total').value = formatNumber((total - discount) + tax);
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        function parseNumber(str) {
            return Number(str.replace(/\./g, '')) || 0;
        }

        // default no rows
    </script>
    <script>
        const products = @json($products);
        const attributeValues = @json($attributeValues);
        let selectedProduct = null;

        function openProductModal() {
            selectedProduct = null;

            renderProductList('');
            document.getElementById('productSearch').value = '';
            document.getElementById('variantSection').classList.add('d-none');
            document.getElementById('productList').classList.remove('d-none');
            document.getElementById('productSearch').classList.remove('d-none');

            new bootstrap.Modal('#productModal').show();
        }

        function renderProductList(keyword) {
            const list = document.getElementById('productList');
            list.innerHTML = '';

            products
                .filter(p => p.nama_produk.toLowerCase().includes(keyword.toLowerCase()))
                .forEach(product => {
                    let item = document.createElement('button');
                    item.className =
                        'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                    item.innerHTML =
                        `<span>${product.nama_produk}</span><span class="badge bg-secondary rounded-pill">${product.variants.length} varian</span>`;
                    item.onclick = () => selectProduct(product);
                    list.appendChild(item);
                });
        }

        document.getElementById('productSearch').addEventListener('input', e => {
            renderProductList(e.target.value);
        });

        function selectProduct(product) {
            selectedProduct = product;

            document.getElementById('productList').classList.add('d-none');
            document.getElementById('productSearch').classList.add('d-none');
            document.getElementById('variantSection').classList.remove('d-none');
            document.getElementById('variantProductName').innerText = product.nama_produk;
            document.getElementById('checkAll').checked = false;
            document.getElementById('variantSearch').value = '';

            renderVariantList(product);
            updateSelectedCount();
        }

        // Back to product list
        document.getElementById('btnBackToProducts').onclick = function() {
            document.getElementById('variantSection').classList.add('d-none');
            document.getElementById('productList').classList.remove('d-none');
            document.getElementById('productSearch').classList.remove('d-none');
        };

        function getVariantLabel(variant) {
            if (variant.variant_attributes && variant.variant_attributes.length) {
                return variant.variant_attributes
                    .map(va => attributeValues[va.attribute_value_id] ?? va.attribute_value_id)
                    .join(' / ');
            }
            return variant.variant_label || variant.sku;
        }

        function renderVariantList(product) {
            const container = document.getElementById('variantList');
            container.innerHTML = '';

            product.variants.forEach(v => {
                const label = getVariantLabel(v);
                container.insertAdjacentHTML('beforeend', `
                    <label class="border rounded-3 p-2 d-flex align-items-center gap-2 variant-check-item" style="cursor:pointer;transition:all .15s">
                        <input type="checkbox" class="form-check-input variant-checkbox mt-0" value="${v.id}" data-sku="${v.sku}">
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:.9rem">${label}</div>
                            <small class="text-muted">SKU: ${v.sku}</small>
                        </div>
                    </label>
                `);
            });
        }

        // Search variants
        document.getElementById('variantSearch').addEventListener('input', function() {
            const keyword = this.value.toLowerCase();
            document.querySelectorAll('.variant-check-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(keyword)) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        });

        // Check all
        document.getElementById('checkAll').addEventListener('change', function() {
            document.querySelectorAll('.variant-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
            updateSelectedCount();
        });

        // Individual checkbox change
        $(document).on('change', '.variant-checkbox', function() {
            updateSelectedCount();
            const allChecked = document.querySelectorAll('.variant-checkbox').length ===
                document.querySelectorAll('.variant-checkbox:checked').length;
            document.getElementById('checkAll').checked = allChecked;
        });

        function updateSelectedCount() {
            const count = document.querySelectorAll('.variant-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('btnSelectVariants').disabled = count === 0;
        }

        // Confirm selection — add each variant as a row
        document.getElementById('btnSelectVariants').onclick = function() {
            const checked = document.querySelectorAll('.variant-checkbox:checked');
            if (!checked.length || !selectedProduct) return;

            checked.forEach((cb) => {
                const variantId = cb.value;
                const variant = selectedProduct.variants.find(v => v.id == variantId);
                if (variant) {
                    addRow(selectedProduct, variant);
                }
            });

            bootstrap.Modal.getInstance(
                document.getElementById('productModal')
            ).hide();
        };
    </script>
@endpush
