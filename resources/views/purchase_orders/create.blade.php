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

                        <table class="table table-bordered" id="items-table">
                            <thead>
                                <tr>
                                    <th width="40%">Produk</th>
                                    <th width="15%" class="text-center">Qty</th>
                                    <th width="20%" class="text-center">Harga</th>
                                    <th width="20%" class="text-center">Subtotal</th>
                                    <th width="5%"></th>
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

        function addRow() {
            let tbody = document.querySelector('#items-table tbody');

            let row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="hidden" name="items[${rowIndex}][variant_id]" class="variant-input">

                    <div class="text-muted selected-variant">
                        Belum dipilih
                    </div>
                </td>

                <td>
                    <input type="number" name="items[${rowIndex}][qty]"
                        class="form-control qty"
                        min="1" value="1"
                        oninput="calculateRow(${rowIndex})">
                </td>

                <td>
                    <input type="number" name="items[${rowIndex}][price]"
                        class="form-control price"
                        min="0" step="0.01"
                        value="0"
                        oninput="calculateRow(${rowIndex})">
                </td>

                <td>
                    <input type="text" class="form-control subtotal"
                        id="subtotal_${rowIndex}"
                        readonly value="0">
                </td>

                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
                </td>
            `;

            tbody.appendChild(row);
            rowIndex++;

            return row;
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            calculateTotal();
        }

        function calculateRow(index) {
            let row = document.querySelectorAll('#items-table tbody tr')[index];
            if (!row) return;

            let qty = row.querySelector('.qty').value || 0;
            let price = row.querySelector('.price').value || 0;
            let subtotal = qty * price;

            document.getElementById(`subtotal_${index}`).value = formatNumber(subtotal);
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
                const sku = cb.dataset.sku;
                const label = `${selectedProduct.nama_produk} (${sku})`;

                const targetRow = addRow();

                targetRow.querySelector('.variant-input').value = variantId;
                targetRow.querySelector('.selected-variant').innerText = label;
                targetRow.querySelector('.selected-variant').classList.remove('text-muted');
                targetRow.querySelector('.selected-variant').classList.add('text-primary', 'fw-semibold');
            });

            bootstrap.Modal.getInstance(
                document.getElementById('productModal')
            ).hide();
        };
    </script>
@endpush
