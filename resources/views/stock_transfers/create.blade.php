@extends('layouts.main.main')
@section('title', 'Stock Transfer')


@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex align-items-center mb-3">
                    <img src="{{ asset('assets/images/alazca_logo.png') }}" width="35" class="me-2">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Request Transfer Stok</h5>
                        <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                    </div>
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
                    <form method="POST" action="{{ route('stock-transfers.store') }}">
                        @csrf

                        {{-- FROM - TO --}}
                        <div class="row align-items-end mb-4">
                            <div class="col-md-5">
                                <label>Dari</label>
                                <select id="from_position" name="from_position" class="form-control">
                                    <option value="warehouse">Warehouse</option>
                                    <option value="store">Store</option>
                                </select>
                            </div>

                            <div class="col-md-2 text-center">
                                <button type="button" class="btn btn-outline-secondary mt-4" onclick="switchPosition()">
                                    <i class="bi bi-arrow-left-right"></i>
                                </button>
                            </div>

                            <div class="col-md-5">
                                <label>Ke</label>
                                <select id="to_position" name="to_position" class="form-control">
                                    <option value="store">Store</option>
                                    <option value="warehouse">Warehouse</option>
                                </select>
                            </div>
                        </div>

                        {{-- ITEMS --}}
                        <table class="table table-bordered" id="items-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th width="160">Qty</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="openProductModal(0, this)">
                                            Pilih Produk
                                        </button>

                                        <input type="hidden" name="items[0][variant_id]" data-stok-warehouse="0"
                                            data-stok-store="0" class="variant-input">

                                        <div class="mt-1 small text-muted selected-variant">
                                            Belum dipilih
                                        </div>
                                        <small class="text-muted stok-info">Stok tersedia: -</small>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][qty]" class="form-control qty-input"
                                            min="1" disabled required>
                                        <small class="text-danger qty-warning d-none">
                                            Qty melebihi stok
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <button type="button" class="btn btn-outline-primary mb-3" onclick="addRow()">
                            <i class="bi bi-plus"></i> Tambah Produk
                        </button>

                        <hr>
                        <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">
                            Batal</a>
                        <button type="submit" class="btn btn-success">
                            Ajukan Transfer
                        </button>
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

                    <!-- Variant Attributes -->
                    <div id="variantSection" class="d-none">
                        <h6 id="variantProductName"></h6>

                        <div id="variantAttributes"></div>
                        <p>Stok tersedia: <span id="variantStock"></span></p>

                        <button class="btn btn-primary mt-3" id="btnSelectVariant" disabled>
                            Pilih Varian
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        let rowIndex = 1; // Tidak lagi digunakan untuk penentuan index baru

        /* FROM POSITION CHANGE */
        document.getElementById('from_position').addEventListener('change', function() {
            document.querySelectorAll('.product-select').forEach(select => {
                if (select.value) updateStock(select);
            });
        });

        /* UPDATE STOCK */
        function updateStock(selectEl) {
            const row = selectEl.closest('tr');
            const stokInfo = row.querySelector('.stok-info');
            const qtyInput = row.querySelector('.qty-input');
            const warning = row.querySelector('.qty-warning');

            if (!selectEl.value) {
                stokInfo.innerText = 'Stok tersedia: -';
                qtyInput.value = '';
                qtyInput.disabled = true;
                warning.classList.add('d-none');
                return;
            }

            const fromPos = document.getElementById('from_position').value;
            const opt = selectEl;

            let stok = fromPos === 'warehouse' ?
                opt.dataset.stokWarehouse :
                opt.dataset.stokStore;

            stok = parseInt(stok ?? 0);

            stokInfo.innerText = `Stok tersedia: ${stok}`;
            qtyInput.value = '';
            qtyInput.max = stok;
            qtyInput.disabled = stok <= 0;
            warning.classList.add('d-none');
        }

        /* QTY VALIDATION */
        document.addEventListener('input', function(e) {
            if (!e.target.classList.contains('qty-input')) return;

            const row = e.target.closest('tr');
            const stokText = row.querySelector('.stok-info').innerText;
            const warning = row.querySelector('.qty-warning');

            const stok = parseInt(stokText.replace(/\D/g, '') || 0);
            const val = parseInt(e.target.value || 0);

            if (val > stok) {
                e.target.value = stok;
                warning.classList.remove('d-none');
            } else {
                warning.classList.add('d-none');
            }
        });

        /* SWITCH POSITION */
        function addRow() {
            const tbody = document.querySelector('#items-table tbody');
            // Cari semua index yang sudah dipakai
            const usedIndexes = Array.from(tbody.querySelectorAll('input.variant-input'))
                .map(input => {
                    const match = input.name.match(/items\[(\d+)\]/);
                    return match ? parseInt(match[1]) : null;
                })
                .filter(idx => idx !== null)
                .sort((a, b) => a - b);

            // Cari index terkecil yang belum dipakai
            let newIndex = 0;
            while (usedIndexes.includes(newIndex)) {
                newIndex++;
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <button type="button" class="btn btn-outline-primary btn-sm"
                        onclick="openProductModal(${newIndex}, this)">
                        Pilih Produk
                    </button>

                    <input type="hidden" name="items[${newIndex}][variant_id]" data-stok-warehouse="0"
                        data-stok-store="0" class="variant-input">

                    <div class="mt-1 small text-muted selected-variant">
                        Belum dipilih
                    </div>
                    <small class="text-muted stok-info">Stok tersedia: -</small>
                </td>
                <td>
                    <input type="number"
                        name="items[${newIndex}][qty]"
                        class="form-control qty-input"
                        min="1"
                        disabled required>
                    <small class="text-danger qty-warning d-none">Qty melebihi stok</small>
                </td>
                <td class="text-center">
                    <button type="button"
                        class="btn btn-danger btn-sm"
                        onclick="removeRow(this)">
                        <i class="bi bi-x"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
            // initSelect2(row.querySelector('.product-select')); // Uncomment jika pakai select2
        }

        /* REMOVE ROW */
        function removeRow(btn) {
            const tbody = document.querySelector('#items-table tbody');
            if (tbody.rows.length === 1) {
                alert('Minimal satu produk');
                return;
            }
            btn.closest('tr').remove();
        }
    </script>
    <script>
        const products = @json($products);
        const attributeValues = @json($attributeValues);
        let activeRow = null;
        let selectedProduct = null;
        let selectedVariant = null;
        let selectedAttributes = {};

        function openProductModal(rowIndex, btn) {
            activeRow = btn.closest('tr');
            selectedProduct = null;
            selectedVariant = null;
            selectedAttributes = {};

            renderProductList('');
            document.getElementById('variantSection').classList.add('d-none');

            new bootstrap.Modal('#productModal').show();
        }

        function renderProductList(keyword) {
            const list = document.getElementById('productList');
            list.innerHTML = '';

            products
                .filter(p => p.nama_produk.toLowerCase().includes(keyword.toLowerCase()))
                .forEach(product => {
                    let item = document.createElement('button');
                    item.className = 'list-group-item list-group-item-action';
                    item.innerText = product.nama_produk;
                    item.onclick = () => selectProduct(product);
                    list.appendChild(item);
                });
        }

        document.getElementById('productSearch').addEventListener('input', e => {
            renderProductList(e.target.value);
        });

        function selectProduct(product) {
            selectedProduct = product;
            selectedAttributes = {};
            selectedVariant = null;

            document.getElementById('variantSection').classList.remove('d-none');
            document.getElementById('variantProductName').innerText =
                product.nama_produk;

            renderVariantAttributes(product);
        }

        function extractAttributes(product) {
            let attributes = {};

            product.variants.forEach(v => {
                v.variant_attributes.forEach(va => {
                    const attrName = va.attribute.nama;

                    if (!attributes[attrName]) {
                        attributes[attrName] = [];
                    }

                    attributes[attrName].push({
                        attribute_id: va.attribute_id,
                        attribute_value_id: va.attribute_value_id
                    });
                });
            });

            // unique per attribute_value_id
            Object.keys(attributes).forEach(attr => {
                attributes[attr] = Object.values(
                    attributes[attr].reduce((acc, cur) => {
                        acc[cur.attribute_value_id] = cur;
                        return acc;
                    }, {})
                );
            });

            return attributes;
        }

        function renderVariantAttributes(product) {
            const container = document.getElementById('variantAttributes');
            container.innerHTML = '';

            const attributes = extractAttributes(product);

            Object.keys(attributes).forEach(attrName => {
                let html = `
                    <div class="mb-3">
                        <strong>${attrName}</strong><br>
                `;

                attributes[attrName].forEach(opt => {
                    html += `
                        <button type="button"
                            class="btn btn-outline-secondary btn-sm me-1 mb-1"
                            onclick="selectAttribute('${attrName}', ${opt.attribute_value_id}, this)">
                            ${attributeValues[opt.attribute_value_id] ?? opt.attribute_value_id}
                        </button>
                    `;
                });

                html += `</div>`;
                container.insertAdjacentHTML('beforeend', html);
            });
        }


        function selectAttribute(attrName, valueId, btn) {
            selectedAttributes[attrName] = valueId;

            btn.parentElement.querySelectorAll('button')
                .forEach(b => b.classList.remove('active'));

            btn.classList.add('active');

            matchVariant();
        }

        function matchVariant() {
            selectedVariant = selectedProduct.variants.find(v => {
                return v.variant_attributes.every(va =>
                    selectedAttributes[va.attribute.nama] === va.attribute_value_id
                );
            });

            document.getElementById('btnSelectVariant').disabled = !selectedVariant;
            if (selectedVariant) {
                const fromPos = document.getElementById('from_position').value;
                const stok = fromPos === 'warehouse' ?
                    selectedVariant.stok_warehouse :
                    selectedVariant.stok_store;

                document.getElementById('variantStock').innerText = stok;
            } else {
                document.getElementById('variantStock').innerText = '-';
            }
        }


        document.getElementById('btnSelectVariant').onclick = function() {
            if (!selectedVariant) return;

            activeRow.querySelector('.variant-input').value = selectedVariant.id;
            // console.log(selectedVariant);
            activeRow.querySelector('.variant-input').dataset.stokWarehouse =
                selectedVariant.stok_warehouse;
            activeRow.querySelector('.variant-input').dataset.stokStore =
                selectedVariant.stok_store;
            activeRow.querySelector('.selected-variant').innerText =
                `${selectedProduct.nama_produk} (${selectedVariant.sku})`;
            updateStock(
                activeRow.querySelector('.variant-input')
            );

            bootstrap.Modal.getInstance(
                document.getElementById('productModal')
            ).hide();
        };
    </script>
@endpush
