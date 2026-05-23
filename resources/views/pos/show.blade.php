@extends('layouts.main.main')
@section('title', 'Detail Penjualan')

@section('content')
    @php
        function variantLabel($item)
        {
            return $item->variant
                ? implode(', ', $item->variant->variantAttributes->map(fn($va) => $va->value->nama)->toArray())
                : '-';
        }
    @endphp
    <div class="card rounded-4">
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
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold">{{ $sale->invoice_number }}</h5>
                    <div class="text-muted">
                        {{ $sale->sale_date }}<br>
                        Kasir: {{ $sale->cashier->name }}
                        @if ($sale->customer_name)
                            <br>Pelanggan: {{ $sale->customer_name }}
                        @else
                            <br>Pelanggan: <em>Umum</em>
                        @endif
                    </div>
                </div>

                <div class="text-end">
                    @if ($sale->status == 'paid')
                        @if ($sale->refunds->count() > 0)
                            <h3><span class="badge bg-info text-dark">REFUNDED</span></h3>
                        @else
                            <h3><span class="badge bg-success">PAID</span></h3>
                        @endif
                    @else
                        <h3><span class="badge bg-warning text-dark">VOID</span></h3>
                    @endif
                </div>
            </div>

            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>No.Item</th>
                        <th>Produk</th>
                        <th>Varian</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr @class([
                            'text-decoration-line-through text-muted' =>
                                $item->status === 'exchanged_out',
                        ])>
                            <td>#{{ $item->id }}</td>
                            <td>
                                {{ $item->variant ? $item->variant->product->nama_produk : $item->product_name }}

                                {{-- Info hasil exchange --}}
                                @if ($item->status === 'exchanged_in' && $item->ref_sale_item_id)
                                    <div class="small text-muted">
                                        ↳ hasil tukar dari item #{{ $item->ref_sale_item_id }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $item->product_name }}</td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-end">{{ number_format($item->price) }}</td>
                            <td class="text-end text-danger">
                                {{ $item->discount_amount > 0 ? '-' . number_format($item->discount_amount) : '-' }}
                            </td>
                            <td class="text-end">{{ number_format($item->subtotal) }}</td>
                            <td class="text-center">
                                {{-- Tombol Exchange --}}
                                @if ($sale->status === 'paid' && in_array($item->status, ['sold', 'exchanged_in']))
                                    <button class="btn btn-sm btn-outline-info"
                                        onclick="openExchangeModal(
                                                        {{ $item->id }},
                                                        {{ $item->qty }},
                                                        {{ $item->subtotal }},
                                                        '{{ $item->product_name }} ({{ variantLabel($item) }})',
                                                        {{ $item->price }}
                                                    )">Tukar</button>
                                @endif

                                {{-- Badge Ditukar --}}
                                @if ($item->status === 'exchanged_out')
                                    <span class="badge bg-secondary">DITUKAR</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>


            <div class="row mt-3">
                <div class="col-md-8 col-sm-6"></div>
                <div class="col-md-4 col-sm-6">
                    <table class="table table-sm">
                        <tr>
                            <th>Subtotal</th>
                            <td class="text-end">{{ number_format($sale->subtotal) }}</td>
                        </tr>
                        <tr>
                            <th>Diskon</th>
                            <td class="text-end text-danger">
                                -{{ number_format($sale->discount_total) }}
                            </td>
                        </tr>
                        <tr class="fw-bold">
                            <th>Total</th>
                            <td class="text-end">{{ number_format($sale->grand_total) }}</td>
                        </tr>
                        <tr>
                            <th>Bayar</th>
                            <td class="text-end">{{ number_format($sale->paid_amount) }}</td>
                        </tr>
                        <tr>
                            <th>Kembali</th>
                            <td class="text-end">{{ number_format($sale->change_amount) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <a href="{{ route('pos.sales') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <button type="button" class="btn btn-outline-primary" id="btnCetakStruk"
                    onclick="cetakStruk({{ $sale->id }})">
                    <i class="bi bi-printer"></i> Cetak Struk
                </button>
                <!-- jika sales date(created_at) adalah hari ini, tampilkan tombol void -->
                @if ($sale->status == 'paid' && $sale->created_at->isToday() && $sale->refunds->count() === 0)
                    <form method="POST" class="form-inline mb-0" action="{{ route('sales.void', $sale) }}"
                        onsubmit="confirmVoid(event)">
                        @csrf
                        <button class="btn btn-outline-danger">
                            <i class="bi bi-trash"></i> Void Penjualan
                        </button>
                    </form>
                @endif
                <!-- jika sales date(created_at) bukan hari ini, tampilkan tombol refund -->
                @if ($sale->status == 'paid' && $sale->refunds->count() === 0 && !$sale->created_at->isToday())
                    <form method="POST" class="form-inline mb-0" action="{{ route('sales.refund', $sale) }}"
                        onsubmit="confirmRefund(event)">
                        @csrf

                        <input type="hidden" name="payment_method" id="refund_payment_method">
                        <input type="hidden" name="akun_bank" id="refund_akun_bank">
                        <input type="hidden" name="paid_amount" id="refund_paid_amount">

                        <button class="btn btn-outline-warning">
                            <i class="bi bi-arrow-counterclockwise"></i> Refund Penjualan
                        </button>
                    </form>
                @endif

            </div>

        </div>
    </div>
    <!-- Exchange Modal -->
    <div class="modal fade" id="exchangeModal" tabindex="-1" aria-labelledby="exchangeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exchangeModalLabel">Tukar Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('sales.exchange', $sale) }}">
                    @csrf
                    <input type="hidden" name="old_item_id" id="oldItemId">
                    <input type="hidden" id="oldItemSubtotal">
                    <div class="modal-body">
                        <div class="alert alert-secondary small">
                            <strong>Produk Lama</strong><br>
                            <span id="oldItemName"></span><br>
                            Harga: Rp <span id="oldItemPrice"></span><br>
                            Qty dibeli: <span id="oldItemQty"></span>
                        </div>
                        <label>Varian Baru</label>
                        <select name="new_variant_id" class="form-select" onchange="updatePrice()" required>
                            @foreach ($variants as $v)
                                <option value="{{ $v->id }}" {{ $v->stok_store <= 0 ? 'disabled' : '' }}
                                    data-harga="{{ $v->harga_jual }}">
                                    {{ $v->product->nama_produk }} ({{ $v->sku }})
                                    Rp.{{ number_format($v->harga_jual) }} Stok: {{ $v->stok_store }}
                                </option>
                            @endforeach
                        </select>

                        <label class="mt-2">
                            Qty Ditukar
                            <small class="text-muted">(maks <span id="maxQty"></span>)</small>
                        </label>
                        <input type="number" name="qty" class="form-control" value="1" min="1"
                            required onkeyup="updatePrice()" autocomplete="off">

                        <div class="border rounded p-2 mt-3 small">
                            <div class="d-flex justify-content-between">
                                <span>Nilai barang lama</span>
                                <strong id="oldSubtotal"></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Nilai barang baru</span>
                                <strong id="newSubtotal"></strong>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Selisih</span>
                                <span id="exchangeDiff"></span>
                            </div>
                        </div>
                        {{-- Tambahkan metode pembayaran jika ada selisih --}}
                        <div id="paymentMethodSection" class="mt-2 mb-3" style="display: none;">
                            <label>Metode Pembayaran</label>
                            <select name="payment_method" class="form-select" onchange="toggleTransferSection()"
                                required>
                                <option value="cash" selected>Tunai</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                        <div id="transferSectionExchange" class="d-none">
                            <label class="form-label">Akun Bank Tujuan</label>
                            <select id="akunBank" name="akun_bank" class="form-select">
                                <option value="">-- Pilih Bank --</option>
                                {!! $akunkas->map(fn($a) => "<option value='{$a->id}'>{$a->no_rek} - {$a->nama_rek} ({$a->bank_rek})</option>")->implode('') !!}
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="processExchangeButton">Proses Exchange</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection`
@push('initial-scripts')
    {{-- <script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.js"></script> --}}
@endpush
@push('scripts')
    <script>
        // document.addEventListener("DOMContentLoaded", () => {
        //     Printer.initQZ();
        // });
    </script>
    <script>
        function printReceipt() {
            fetch('{{ route('sales.print-receipt', $sale->id) }}')
                .then(response => response.json())
                .then(data => {
                    Printer.printReceiptPrinter(data);
                })
                .catch(error => {
                    console.error('Error fetching receipt data:', error);
                    Swal.fire('Gagal mengambil data struk.');
                });
        }

        /**
         * Cetak struk via RawBT — mengikuti pola CI3 yang sudah terbukti.
         *
         * 1. AJAX POST → server kembalikan Android Intent URI (plain text).
         * 2. Android : window.location.href = intentUri  → buka RawBT.
         * 3. PC/Mac  : WebSocket ws://127.0.0.1:40213/ → kirim intentUri.
         *              Fallback: cetak browser jika WebSocket gagal.
         */
        function cetakStruk(saleId) {
            const btn = document.getElementById('btnCetakStruk');
            btn.disabled = true;
            const originalLabel = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mengirim...';

            fetch(`/sales/${saleId}/showticketprint`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(intentUri => {
                    // Debug: lihat intent URI di console browser
                    console.log('[RawBT] intentUri:', intentUri);

                    var ua = navigator.userAgent.toLowerCase();
                    var isAndroid = ua.indexOf('android') > -1;

                    if (isAndroid) {
                        // ── Android: langsung navigasi ke intent URI (sama persis dengan CI3) ──
                        window.location.href = intentUri;
                    } else {
                        // ── PC/Mac: WebSocket ke RawBT port 40213 (sama persis dengan CI3) ──
                        try {
                            var socket = new WebSocket('ws://127.0.0.1:40213/');
                            socket.bufferType = 'arraybuffer';
                            socket.onerror = function() {
                                btn.disabled = false;
                                btn.innerHTML = originalLabel;
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'RawBT tidak terdeteksi',
                                    text: 'Pastikan aplikasi RawBT WebPrint aktif di PC.',
                                    showCancelButton: true,
                                    confirmButtonText: 'Cetak Browser',
                                    cancelButtonText: 'Tutup',
                                }).then(result => {
                                    if (result.isConfirmed) {
                                        window.open('{{ route('sales.receipt', ':id') }}'.replace(':id',
                                            saleId), '_blank');
                                    }
                                });
                            };
                            socket.onopen = function() {
                                socket.send(intentUri);
                                socket.close(1000, 'Work complete');
                                btn.disabled = false;
                                btn.innerHTML = originalLabel;
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Dikirim ke printer',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            };
                        } catch (ex) {
                            btn.disabled = false;
                            btn.innerHTML = originalLabel;
                            Swal.fire('Error', ex.message, 'error');
                        }
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalLabel;
                    Swal.fire('Error', 'Gagal mengambil data cetak: ' + err.message, 'error');
                });
        }

        function toggleTransferSection() {
            const paymentMethod = document.querySelector('select[name="payment_method"]').value;
            const transferSection = document.getElementById('transferSectionExchange');
            if (paymentMethod === 'transfer') {
                transferSection.classList.remove('d-none');
            } else {
                transferSection.classList.add('d-none');
            }
        }

        function openExchangeModal(oldItemId, oldItemQty, oldItemSubtotal, oldItemName, oldItemPrice) {
            $('#oldItemId').val(oldItemId);
            $('#oldItemSubtotal').val(oldItemSubtotal);
            $('#oldItemName').text(oldItemName);
            $('#oldItemPrice').text(oldItemPrice.toLocaleString());
            $('#oldItemQty').text(oldItemQty);
            $('#maxQty').text(oldItemQty);

            $('input[name="qty"]').attr('max', oldItemQty);
            $('input[name="qty"]').val(1);

            updatePrice();
            $('#exchangeModal').modal('show');
        }

        function updatePrice() {
            const option = document.querySelector('select[name="new_variant_id"] option:checked');
            const newPrice = parseFloat(option.dataset.harga);
            const qty = parseInt(document.querySelector('input[name="qty"]').value);
            const oldPrice = parseFloat($('#oldItemPrice').text().replace(/\D/g, ''));

            const oldSubtotal = oldPrice * qty;
            const newSubtotal = newPrice * qty;
            const diff = newSubtotal - oldSubtotal;

            $('#oldSubtotal').text('Rp ' + oldSubtotal.toLocaleString());
            $('#newSubtotal').text('Rp ' + newSubtotal.toLocaleString());
            // console.log(newPrice + ' --- ' + diff);
            if (diff > 0) {
                $('#exchangeDiff').html(`<span class="text-danger">+ Rp ${diff.toLocaleString()}</span>`);
                $('#paymentMethodSection').show();
            } else if (diff < 0) {
                $('#exchangeDiff').html(`<span class="text-success">- Rp ${Math.abs(diff).toLocaleString()}</span>`);
                $('#paymentMethodSection').show();
            } else {
                $('#exchangeDiff').html(`<span class="text-muted">Rp 0</span>`);
                $('#paymentMethodSection').hide();
            }
        }


        function confirmVoid(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Void penjualan ini?',
                text: 'Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Void!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        }

        function confirmRefund(event) {
            event.preventDefault();

            const form = event.target;
            const total = {{ (int) $sale->grand_total }};

            Swal.fire({
                title: 'Refund Pembayaran',
                width: 440,
                html: `
                    <div class="text-center mb-3">
                        <div class="text-muted small">Total Refund</div>
                        <div style="font-size:26px;font-weight:700;color:#4f46e5">
                            Rp ${total.toLocaleString('id-ID')}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <div class="d-flex gap-2">
                            <button type="button" id="btnCash" class="btn btn-primary w-50">💵 Cash</button>
                            <button type="button" id="btnTransfer" class="btn btn-outline-primary w-50">🏦 Transfer</button>
                        </div>
                    </div>

                    <div id="transferSection" class="d-none">
                        <label class="form-label">Akun Bank Tujuan</label>
                        <select id="akunBank" class="form-select">
                            <option value="">-- Pilih Bank --</option>
                            {!! $akunkas->map(fn($a) => "<option value='{$a->id}'>{$a->no_rek} - {$a->nama_rek} ({$a->bank_rek})</option>")->implode('') !!}
                        </select>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Proses Refund',
                cancelButtonText: 'Batal',
                didOpen: () => {
                    let paymentMethod = 'cash';

                    const btnCash = document.getElementById('btnCash');
                    const btnTransfer = document.getElementById('btnTransfer');
                    const transferSection = document.getElementById('transferSection');

                    btnCash.onclick = () => {
                        paymentMethod = 'cash';
                        btnCash.classList.replace('btn-outline-primary', 'btn-primary');
                        btnTransfer.classList.replace('btn-primary', 'btn-outline-primary');
                        transferSection.classList.add('d-none');
                    };

                    btnTransfer.onclick = () => {
                        paymentMethod = 'transfer';
                        btnTransfer.classList.replace('btn-outline-primary', 'btn-primary');
                        btnCash.classList.replace('btn-primary', 'btn-outline-primary');
                        transferSection.classList.remove('d-none');
                    };

                    Swal.getConfirmButton().onclick = () => {
                        const akunBank = document.getElementById('akunBank')?.value;

                        if (paymentMethod === 'transfer' && !akunBank) {
                            Swal.showValidationMessage('Pilih akun bank tujuan');
                            return;
                        }

                        // 🔑 ISI HIDDEN INPUT
                        document.getElementById('refund_payment_method').value = paymentMethod;
                        document.getElementById('refund_akun_bank').value =
                            paymentMethod === 'cash' ?
                            '{{ $akunkasir }}' :
                            akunBank;

                        document.getElementById('refund_paid_amount').value = total;

                        form.submit();
                    };
                }
            });
        }
    </script>
@endpush
