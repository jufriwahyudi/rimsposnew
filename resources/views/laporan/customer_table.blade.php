@if ($customers->isEmpty())
    <div class="alert alert-info">Tidak ada data customer yang ditemukan.</div>
@else
    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="rounded-3 p-3 text-white" style="background:linear-gradient(135deg,#7c3aed,#4f46e5)">
                <div class="small opacity-75">Total Customer</div>
                <div class="fs-5 fw-bold">{{ $customers->count() }}</div>
                <div class="small opacity-75 mt-1">
                    @if($mulai && $akhir)
                        Registrasi: {{ date('d-m-Y', strtotime($mulai)) }} s/d {{ date('d-m-Y', strtotime($akhir)) }}
                    @else
                        Semua Periode Registrasi
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th width="50" class="text-center">No</th>
                    <th>Nama Customer</th>
                    <th>No. Telepon</th>
                    <th>Alamat</th>
                    <th class="text-center" width="160">Tanggal Registrasi</th>
                    @foreach ($customFields as $field)
                        <th>{{ $field->label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($customers as $i => $cust)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $cust->name }}</td>
                        <td>{{ $cust->phone ?? '-' }}</td>
                        <td>{{ $cust->alamat ?? '-' }}</td>
                        <td class="text-center">{{ $cust->created_at ? $cust->created_at->format('d-m-Y H:i') : '-' }}</td>
                        @foreach ($customFields as $field)
                            <td>
                                {{ $cust->custom_values[$field->name] ?? '-' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
