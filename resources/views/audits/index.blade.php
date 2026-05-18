@extends('layouts.main.main')
@section('title', 'Daily Audits')

@section('content')
    <div class="card rounded-4">
        <div class="card-header">
            <h5 class="fw-bold">Daily Audits</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th>Tanggal</th>
                    <th>Total Sales</th>
                    <th>Cash Diff</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                @foreach ($audits as $audit)
                    <tr>
                        <td>{{ $audit->audit_date->format('d-m-Y') }}</td>
                        <td>{{ number_format($audit->total_sales, 2) }}</td>
                        <td>{{ number_format($audit->cash_difference, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $audit->status == 'OK' ? 'success' : 'danger' }}">
                                {{ $audit->status }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('audits.show', $audit) }}" class="btn btn-sm btn-info">Detail</a>
                        </td>
                    </tr>
                @endforeach
            </table>

            {{ $audits->links() }}
        </div>
    </div>
@endsection
