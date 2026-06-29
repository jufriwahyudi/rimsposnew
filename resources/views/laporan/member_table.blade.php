@if ($members->isEmpty())
    <div class="alert alert-info">Tidak ada data member yang ditemukan.</div>
@else
    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="rounded-3 p-3 text-white" style="background:linear-gradient(135deg,#7c3aed,#4f46e5)">
                <div class="small opacity-75">Total Member</div>
                <div class="fs-4 fw-bold">{{ number_format($totalMember) }} Member</div>
                <div class="small opacity-75 mt-1">
                    @if($mulai && $akhir)
                        Registrasi: {{ date('d-m-Y', strtotime($mulai)) }} s/d {{ date('d-m-Y', strtotime($akhir)) }}
                    @else
                        Semua Periode Registrasi
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rounded-3 p-3 text-white" style="background:linear-gradient(135deg,#059669,#10b981)">
                <div class="small opacity-75">Total Poin Terkumpul</div>
                <div class="fs-4 fw-bold">{{ number_format($totalPoin) }} Poin</div>
                <div class="small opacity-75 mt-1">Seluruh Member Dalam Store/Business</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rounded-3 p-3 text-white" style="background:linear-gradient(135deg,#d97706,#f59e0b)">
                <div class="small opacity-75">Member Aktif</div>
                <div class="fs-4 fw-bold">{{ number_format($memberAktif) }} Member</div>
                <div class="small opacity-75 mt-1">Status Keanggotaan Aktif</div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th width="50" class="text-center">No</th>
                    <th>Nama Member</th>
                    <th>No. Telepon</th>
                    <th>Email</th>
                    <th class="text-center" width="130">Tanggal Lahir</th>
                    <th class="text-end" width="130">Total Poin</th>
                    <th class="text-center" width="100">Status</th>
                    <th class="text-center" width="160">Tanggal Terdaftar</th>
                    <th class="text-center" width="90">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($members as $i => $mem)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $mem->name }}</td>
                        <td>{{ $mem->phone ?? '-' }}</td>
                        <td>{{ $mem->email ?? '-' }}</td>
                        <td class="text-center">{{ $mem->birth_date ? $mem->birth_date->format('d-m-Y') : '-' }}</td>
                        <td class="text-end">
                            <span class="badge {{ $mem->total_points > 0 ? 'bg-success' : 'bg-secondary' }} fs-6">
                                {{ number_format($mem->total_points) }} Poin
                            </span>
                        </td>
                        <td class="text-center">
                            @if($mem->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $mem->created_at ? $mem->created_at->format('d-m-Y H:i') : '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('members.history', $mem->id) }}" class="btn btn-sm btn-outline-info" title="Lihat Riwayat Poin">
                                <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">history</i> Riwayat
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
