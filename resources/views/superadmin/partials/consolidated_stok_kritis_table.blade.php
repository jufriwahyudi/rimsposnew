<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">#</th>
                <th>SKU</th>
                <th>Nama Produk & Varian</th>
                <th>Toko</th>
                <th class="text-end">Stok Gudang</th>
                <th class="text-end">Stok Toko</th>
                <th class="text-end pe-3">Total Sisa Stok</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($criticalVariants as $i => $v)
                <tr>
                    <td class="ps-3">{{ $i + 1 }}</td>
                    <td><code>{{ $v->sku }}</code></td>
                    <td>
                        <div class="fw-semibold text-slate-800">{{ $v->product->nama_produk ?? '-' }}</div>
                        @if($v->variant_label)
                            <small class="text-muted">Varian: {{ $v->variant_label }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-indigo-subtle text-indigo border-0 px-2.5 py-1 rounded">
                            <i class="bx bx-store me-1" style="font-size: 11px;"></i>{{ $v->store->name ?? 'Unknown Store' }}
                        </span>
                    </td>
                    <td class="text-end fw-semibold">{{ number_format($v->stok_warehouse) }}</td>
                    <td class="text-end fw-semibold">{{ number_format($v->stok_store) }}</td>
                    <td class="text-end pe-3">
                        <span class="badge {{ $v->stok_total <= 0 ? 'bg-danger' : 'bg-warning text-dark' }} border-0 px-3 py-1.5 rounded fw-bold" style="font-size: 12px;">
                            {{ number_format($v->stok_total) }} unit
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bx bx-check-shield text-success fs-1 d-block mb-2"></i>
                        Semua stok aman! Tidak ada varian produk di bawah ambang batas kritis (<= {{ $threshold }} unit).
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-3 border-top bg-light text-muted" style="font-size: 12px;">
    <span><i class="bx bx-info-circle me-1"></i> Data di atas disaring berdasarkan ambang batas stok kritis kurang dari atau sama dengan <strong>{{ $threshold }} unit</strong>.</span>
</div>
