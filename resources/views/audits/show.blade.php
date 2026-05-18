@extends('layouts.main.main')
@section('title', 'Daily Audits')

@section('content')
    <div class="card rounded-4">
        <div class="card-header">
            <h5 class="fw-bold">Daily Audit {{ $dailyAudit->audit_date->format('d-m-Y') }}</h5>
        </div>
        <div class="card-body">
            <p><strong>Total Sales:</strong> {{ number_format($dailyAudit->total_sales, 2) }}</p>
            <p><strong>Cash Difference:</strong> {{ number_format($dailyAudit->cash_difference, 2) }}</p>

            <hr>

            <table class="table table-sm table-bordered">
                <tr>
                    <th>Issue</th>
                    <th>Deskripsi</th>
                    <th>Expected</th>
                    <th>Actual</th>
                </tr>
                @foreach ($dailyAudit->details as $detail)
                    <tr>
                        <td>{{ $detail->issue_type }}</td>
                        <td>{{ $detail->description }}</td>
                        <td>{{ number_format($detail->expected_value, 2) }}</td>
                        <td>{{ number_format($detail->actual_value, 2) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
