<!-- <table>
    <thead>
        <tr>
            <th>Produk / Varian</th>
            <th>Warehouse</th>
            <th>Store</th>
            <th>Total</th>
            <th>Modal</th>
            <th>Nilai Persediaan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)

            <tr>
                <td colspan="6"><strong>{{ $product->nama_produk }}</strong></td>
            </tr>

            @foreach($product->variants as $variant)
                @php
                    $stokWarehouse = $variant->stock_warehouse ?? 0;
                    $stokStore = $variant->stock_store ?? 0;
                    $totalStok = $stokWarehouse + $stokStore;
                    $modal = $variant->modalPerTanggal($tanggal);
                @endphp
                <tr>
                    <td>
                        {{ $variant->sku }} <br>
                        {{ $variant->variant_label }}
                    </td>
                    <td>{{ $stokWarehouse }}</td>
                    <td>{{ $stokStore }}</td>
                    <td>{{ $totalStok }}</td>
                    <td>{{ $modal }}</td>
                    <td>{{ $modal * $totalStok }}</td>r
                </tr>
            @endforeach

        @endforeach
    </tbody>
</table> -->
