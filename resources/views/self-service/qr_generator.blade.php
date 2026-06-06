@extends('layouts.main.main')
@section('title', 'QR Code Generator')

@section('content')
    <div class="row g-4">
        {{-- ═══════════════ LEFT: FORM + LIST ═══════════════ --}}
        <div class="col-lg-5 col-md-6 d-print-none">

            {{-- Generate Form Card --}}
            <div class="qr-form-card">
                <div class="qr-form-card-header">
                    <div class="qr-form-icon">
                        <i class="bi bi-qr-code"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">QR Code Generator</h5>
                        <p class="text-muted small mb-0">Buat QR code untuk meja pelanggan</p>
                    </div>
                </div>
                <div class="qr-form-card-body">
                    <form id="qrForm">
                        <label class="form-label fw-semibold small text-muted text-uppercase letter-spacing-1">Nama / Nomor Meja</label>
                        <div class="input-group input-group-lg qr-input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-grid-3x3-gap text-primary"></i>
                            </span>
                            <input type="text" id="tableName"
                                class="form-control border-start-0 ps-0"
                                placeholder="Contoh: Meja 05" required>
                        </div>
                        <button type="submit" class="btn btn-generate w-100 mt-3">
                            <i class="bi bi-lightning-charge-fill me-2"></i>Generate QR Code
                        </button>
                    </form>
                </div>
            </div>

            {{-- Table List Card --}}
            <div class="qr-list-card mt-3">
                <div class="qr-list-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-0">Daftar Meja</h6>
                            <p class="text-muted small mb-0">{{ count($qrCodes) }} meja terdaftar</p>
                        </div>
                        <span class="badge-count">{{ count($qrCodes) }}</span>
                    </div>
                </div>
                <div class="qr-list-body" id="qrListContainer">
                    @forelse($qrCodes as $code)
                        <div class="qr-list-item" id="item-qr-{{ $code->id }}"
                            onclick="previewQrCode({{ $code->id }}, '{{ $code->table_name }}', '{{ $code->url }}', '{{ route('settings.qr-generator.image', $code->id) }}', '{{ route('settings.qr-generator.download', $code->id) }}')">
                            <div class="qr-list-item-icon">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                            </div>
                            <div class="qr-list-item-info flex-grow-1">
                                <div class="fw-semibold">{{ $code->table_name }}</div>
                                <div class="small text-muted">Klik untuk pratinjau</div>
                            </div>
                            <div class="qr-list-item-actions" onclick="event.stopPropagation()">
                                <button class="qr-action-btn qr-action-download" title="Unduh PNG"
                                    onclick="previewThenDownload({{ $code->id }}, '{{ $code->table_name }}', '{{ $code->url }}', '{{ route('settings.qr-generator.image', $code->id) }}', '{{ route('settings.qr-generator.download', $code->id) }}')">
                                    <i class="bi bi-download"></i>
                                </button>
                                <button class="qr-action-btn qr-action-delete" title="Hapus"
                                    onclick="deleteQrCode({{ $code->id }})">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="qr-empty-state" id="emptyState">
                            <i class="bi bi-inbox fs-2 mb-2 opacity-50"></i>
                            <p class="small text-muted mb-0">Belum ada meja terdaftar</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Guide --}}
            <div class="qr-guide-card mt-3">
                <div class="d-flex gap-3 align-items-start mb-2">
                    <div class="qr-guide-step">1</div>
                    <p class="small mb-0">Ketik nama meja (misal: <strong>Meja 10</strong>) lalu klik <strong>Generate</strong>.</p>
                </div>
                <div class="d-flex gap-3 align-items-start mb-2">
                    <div class="qr-guide-step">2</div>
                    <p class="small mb-0">QR tersimpan otomatis ke database dengan tanda tangan HMAC aman.</p>
                </div>
                <div class="d-flex gap-3 align-items-start">
                    <div class="qr-guide-step">3</div>
                    <p class="small mb-0">Klik meja di daftar → lihat kartu QR → unduh PNG untuk dicetak.</p>
                </div>
            </div>
        </div>

        {{-- ═══════════════ RIGHT: PORTRAIT CARD PREVIEW ═══════════════ --}}
        <div class="col-lg-7 col-md-6">
            <div class="qr-preview-stage" id="qrPreviewStage">

                {{-- Placeholder --}}
                <div class="qr-stage-placeholder" id="qrPlaceholder">
                    <div class="qr-placeholder-icon">
                        <i class="bi bi-qr-code"></i>
                    </div>
                    <h5 class="fw-bold mt-3 mb-1">Pilih atau Buat QR Code</h5>
                    <p class="text-muted small">Pilih meja dari daftar atau generate baru untuk melihat kartu QR-nya di sini</p>
                    <div class="qr-placeholder-dots mt-3">
                        <span></span><span></span><span></span>
                    </div>
                </div>

                {{-- Portrait Card --}}
                <div class="qr-portrait-wrapper" id="qrResultCard" style="display:none;">
                    {{-- The printable card --}}
                    <div class="qr-portrait-card" id="printableCard">
                        {{-- Card Top Accent --}}
                        <div class="qr-portrait-top">
                            <div class="qr-portrait-logo-area">
                                <div class="qr-portrait-logo">
                                    <i class="bi bi-shop-window"></i>
                                </div>
                                <div class="qr-portrait-store-name">{{ $store->name }}</div>
                                <div class="qr-portrait-tagline">Customer Self-Service</div>
                            </div>

                            {{-- Decorative circles --}}
                            <div class="qr-deco-circle qr-deco-1"></div>
                            <div class="qr-deco-circle qr-deco-2"></div>
                        </div>

                        {{-- Table Badge --}}
                        <div class="qr-portrait-middle">
                            <div class="qr-portrait-table-badge" id="cardTableBadge">MEJA 05</div>
                        </div>

                        {{-- QR Code Area --}}
                        <div class="qr-portrait-qr-area">
                            <div class="qr-portrait-qr-frame">
                                <img id="qrImage" src="" alt="QR Code" class="qr-portrait-img">
                                <div class="qr-frame-corner qr-fc-tl"></div>
                                <div class="qr-frame-corner qr-fc-tr"></div>
                                <div class="qr-frame-corner qr-fc-bl"></div>
                                <div class="qr-frame-corner qr-fc-br"></div>
                            </div>
                        </div>

                        {{-- Instruction --}}
                        <div class="qr-portrait-instruction">
                            <div class="qr-scan-icon">
                                <i class="bi bi-phone-fill"></i>
                                <i class="bi bi-arrow-right-short"></i>
                                <i class="bi bi-qr-code-scan"></i>
                            </div>
                            <p class="qr-scan-title">SCAN UNTUK MEMESAN</p>
                            <p class="qr-scan-subtitle">Arahkan kamera HP Anda ke kode di atas untuk membuka menu & memesan langsung.</p>
                        </div>

                        {{-- Card Footer --}}
                        <div class="qr-portrait-footer">
                            <span>RIMS POS</span>
                            <span class="qr-footer-dot">•</span>
                            <span>QR ORDER SYSTEM</span>
                        </div>
                    </div>

                    {{-- Action Bar below card --}}
                    <div class="qr-action-bar">
                        <div class="qr-url-display">
                            <i class="bi bi-link-45deg text-muted me-1"></i>
                            <span id="qrUrlText" class="qr-url-text">-</span>
                            <button class="qr-copy-btn" onclick="copyQrUrl()" title="Salin URL">
                                <i class="bi bi-clipboard" id="copyIcon"></i>
                            </button>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button id="downloadBtn" onclick="downloadCardAsPng()" class="btn btn-download-main flex-grow-1">
                                <i class="bi bi-download me-2" id="downloadIcon"></i>
                                <span id="downloadBtnText">Unduh PNG</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* ══════════════════════════════════════════
           LEFT PANEL — FORM CARD
        ══════════════════════════════════════════ */
        .qr-form-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e8ecf0;
            box-shadow: 0 4px 24px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .qr-form-card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px 24px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .qr-form-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px;
            flex-shrink: 0;
        }
        .qr-form-card-body { padding: 20px 24px 24px; }
        .letter-spacing-1 { letter-spacing: 0.08em; }

        .qr-input-group {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .qr-input-group:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .qr-input-group .input-group-text { border: none; background: #fff; color: #64748b; }
        .qr-input-group .form-control { border: none; box-shadow: none; font-size: 15px; }
        .qr-input-group .form-control:focus { box-shadow: none; }

        .btn-generate {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            padding: 13px 20px;
            font-size: 15px;
            letter-spacing: 0.02em;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(79,70,229,0.3);
        }
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79,70,229,0.4);
            color: white;
        }
        .btn-generate:active { transform: translateY(0); }

        /* ══════════════════════════════════════════
           LEFT PANEL — LIST CARD
        ══════════════════════════════════════════ */
        .qr-list-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e8ecf0;
            box-shadow: 0 4px 24px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .qr-list-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .badge-count {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            font-size: 12px;
            font-weight: 700;
            min-width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .qr-list-body {
            max-height: 280px;
            overflow-y: auto;
            padding: 8px 0;
        }
        .qr-list-body::-webkit-scrollbar { width: 4px; }
        .qr-list-body::-webkit-scrollbar-track { background: transparent; }
        .qr-list-body::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

        .qr-list-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background 0.15s;
            border-left: 3px solid transparent;
        }
        .qr-list-item:hover {
            background: #f8f7ff;
            border-left-color: #6366f1;
        }
        .qr-list-item.active {
            background: #f5f3ff;
            border-left-color: #7c3aed;
        }
        .qr-list-item-icon {
            width: 36px; height: 36px;
            background: #f5f3ff;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #7c3aed;
            font-size: 16px;
            flex-shrink: 0;
        }
        .qr-list-item-info .fw-semibold { font-size: 14px; }
        .qr-list-item-info .small { font-size: 11px; }

        .qr-list-item-actions { display: flex; gap: 4px; }
        .qr-action-btn {
            width: 30px; height: 30px;
            border-radius: 8px;
            border: none;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
        }
        .qr-action-download { background: #ecfdf5; color: #059669; }
        .qr-action-download:hover { background: #059669; color: white; }
        .qr-action-delete { background: #fef2f2; color: #dc2626; }
        .qr-action-delete:hover { background: #dc2626; color: white; }

        .qr-empty-state {
            text-align: center;
            padding: 32px 20px;
            color: #94a3b8;
        }

        /* ══════════════════════════════════════════
           LEFT PANEL — GUIDE
        ══════════════════════════════════════════ */
        .qr-guide-card {
            background: #fafbff;
            border: 1px solid #e8ecf0;
            border-radius: 16px;
            padding: 16px 20px;
        }
        .qr-guide-step {
            width: 26px; height: 26px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            font-weight: 800;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* ══════════════════════════════════════════
           RIGHT PANEL — PREVIEW STAGE
        ══════════════════════════════════════════ */
        .qr-preview-stage {
            min-height: 580px;
            background: radial-gradient(ellipse at 30% 30%, #eef2ff 0%, #f8fafc 60%);
            border-radius: 24px;
            border: 1.5px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
        }

        /* Placeholder */
        .qr-stage-placeholder {
            text-align: center;
            padding: 20px;
        }
        .qr-placeholder-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #e0e7ff, #ede9fe);
            border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            font-size: 36px;
            color: #6366f1;
            margin: 0 auto;
            box-shadow: 0 8px 24px rgba(99,102,241,0.15);
        }
        .qr-placeholder-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .qr-placeholder-dots span {
            width: 8px; height: 8px;
            background: #cbd5e1;
            border-radius: 50%;
            animation: dotPulse 1.5s ease-in-out infinite;
        }
        .qr-placeholder-dots span:nth-child(2) { animation-delay: 0.3s; }
        .qr-placeholder-dots span:nth-child(3) { animation-delay: 0.6s; }
        @keyframes dotPulse {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1); }
        }

        /* Portrait card wrapper */
        .qr-portrait-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 320px;
            animation: cardReveal 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes cardReveal {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ══════════════════════════════════════════
           THE PORTRAIT QR CARD
        ══════════════════════════════════════════ */
        .qr-portrait-card {
            width: 300px;
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(99, 102, 241, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .qr-portrait-card:hover {
            transform: translateY(-6px) rotate(0.5deg);
            box-shadow: 
                0 35px 60px -15px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(99, 102, 241, 0.15);
        }

        /* Top header */
        .qr-portrait-top {
            background: linear-gradient(150deg, #312e81 0%, #4f46e5 40%, #7c3aed 70%, #9333ea 100%);
            padding: 28px 24px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .qr-portrait-logo-area { position: relative; z-index: 1; }
        .qr-portrait-logo {
            width: 52px; height: 52px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1.5px solid rgba(255,255,255,0.25);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .qr-portrait-store-name {
            font-size: 17px;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            line-height: 1.2;
        }
        .qr-portrait-tagline {
            font-size: 10px;
            color: rgba(255,255,255,0.6);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* Decorative circles on header */
        .qr-deco-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .qr-deco-1 { width: 120px; height: 120px; top: -40px; right: -30px; }
        .qr-deco-2 { width: 80px; height: 80px; bottom: -20px; left: -20px; }

        /* Middle: table badge */
        .qr-portrait-middle {
            background: #fff;
            padding: 20px 24px 0;
            text-align: center;
        }
        .qr-portrait-table-badge {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #f5f3ff, #ede9fe);
            color: #5b21b6;
            font-size: 20px;
            font-weight: 900;
            padding: 8px 28px;
            border-radius: 50px;
            letter-spacing: 2px;
            border: 2px dashed #c4b5fd;
            box-shadow: 0 4px 12px rgba(124,58,237,0.12);
        }

        /* QR code area */
        .qr-portrait-qr-area {
            background: #fff;
            padding: 20px 24px;
            display: flex;
            justify-content: center;
        }
        .qr-portrait-qr-frame {
            position: relative;
            padding: 14px;
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f1f5f9;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }
        .qr-portrait-img {
            width: 160px; height: 160px;
            display: block;
        }

        /* Corner decorators on QR frame */
        .qr-frame-corner {
            position: absolute;
            width: 14px; height: 14px;
            border-color: #6366f1;
            border-style: solid;
        }
        .qr-fc-tl { top: 4px; left: 4px; border-width: 2px 0 0 2px; border-radius: 4px 0 0 0; }
        .qr-fc-tr { top: 4px; right: 4px; border-width: 2px 2px 0 0; border-radius: 0 4px 0 0; }
        .qr-fc-bl { bottom: 4px; left: 4px; border-width: 0 0 2px 2px; border-radius: 0 0 0 4px; }
        .qr-fc-br { bottom: 4px; right: 4px; border-width: 0 2px 2px 0; border-radius: 0 0 4px 0; }

        /* Instruction area */
        .qr-portrait-instruction {
            background: #fafbff;
            padding: 16px 24px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
        }
        .qr-scan-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 20px;
            color: #6366f1;
            margin-bottom: 6px;
        }
        .qr-scan-title {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #4f46e5;
            margin-bottom: 4px;
        }
        .qr-scan-subtitle {
            font-size: 10.5px;
            color: #94a3b8;
            line-height: 1.5;
            margin-bottom: 0;
        }

        /* Card footer */
        .qr-portrait-footer {
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 10px 16px;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .qr-footer-dot { opacity: 0.5; }

        /* ══════════════════════════════════════════
           ACTION BAR below card
        ══════════════════════════════════════════ */
        .qr-action-bar {
            margin-top: 20px;
            width: 100%;
        }
        .qr-url-display {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1.5px solid #e8ecf0;
            border-radius: 12px;
            padding: 8px 12px;
            gap: 6px;
            overflow: hidden;
        }
        .qr-url-text {
            font-size: 11px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }
        .qr-copy-btn {
            background: none;
            border: none;
            color: #6366f1;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 6px;
            transition: background 0.15s;
            flex-shrink: 0;
        }
        .qr-copy-btn:hover { background: #f0f0ff; }

        .btn-download-main {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            padding: 11px 20px;
            font-size: 14px;
            text-align: center;
            transition: all 0.25s;
            box-shadow: 0 4px 15px rgba(16,185,129,0.3);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-download-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16,185,129,0.4);
            color: white;
        }
    </style>
@endpush

@push('scripts')
    <script>
        const csrfToken = "{{ csrf_token() }}";
        let activeItemId = null;

        // ── Form Submit ──────────────────────────────────────────────
        document.getElementById('qrForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const table = document.getElementById('tableName').value.trim();
            if (!table) return;

            Swal.fire({
                title: 'Sedang memproses...',
                text: 'Membuat QR Code untuk ' + table,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch("{{ route('settings.qr-generator.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ table_name: table })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.close();
                    previewQrCode(res.qr_code.id, res.qr_code.table_name, res.qr_code.url, res.qr_code.image_url, res.qr_code.download_url);
                    addItemToList(res.qr_code);
                    document.getElementById('tableName').value = '';

                    if (res.already_exists) {
                        Swal.fire({ title: 'Info', text: res.message, icon: 'info', timer: 2000, showConfirmButton: false });
                    } else {
                        Swal.fire({ title: 'Sukses!', text: 'QR Code berhasil dibuat.', icon: 'success', timer: 1500, showConfirmButton: false });
                    }
                } else {
                    Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(() => Swal.fire('Gagal', 'Terjadi kesalahan sistem.', 'error'));
        });

        // ── Preview QR Code ──────────────────────────────────────────
        function previewQrCode(id, tableName, url, imageUrl, downloadUrl) {
            // Highlight active item in list
            document.querySelectorAll('.qr-list-item').forEach(el => el.classList.remove('active'));
            const activeItem = document.getElementById('item-qr-' + id);
            if (activeItem) activeItem.classList.add('active');
            activeItemId = id;

            // Update card contents
            document.getElementById('cardTableBadge').innerText = tableName.toUpperCase();
            document.getElementById('qrImage').src = imageUrl;
            document.getElementById('qrUrlText').innerText = url;
            document.getElementById('downloadBtn').href = downloadUrl || '/settings/qr-generator/download/' + id;

            // Show card, hide placeholder
            document.getElementById('qrPlaceholder').style.display = 'none';
            const resultCard = document.getElementById('qrResultCard');
            resultCard.style.display = 'flex';
            resultCard.style.flexDirection = 'column';
            resultCard.style.alignItems = 'center';
        }

        // ── Add item to list ─────────────────────────────────────────
        function addItemToList(qr) {
            const emptyState = document.getElementById('emptyState');
            if (emptyState) emptyState.remove();

            // If already exists, just flash
            let existingItem = document.getElementById('item-qr-' + qr.id);
            if (existingItem) {
                existingItem.style.transition = 'background 0.5s';
                existingItem.style.background = '#d1fae5';
                setTimeout(() => { existingItem.style.background = ''; }, 1000);
                return;
            }

            const container = document.getElementById('qrListContainer');
            const div = document.createElement('div');
            div.className = 'qr-list-item';
            div.id = 'item-qr-' + qr.id;
            div.setAttribute('onclick', `previewQrCode(${qr.id}, '${qr.table_name}', '${qr.url}', '${qr.image_url}', '${qr.download_url}')`);
            div.innerHTML = `
                <div class="qr-list-item-icon">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </div>
                <div class="qr-list-item-info flex-grow-1">
                    <div class="fw-semibold">${qr.table_name}</div>
                    <div class="small text-muted">Klik untuk pratinjau</div>
                </div>
                <div class="qr-list-item-actions" onclick="event.stopPropagation()">
                    <button class="qr-action-btn qr-action-download" title="Unduh PNG"
                        onclick="previewThenDownload(${qr.id}, '${qr.table_name}', '${qr.url}', '${qr.image_url}', '${qr.download_url}')">
                        <i class="bi bi-download"></i>
                    </button>
                    <button class="qr-action-btn qr-action-delete" title="Hapus" onclick="deleteQrCode(${qr.id})">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
        }

        // ── Delete QR Code ───────────────────────────────────────────
        function deleteQrCode(id) {
            Swal.fire({
                title: 'Hapus QR Code?',
                text: 'QR Code ini akan dihapus dari database!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Menghapus...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    fetch(`/settings/qr-generator/${id}`, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            const item = document.getElementById('item-qr-' + id);
                            if (item) item.remove();

                            const container = document.getElementById('qrListContainer');
                            if (container && container.children.length === 0) {
                                container.innerHTML = `
                                    <div class="qr-empty-state" id="emptyState">
                                        <i class="bi bi-inbox fs-2 mb-2 opacity-50"></i>
                                        <p class="small text-muted mb-0">Belum ada meja terdaftar</p>
                                    </div>`;
                            }

                            // Reset preview if deleted item was being shown
                            if (activeItemId == id) {
                                document.getElementById('qrPlaceholder').style.display = 'block';
                                document.getElementById('qrResultCard').style.display = 'none';
                                activeItemId = null;
                            }

                            Swal.fire('Dihapus!', 'QR Code berhasil dihapus.', 'success');
                        } else {
                            Swal.fire('Gagal', res.message || 'Gagal menghapus.', 'error');
                        }
                    })
                    .catch(() => Swal.fire('Gagal', 'Terjadi kesalahan sistem.', 'error'));
                }
            });
        }

        // ── Copy URL ─────────────────────────────────────────────────
        function copyQrUrl() {
            const urlText = document.getElementById('qrUrlText').innerText;
            navigator.clipboard.writeText(urlText).then(() => {
                const icon = document.getElementById('copyIcon');
                icon.className = 'bi bi-clipboard-check';
                setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 2000);
            });
        }

        // ── Download Card as PNG (html2canvas) ───────────────────────
        function downloadCardAsPng() {
            const card = document.getElementById('printableCard');
            const tableName = document.getElementById('cardTableBadge').innerText || 'qrcode';
            const filename = 'qrcode-' + tableName.toLowerCase().replace(/\s+/g, '-') + '.png';

            // Update button state
            const btn = document.getElementById('downloadBtn');
            const icon = document.getElementById('downloadIcon');
            const btnText = document.getElementById('downloadBtnText');
            btn.disabled = true;
            icon.className = 'bi bi-hourglass-split me-2';
            btnText.textContent = 'Menyiapkan...';

            html2canvas(card, {
                scale: 3,           // 3x resolution for crisp print quality
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false,
                removeContainer: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = filename;
                link.href = canvas.toDataURL('image/png');
                link.click();

                // Restore button
                btn.disabled = false;
                icon.className = 'bi bi-download me-2';
                btnText.textContent = 'Unduh PNG';
            }).catch(err => {
                console.error('html2canvas error:', err);
                btn.disabled = false;
                icon.className = 'bi bi-download me-2';
                btnText.textContent = 'Unduh PNG';
                Swal.fire('Gagal', 'Gagal mengunduh kartu QR.', 'error');
            });
        }

        // ── Preview then immediately download (from list item button) ─
        function previewThenDownload(id, tableName, url, imageUrl, downloadUrl) {
            previewQrCode(id, tableName, url, imageUrl, downloadUrl);
            // Wait for image to load, then capture
            const img = document.getElementById('qrImage');
            const doDownload = () => downloadCardAsPng();
            if (img.complete) {
                setTimeout(doDownload, 300);
            } else {
                img.onload = () => setTimeout(doDownload, 300);
            }
        }
    </script>

    {{-- html2canvas: capture DOM element as PNG --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" referrerpolicy="no-referrer"></script>
@endpush
