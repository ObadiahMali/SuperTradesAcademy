@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Reports</h1>

  <form method="get" action="{{ route('admin.reports.index') }}" class="mb-4">
    <div class="row g-2">
      <div class="col-auto">
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control" />
      </div>
      <div class="col-auto">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control" />
      </div>
      <div class="col-auto">
        <select name="type" class="form-select">
          <option value="all" {{ ($filters['type'] ?? '') === 'all' ? 'selected' : '' }}>All</option>
          <option value="payments" {{ ($filters['type'] ?? '') === 'payments' ? 'selected' : '' }}>Payments</option>
          <option value="students" {{ ($filters['type'] ?? '') === 'students' ? 'selected' : '' }}>Students</option>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-primary">Generate</button>
      </div>
      <div class="col-auto">
        <a href="{{ route('admin.reports.export', array_merge(request()->only(['from','to','type']), ['export' => 'csv'])) }}" class="btn btn-outline-secondary">Export CSV</a>
      </div>
    </div>
  </form>

  @if(!empty($summary))
    <div class="mb-3">
      <strong>Summary</strong>
      <div>Total payments: {{ $summary['total_payments'] ?? 0 }}</div>
      <div>Total paid (UGX): {{ number_format($summary['total_paid_ugx'] ?? 0, 2) }}</div>
      <div>Students in range: {{ $summary['students_count'] ?? 0 }}</div>
    </div>
  @endif

  @if(!empty($rows) && $rows->count())
    <table class="table table-sm">
      <thead>
        <tr>
          <th>Date</th>
          <th>Student</th>
          <th>Original</th>
          <th>Converted (UGX)</th>
          <th>Method</th>
          <th>Reference</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $r)
          <tr>
            <td>{{ $r['date'] }}</td>
            <td>{{ $r['student_name'] }} ({{ $r['student_id'] }})</td>
            <td>{{ $r['original_currency'] }} {{ number_format($r['original_amount'], 2) }}</td>
            <td class="text-end">UGX {{ number_format($r['converted_ugx'], 2) }}</td>
            <td>{{ $r['method'] }}</td>
            <td>{{ $r['reference'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <div class="alert alert-info">Use the filters above to generate a report. Export logic can be added in the controller.</div>
  @endif
</div>
@endsection