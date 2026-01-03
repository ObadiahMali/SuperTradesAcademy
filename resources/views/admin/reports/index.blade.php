{{-- resources/views/admin/reports/index.blade.php --}}
@extends('layouts.app')

@section('content')
@php
  $filters = $filters ?? [];
  $intakes = $intakes ?? collect();
@endphp

<!-- Select2 CSS (CDN) - optional, safe to keep -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />

<style>
  /*
    Layout & overflow fixes
    - neutralize row negative margins by removing outer padding on small screens
    - ensure no element uses 100vw accidentally
    - keep filter controls compact and responsive
  */

  html, body { box-sizing: border-box; }

  .reports-wrapper { padding-left: 0; padding-right: 0; }
  @media (min-width: 576px) { .reports-wrapper { padding-left: .5rem; padding-right: .5rem; } }
  @media (min-width: 992px) { .reports-wrapper { padding-left: 1rem; padding-right: 1rem; } }

  /* Prevent elements from using 100vw which causes horizontal scroll */
  [style*="width:100vw"], .full-vw { width: 100% !important; max-width: 100%; box-sizing: border-box; }

  /* Compact filter controls */
  .report-filter-select { min-width: 140px; max-width: 320px; width: auto; }
  .report-filter-date   { min-width: 140px; max-width: 220px; width: auto; }

  @media (max-width: 575.98px) {
    .report-filter-select,
    .report-filter-date {
      width: 100% !important;
      min-width: 100%;
      max-width: 100%;
    }
  }

  /* Select2 height tweak (if used) */
  .select2-container--bootstrap4 .select2-selection--single {
    height: calc(1.5em + .75rem + 2px);
    padding: .375rem .75rem;
  }

  /* Filters row spacing */
  .filters-row { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
  .filters-row .form-control, .filters-row .form-select { height: calc(1.5em + .75rem + 2px); }

  /* Table responsiveness */
  .table-responsive { overflow-x:auto; -webkit-overflow-scrolling: touch; }

  /* Small-screen inline details (visible only on xs) */
  .small-row-details { display:block; margin-top:.35rem; font-size:.875rem; color:#6c757d; }
  .small-row-details .label { font-weight:600; color:#495057; margin-right:.35rem; }
  @media (min-width: 576px) {
    .small-row-details { display:none !important; }
  }

  /* Summary box */
  .summary-box { background:#fff; padding:.75rem; border-radius:.375rem; border:1px solid #e9ecef; margin-bottom:.75rem; }

  /* Pagination wrapper */
  .pagination-wrapper { margin-top:.75rem; display:flex; justify-content:flex-end; }
</style>

<div class="container-fluid reports-wrapper px-0">
  <div class="row gx-0 mx-0">
    <div class="col-12 px-0">
      <h1 class="h4 mb-3">Reports</h1>

      <form method="get" action="{{ route('admin.reports.index') }}" class="mb-3">
        <div class="filters-row">
          <div class="me-2 mb-2" style="min-width:0;">
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm report-filter-date" />
          </div>

          <div class="me-2 mb-2" style="min-width:0;">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm report-filter-date" />
          </div>

          <div class="me-2 mb-2" style="min-width:0;">
            <select name="type" class="form-select form-select-sm report-filter-select">
              <option value="all" {{ ($filters['type'] ?? '') === 'all' ? 'selected' : '' }}>All</option>
              <option value="payments" {{ ($filters['type'] ?? '') === 'payments' ? 'selected' : '' }}>Payments</option>
              <option value="students" {{ ($filters['type'] ?? '') === 'students' ? 'selected' : '' }}>Students</option>
            </select>
          </div>

          <div class="me-2 mb-2" style="min-width:0;">
            <select name="plan" class="form-select form-select-sm report-filter-select">
              <option value="">All plans</option>
              @foreach(config('plans.plans') ?? [] as $key => $cfg)
                <option value="{{ $key }}" {{ ($filters['plan'] ?? '') === $key ? 'selected' : '' }}>
                  {{ $cfg['label'] ?? ucwords(str_replace('_',' ',$key)) }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="me-2 mb-2" style="min-width:0;">
            <select name="intake" class="form-select form-select-sm report-filter-select">
              <option value="">All intakes</option>
              @if(!empty($intakes) && is_iterable($intakes))
                @foreach($intakes as $i)
                  <option value="{{ $i->id }}" {{ (string)($filters['intake'] ?? '') === (string)$i->id ? 'selected' : '' }}>
                    {{ $i->name }} @if(isset($i->start_date)) ({{ \Carbon\Carbon::parse($i->start_date)->format('Y') }})@endif
                  </option>
                @endforeach
              @endif
            </select>
          </div>

          <div class="me-2 mb-2">
            <button class="btn btn-primary btn-sm">Generate</button>
          </div>

          <div class="me-2 mb-2">
            <a href="{{ route('admin.reports.export', array_merge(request()->only(['from','to','type','plan','intake']), ['export' => 'csv'])) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
          </div>
        </div>
      </form>

   @if(!empty($summary))
  <div class="summary-box small">
    <div><strong>Summary</strong></div>

    <div class="mt-1">Students in range: <span class="fw-semibold">{{ $summary['students_count'] ?? 0 }}</span></div>

    @if(($filters['type'] ?? 'all') === 'payments')
      <div class="mt-1">Payments in range: <span class="fw-semibold">{{ $summary['total_payments'] ?? 0 }}</span></div>
      <div class="mt-1">Total paid (UGX): <span class="fw-semibold">{{ number_format($summary['total_paid_ugx'] ?? 0, 2) }}</span></div>

      {{-- Show expected / paid / unpaid for the students represented by these payments --}}
      <div class="mt-1">Total expected (UGX): <span class="fw-semibold">{{ number_format($summary['total_expected_ugx'] ?? 0, 2) }}</span></div>
      <div class="mt-1">Total paid by those students (UGX): <span class="fw-semibold">{{ number_format($summary['total_paid_students_ugx'] ?? ($summary['total_paid_ugx'] ?? 0), 2) }}</span></div>
      <div class="mt-1">Total unpaid (UGX): <span class="fw-semibold">{{ number_format($summary['total_unpaid_ugx'] ?? max(0, ($summary['total_expected_ugx'] ?? 0) - ($summary['total_paid_students_ugx'] ?? ($summary['total_paid_ugx'] ?? 0))), 2) }}</span></div>

    @else
      <div class="mt-1">Total expected (UGX): <span class="fw-semibold">{{ number_format($summary['total_expected_ugx'] ?? 0, 2) }}</span></div>
      <div class="mt-1">Total paid by students (UGX): <span class="fw-semibold">{{ number_format($summary['total_paid_students_ugx'] ?? 0, 2) }}</span></div>
      <div class="mt-1">Total unpaid (UGX): <span class="fw-semibold">{{ number_format($summary['total_unpaid_ugx'] ?? 0, 2) }}</span></div>
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
                <th class="d-none d-sm-table-cell">Intake</th>
                <th class="text-end text-nowrap">Original (USD)</th>
                <th class="text-end text-nowrap">Converted (UGX)</th>
                <th class="text-end d-none d-sm-table-cell">Amount Paid (UGX)</th>
                <th class="text-end d-none d-sm-table-cell">Amount Due (UGX)</th>
              </tr>
            </thead>

            <tbody>
              @foreach($rows as $r)
                <tr>
                  <td class="text-nowrap">{{ $r['date'] }}</td>

                  <td>
                    <div class="fw-semibold">{{ $r['student_name'] }}</div>
                    <div class="text-muted small">ID: {{ $r['student_id'] }}</div>

                    {{-- Small-screen inline details: Plan, Intake, Paid, Due --}}
                    <div class="small-row-details">
                      <div><span class="label">Plan:</span><span>{{ $r['plan_label'] ?? ($r['plan_key'] ?? '—') }}</span></div>
                      <div><span class="label">Intake:</span><span>{{ $r['intake_name'] ?? $r['intake'] ?? '—' }}</span></div>
                      <div><span class="label">Paid:</span><span>UGX {{ number_format($r['amount_paid_ugx'] ?? ($r['converted_ugx'] ?? 0), 2) }}</span></div>
                      <div><span class="label">Due:</span><span>UGX {{ number_format($r['amount_due_ugx'] ?? 0, 2) }}</span></div>
                    </div>
                  </td>

                  <td class="d-none d-sm-table-cell">{{ $r['plan_label'] ?? ($r['plan_key'] ?? '—') }}</td>

                  <td class="d-none d-sm-table-cell">{{ $r['intake_name'] ?? $r['intake'] ?? '—' }}</td>

                  <td class="text-end">{{ $r['original_currency'] ?? 'USD' }} {{ number_format($r['original_amount'] ?? 0, 2) }}</td>
                  <td class="text-end">UGX {{ number_format($r['converted_ugx'] ?? 0, 2) }}</td>

                  <td class="text-end d-none d-sm-table-cell">UGX {{ number_format($r['amount_paid_ugx'] ?? ($r['converted_ugx'] ?? 0), 2) }}</td>
                  <td class="text-end d-none d-sm-table-cell">UGX {{ number_format($r['amount_due_ugx'] ?? 0, 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @if(method_exists($rows, 'links'))
          <div class="pagination-wrapper">
            {!! $rows->appends(request()->query())->links('pagination::bootstrap-5') !!}
          </div>
        @endif
      @else
        <div class="alert alert-info">Use the filters above to generate a report. No rows match the selected filters.</div>
      @endif
    </div>
  </div>
</div>

<!-- jQuery + Select2 JS (CDN) - initialization commented out (optional) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  // If you want Select2, uncomment and test. It can cause layout changes on some setups.
   $(document).ready(function () {
     $('select').select2({  dropdownAutoWidth: true });
   });
</script>
@endsection