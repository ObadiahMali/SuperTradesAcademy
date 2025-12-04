@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Reports</h1>

  <form method="get" action="{{ route('admin.reports.index') }}" class="mb-4">
    <div class="row g-2 align-items-center">
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
        <select name="plan" class="form-select">
          <option value="">All plans</option>
          @foreach(config('plans.plans') ?? [] as $key => $cfg)
            <option value="{{ $key }}" {{ ($filters['plan'] ?? '') === $key ? 'selected' : '' }}>
              {{ $cfg['label'] ?? ucwords(str_replace('_',' ',$key)) }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-auto">
        <button class="btn btn-primary">Generate</button>
      </div>

      <div class="col-auto">
        <a href="{{ route('admin.reports.export', array_merge(request()->only(['from','to','type','plan']), ['export' => 'csv'])) }}" class="btn btn-outline-secondary">Export CSV</a>
      </div>
    </div>
  </form>

  @if(!empty($summary))
    <div class="mb-3">
      <strong>Summary</strong>
      <div>Students in range: {{ $summary['students_count'] ?? 0 }}</div>

      @if(($filters['type'] ?? 'all') === 'students' || ($filters['type'] ?? 'all') === 'all')
        <div>Total expected (UGX): {{ number_format($summary['total_expected_ugx'] ?? 0, 2) }}</div>
        <div>Total paid by students (UGX): {{ number_format($summary['total_paid_students_ugx'] ?? 0, 2) }}</div>
        <div>Total unpaid (UGX): {{ number_format($summary['total_unpaid_ugx'] ?? 0, 2) }}</div>
      @endif
    </div>
  @endif

  @if(!empty($rows) && $rows->count())
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-nowrap">Date</th>
            <th>Student</th>
            <th class="d-none d-sm-table-cell">Plan</th>

            @if(($filters['type'] ?? 'all') === 'students')
              <th class="d-none d-sm-table-cell">Intake</th>
            @endif

            <th class="text-nowrap">Original (USD)</th>
            <th class="text-end text-nowrap">Converted (UGX)</th>

            @if(($filters['type'] ?? 'all') === 'students')
              <th class="text-end d-none d-sm-table-cell">Amount Paid (UGX)</th>
              <th class="text-end d-none d-sm-table-cell">Amount Due (UGX)</th>
            @else
              <th class="d-none d-sm-table-cell">Method</th>
              <th class="text-nowrap d-none d-sm-table-cell">Reference</th>
            @endif
          </tr>
        </thead>

        <tbody>
          @foreach($rows as $r)
            <tr>
              <td class="text-nowrap">{{ $r['date'] }}</td>
              <td>
                <div class="fw-semibold">{{ $r['student_name'] }}</div>
                <div class="text-muted small">ID: {{ $r['student_id'] }}</div>
              </td>

              <td class="d-none d-sm-table-cell">{{ $r['plan_label'] ?? ($r['plan_key'] ?? '—') }}</td>

              @if(($filters['type'] ?? 'all') === 'students')
                <td class="d-none d-sm-table-cell">{{ $r['intake'] ?? '—' }}</td>
              @endif

              <td class="text-nowrap">{{ $r['original_currency'] ?? 'USD' }} {{ number_format($r['original_amount'] ?? 0, 2) }}</td>
              <td class="text-end text-nowrap">UGX {{ number_format($r['converted_ugx'] ?? 0, 2) }}</td>

              @if(($filters['type'] ?? 'all') === 'students')
                <td class="text-end d-none d-sm-table-cell text-nowrap">UGX {{ number_format($r['amount_paid_ugx'] ?? 0, 2) }}</td>
                <td class="text-end d-none d-sm-table-cell text-nowrap">UGX {{ number_format($r['amount_due_ugx'] ?? 0, 2) }}</td>
              @else
                <td class="d-none d-sm-table-cell">{{ $r['method'] ?? '—' }}</td>
                <td class="text-nowrap d-none d-sm-table-cell">{{ $r['reference'] ?? '—' }}</td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="alert alert-info">Use the filters above to generate a report. No rows match the selected filters.</div>
  @endif
</div>
@endsection