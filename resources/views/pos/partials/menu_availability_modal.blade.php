<!-- MODAL KETERSEDIAAN MENU (86) -->
<div class="modal fade" id="modalMenuAvailability" tabindex="-1" aria-labelledby="modalMenuAvailabilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-light">
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark p-2 rounded-3 me-2">
                        <i class="bi bi-slash-circle fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalMenuAvailabilityLabel">Ketersediaan Menu & Batas Porsi</h5>
                        <small class="text-muted">Atur ketersediaan menu habis / porsi harian langsung dari kasir</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchMenuAvailability" class="form-control" placeholder="Cari nama menu, varian, atau SKU..." onkeyup="filterMenuAvailability()">
                        </div>
                    </div>
                    <div class="col-md-5 d-flex gap-2">
                        <select id="filterTenantAvailability" class="form-select" onchange="loadMenuAvailability()">
                            <option value="">Semua Tenant / Stand</option>
                        </select>
                        <button type="button" class="btn btn-outline-danger btn-sm text-nowrap" onclick="resetAllMenuAvailability()" title="Reset semua menu jadi Tersedia (Unlimited)">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 420px;">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Menu / Varian</th>
                                <th>Tenant</th>
                                <th class="text-center" width="130">Status</th>
                                <th class="text-center" width="160">Sisa Porsi (Kuota)</th>
                                <th class="text-center" width="130">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="menuAvailabilityList">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div> Memuat menu...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <span class="small text-muted me-auto"><i class="bi bi-info-circle me-1"></i> Perubahan langsung aktif di POS Web, POS Mobile, dan QR Self-Service.</span>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ==========================================
    // KETERSEDIAAN MENU & KUOTA PORSI (86 SYSTEM)
    // ==========================================
    let cachedMenuVariants = [];

    function openMenuAvailabilityModal() {
        const modalEl = document.getElementById('modalMenuAvailability');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            loadMenuAvailability();
        }
    }

    function loadMenuAvailability() {
        const tenantId = $('#filterTenantAvailability').val() || '';
        const storeId = '{{ session("store_id") }}';

        $('#menuAvailabilityList').html('<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div> Memuat menu...</td></tr>');

        $.get(`{{ url("/pos/menu-availability") }}?store_id=${storeId}&tenant_id=${tenantId}`)
            .done(function(res) {
                if (res.success && res.data) {
                    cachedMenuVariants = res.data;
                    populateTenantFilter(res.data);
                    renderMenuAvailability(cachedMenuVariants);
                } else {
                    $('#menuAvailabilityList').html('<tr><td colspan="5" class="text-center py-4 text-danger">Gagal memuat ketersediaan menu.</td></tr>');
                }
            })
            .fail(function(err) {
                $('#menuAvailabilityList').html('<tr><td colspan="5" class="text-center py-4 text-danger">Gagal: ' + (err.responseJSON?.message || err.statusText) + '</td></tr>');
            });
    }

    function populateTenantFilter(items) {
        const select = $('#filterTenantAvailability');
        const current = select.val();
        const tenants = {};
        items.forEach(i => {
            if (i.tenant_id && i.tenant_name) {
                tenants[i.tenant_id] = i.tenant_name;
            }
        });
        let opts = '<option value="">Semua Tenant / Stand</option>';
        Object.keys(tenants).forEach(tid => {
            opts += `<option value="${tid}" ${current == tid ? 'selected' : ''}>${tenants[tid]}</option>`;
        });
        select.html(opts);
    }

    function renderMenuAvailability(items) {
        if (!items || items.length === 0) {
            $('#menuAvailabilityList').html('<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada menu ditemukan.</td></tr>');
            return;
        }

        let html = '';
        items.forEach((item) => {
            const isAvailable = item.is_available;
            const isSoldOut = item.is_sold_out;
            const quota = item.daily_quota;
            
            let badgeStatus = isSoldOut
                ? '<span class="badge bg-danger px-2 py-1"><i class="bi bi-x-circle me-1"></i> Habis</span>'
                : (quota !== null
                    ? `<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock-history me-1"></i> Sisa ${quota}</span>`
                    : '<span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i> Tersedia</span>');

            const prodName = item.name || item.product_name;
            const varName = item.variant || item.variant_name;

            html += `
                <tr class="${isSoldOut ? 'table-danger-subtle' : ''}">
                    <td>
                        <strong class="text-dark">${prodName}</strong>
                        ${varName && varName !== prodName ? `<br><small class="text-muted">${varName}</small>` : ''}
                        <br><span class="badge bg-light text-secondary border font-monospace" style="font-size: 10px;">${item.sku}</span>
                    </td>
                    <td><small class="fw-semibold text-secondary">${item.tenant_name || 'Umum'}</small></td>
                    <td class="text-center">${badgeStatus}</td>
                    <td class="text-center">
                        <div class="input-group input-group-sm justify-content-center">
                            <input type="number" id="quota-input-${item.id}" class="form-control text-center" style="max-width: 90px;" 
                                value="${quota !== null ? quota : ''}" placeholder="∞ (Bebas)" min="0">
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            ${isAvailable && !isSoldOut ? `
                                <button class="btn btn-outline-danger btn-sm" onclick="quickSetAvailability(${item.id}, false, 0)" title="Tandai Habis Segera">
                                    <i class="bi bi-slash-circle"></i> Habis
                                </button>
                            ` : `
                                <button class="btn btn-outline-success btn-sm" onclick="quickSetAvailability(${item.id}, true, null)" title="Aktifkan Kembali">
                                    <i class="bi bi-check2-circle"></i> Buka
                                </button>
                            `}
                            <button class="btn btn-primary btn-sm" onclick="saveVariantAvailability(${item.id})" title="Simpan Kuota Porsi">
                                <i class="bi bi-save"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        $('#menuAvailabilityList').html(html);
    }

    function filterMenuAvailability() {
        const q = ($('#searchMenuAvailability').val() || '').toLowerCase();
        const filtered = cachedMenuVariants.filter(v => {
            const pName = (v.name || v.product_name || '').toLowerCase();
            const vName = (v.variant || v.variant_name || '').toLowerCase();
            const sku = (v.sku || '').toLowerCase();
            const tName = (v.tenant_name || '').toLowerCase();
            return pName.includes(q) || vName.includes(q) || sku.includes(q) || tName.includes(q);
        });
        renderMenuAvailability(filtered);
    }

    function quickSetAvailability(variantId, isAvailable, quota) {
        const storeId = '{{ session("store_id") }}';
        $.post(`{{ url("/pos/menu-availability/update") }}`, {
            _token: '{{ csrf_token() }}',
            store_id: storeId,
            variant_id: variantId,
            is_available: isAvailable ? 1 : 0,
            daily_quota: quota
        }).done(function(res) {
            if (res.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: isAvailable ? 'success' : 'warning',
                    title: isAvailable ? 'Menu dibuka kembali' : 'Menu ditandai Habis',
                    showConfirmButton: false,
                    timer: 1500
                });
                loadMenuAvailability();
            }
        }).fail(function(err) {
            Swal.fire('Gagal', err.responseJSON?.message || 'Gagal mengubah ketersediaan', 'error');
        });
    }

    function saveVariantAvailability(variantId) {
        const storeId = '{{ session("store_id") }}';
        const quotaVal = $(`#quota-input-${variantId}`).val();
        const quota = (quotaVal !== '' && quotaVal !== null) ? parseInt(quotaVal) : null;
        const isAvailable = (quota === null || quota > 0);

        $.post(`{{ url("/pos/menu-availability/update") }}`, {
            _token: '{{ csrf_token() }}',
            store_id: storeId,
            variant_id: variantId,
            is_available: isAvailable ? 1 : 0,
            daily_quota: quota
        }).done(function(res) {
            if (res.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Kuota menu berhasil disimpan',
                    showConfirmButton: false,
                    timer: 1500
                });
                loadMenuAvailability();
            }
        }).fail(function(err) {
            Swal.fire('Gagal', err.responseJSON?.message || 'Gagal menyimpan', 'error');
        });
    }

    function resetAllMenuAvailability() {
        Swal.fire({
            title: 'Reset Semua Menu?',
            text: 'Semua menu akan di-reset menjadi TERSEDIA tanpa batas porsi.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Ya, Reset Semua',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const storeId = '{{ session("store_id") }}';
                const tenantId = $('#filterTenantAvailability').val() || null;
                $.post(`{{ url("/pos/menu-availability/reset-all") }}`, {
                    _token: '{{ csrf_token() }}',
                    store_id: storeId,
                    tenant_id: tenantId
                }).done(function(res) {
                    Swal.fire('Berhasil', res.message, 'success');
                    loadMenuAvailability();
                }).fail(function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Gagal reset', 'error');
                });
            }
        });
    }
</script>
