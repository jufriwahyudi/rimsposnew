<!--bootstrap js-->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

<!--plugins-->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<!--plugins-->
<script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/js/notify/bootstrap-notify.min.js') }}"></script>
<script src="{{ asset('assets/js/main.js?v=1.0.0') }}"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="text/javascript">
    const input = document.getElementById('search-product');
    const dropdown = document.getElementById('search-dropdown');

    let selectedIndex = -1;
    let results = [];

    let debounceTimer;

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-bar')) {
            dropdown.classList.add('d-none');
        }
    });

    input.addEventListener('keyup', function(e) {
        const q = this.value;

        // navigation keyboard
        if (e.key === 'ArrowDown') return move(1);
        if (e.key === 'ArrowUp') return move(-1);
        if (e.key === 'Enter') return select();

        clearTimeout(debounceTimer);

        if (q.length < 2) {
            dropdown.classList.add('d-none');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/product/search?q=${q}`)
                .then(res => res.json())
                .then(data => {
                    results = data;
                    renderDropdown(data);
                });
        }, 250);
    });

    function renderDropdown(data) {
        if (!data.length) {
            dropdown.innerHTML = `<div class="search-item">Tidak ditemukan</div>`;
            dropdown.classList.remove('d-none');
            return;
        }

        dropdown.innerHTML = data.map((item, i) => `
        <div class="search-item" data-index="${i}">
            <div class="search-title">
                ${item.nama_produk} (${item.label ?? '-'})
            </div>

            <div class="search-sub d-flex justify-content-between align-items-center">
                <div>
                    SKU: ${item.sku ?? '-'} <br>
                    Barcode: ${item.barcode ?? '-'}
                </div>

                <div class="d-flex gap-1">
                    <button class="btn-copy" data-type="sku" data-value="${item.sku}">
                        📋
                    </button>
                    <button class="btn-copy" data-type="barcode" data-value="${item.barcode}">
                        🧾
                    </button>
                </div>
            </div>
        </div>
    `).join('');

        dropdown.classList.remove('d-none');
        selectedIndex = -1;

        // klik item → buka detail
        document.querySelectorAll('.search-item').forEach(el => {
            el.addEventListener('click', function(e) {
                if (e.target.closest('.btn-copy')) return; // jangan trigger kalau klik tombol
                const index = this.dataset.index;
                goTo(results[index]);
            });
        });

        // tombol copy
        document.querySelectorAll('.btn-copy').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); // penting
                copyText(this.dataset.value, this);
            });
        });
    }

    function move(step) {
        const items = document.querySelectorAll('.search-item');
        if (!items.length) return;

        selectedIndex += step;

        if (selectedIndex < 0) selectedIndex = items.length - 1;
        if (selectedIndex >= items.length) selectedIndex = 0;

        items.forEach(el => el.classList.remove('active'));
        items[selectedIndex].classList.add('active');
    }

    function select() {
        if (selectedIndex >= 0 && results[selectedIndex]) {
            goTo(results[selectedIndex]);
        }
    }

    function goTo(item) {
        // default: buka detail
        window.location.href = item.url;

        // alternatif:
        // navigator.clipboard.writeText(item.barcode);
    }

    function copyText(text, el) {
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            showCopyFeedback(el, '✔');
        }).catch(() => {
            showCopyFeedback(el, '❌');
        });
    }

    function showCopyFeedback(el, icon) {
        const original = el.innerHTML;
        el.innerHTML = icon;

        setTimeout(() => {
            el.innerHTML = original;
        }, 800);
    }
</script>
@stack('initial-scripts')
@vite(['resources/js/app.js'])
