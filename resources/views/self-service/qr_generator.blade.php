@extends('layouts.main.main')
@section('title', 'QR Code Generator')

@section('content')
    <div class="row">
        <div class="col-md-5 d-print-none">
            <div class="card rounded-4 p-3 shadow-sm border">
                <div class="card-header border-0 bg-transparent p-0 mb-3">
                    <h5 class="fw-bold text-primary mb-1">QR Code Generator</h5>
                    <p class="text-muted small">Buat QR code unik ber-tanda tangan digital untuk meja makan pelanggan.</p>
                </div>
                <div class="card-body p-0">
                    <form id="qrForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor / Nama Meja</label>
                            <input type="text" id="tableName" class="form-control form-control-lg" placeholder="Contoh: Meja 05" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 btn-lg rounded-3">
                            <i class="bi bi-qr-code"></i> Generate QR Code
                        </button>
                    </form>
                </div>
            </div>

            <!-- List generated tables -->
            <div class="card rounded-4 p-3 shadow-sm border mt-3">
                <div class="card-header border-0 bg-transparent p-0 mb-2">
                    <h6 class="fw-bold">Panduan Penggunaan</h6>
                </div>
                <div class="card-body p-0 small text-muted">
                    <ol class="ps-3 mb-0">
                        <li class="mb-2">Ketik nama meja (misal: <strong>Meja 10</strong>).</li>
                        <li class="mb-2">Sistem akan generate URL pesanan mandiri lengkap dengan signature pengaman HMAC agar tidak bisa dimanipulasi pelanggan.</li>
                        <li class="mb-2">Tekan <strong>Cetak Kartu QR</strong> untuk mencetak card meja, lalu tempel di meja makan.</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card rounded-4 p-4 shadow-sm border text-center" id="qrResultCard" style="display: none;">
                <div class="d-print-none mb-3 text-end">
                    <button class="btn btn-outline-success btn-sm rounded-pill" onclick="window.print()">
                        <i class="bi bi-printer"></i> Cetak Kartu QR
                    </button>
                </div>

                <!-- Printable Card Area -->
                <div class="qr-print-card mx-auto" id="printableCard">
                    <div class="qr-card-header">
                        <h4 class="fw-bold mb-1 text-uppercase text-white">{{ $store->name }}</h4>
                        <div class="small text-white-50">CUSTOMER SELF-SERVICE</div>
                    </div>
                    <div class="qr-card-body">
                        <div class="qr-table-badge" id="cardTableBadge">MEJA 05</div>
                        <div class="qr-code-img-wrap">
                            <img id="qrImage" src="" alt="QR Code">
                        </div>
                        <div class="qr-card-instructions">
                            <h6 class="fw-bold mb-1"><i class="bi bi-phone"></i> SCAN UNTUK MEMESAN</h6>
                            <p class="small text-muted mb-0">Pindai kode QR di atas untuk membuka menu & memesan langsung dari HP Anda.</p>
                        </div>
                    </div>
                    <div class="qr-card-footer">
                        RIMS POS &bull; QR ORDER SYSTEM
                    </div>
                </div>

                <div class="mt-3 d-print-none">
                    <label class="form-label small fw-semibold text-muted">Target URL Link:</label>
                    <input type="text" class="form-control form-control-sm text-center bg-light" id="qrUrlText" readonly>
                </div>
            </div>

            <!-- Placeholder -->
            <div class="card rounded-4 p-5 shadow-sm border text-center text-muted d-print-none" id="qrPlaceholder">
                <i class="bi bi-qr-code fs-1 mb-2 text-black-50"></i>
                <h5>Belum ada QR Code yang di-generate</h5>
                <p class="small">Ketik nomor meja di formulir samping untuk memulai.</p>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Printable QR Card Premium Design */
        .qr-print-card {
            width: 320px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            text-align: center;
        }

        .qr-card-header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            padding: 20px 15px;
            color: white;
        }

        .qr-card-body {
            padding: 24px 20px;
        }

        .qr-table-badge {
            background: #f5f3ff;
            color: #4f46e5;
            font-size: 20px;
            font-weight: 800;
            padding: 8px 24px;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 20px;
            letter-spacing: 1px;
            border: 2px dashed #c084fc;
        }

        .qr-code-img-wrap {
            padding: 10px;
            background: white;
            border-radius: 14px;
            border: 1px solid #f1f5f9;
            display: inline-block;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .qr-code-img-wrap img {
            width: 180px;
            height: 180px;
        }

        .qr-card-instructions h6 {
            color: #1f2937;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        .qr-card-instructions p {
            font-size: 11px;
            line-height: 1.5;
        }

        .qr-card-footer {
            border-top: 1px solid #f1f5f9;
            padding: 12px 10px;
            background: #faf5ff;
            color: #9333ea;
            font-weight: bold;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        /* Print Media Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            #printableCard, #printableCard * {
                visibility: visible;
            }
            #printableCard {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                border: none;
                box-shadow: none;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        const storeId = "{{ $store->id }}";
        
        document.getElementById('qrForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const table = document.getElementById('tableName').value.trim();
            if (!table) return;

            // Fetch signature hmac from API
            fetch(`/settings/qr-generator/sign?store_id=${storeId}&table=${encodeURIComponent(table)}`)
                .then(r => r.json())
                .then(res => {
                    const qrUrl = `${window.location.origin}/order?store_id=${storeId}&table=${encodeURIComponent(table)}&hash=${res.hash}`;
                    
                    document.getElementById('qrUrlText').value = qrUrl;
                    document.getElementById('cardTableBadge').innerText = table.toUpperCase();
                    
                    // Generate QR image url using qrserver api
                    const qrImageUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrUrl)}&margin=1`;
                    document.getElementById('qrImage').src = qrImageUrl;

                    document.getElementById('qrPlaceholder').style.display = 'none';
                    document.getElementById('qrResultCard').style.display = 'block';
                })
                .catch(err => {
                    alert('Gagal generate signature untuk QR Code.');
                });
        });
    </script>
@endpush
