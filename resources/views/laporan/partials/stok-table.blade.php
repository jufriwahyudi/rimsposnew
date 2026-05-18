<div class="card rounded-4">
    <div class="card-body">
        <h6 class="mb-3">
            Laporan Stok per {{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}
        </h6>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Produk / Varian</th>
                        <th class="text-center" width="10%">Warehouse</th>
                        <th class="text-center" width="10%">Store</th>
                        <th class="text-center" width="10%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $productId => $variants)
                        {{-- ROW PRODUK --}}
                        <tr class="table-secondary fw-bold">
                            <td colspan="4">
                                <i class="bx bx-package me-1"></i>
                                {{ $variants->first()->product->nama_produk }}
                            </td>
                        </tr>

                        {{-- ROW VARIAN --}}
                        @foreach ($variants as $item)
                            @php
                                $total = ($item->stok_warehouse ?? 0) + ($item->stok_store ?? 0);
                            @endphp
                            <tr>
                                <td style="padding-left: 30px">
                                    <i class="bx bx-chevron-right text-muted"></i>
                                    {{ $item->sku }}<br>
                                    <small class="text-muted" style="font-size: 11px; padding-left: 20px;">
                                        {{ $item->variant_label ?? 'Tidak ada varian' }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    {{ $item->stok_warehouse ?? 0 }}
                                </td>
                                <td class="text-end">
                                    {{ $item->stok_store ?? 0 }}
                                </td>
                                <td class="text-end fw-semibold">
                                    {{ $total }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
