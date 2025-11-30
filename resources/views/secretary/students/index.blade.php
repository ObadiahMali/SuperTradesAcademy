@extends('layouts.app')
@section('title','Students')
@section('content')

<style>
  .page-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
  .page-sub { color:#64748b; font-size:0.95rem; }
  .card-clean { border-radius:12px; padding:16px; box-shadow:0 1px 6px rgba(2,6,23,0.04); }
  .table-head { background:#f8fafc; font-weight:700; color:#0f172a; }
  .controls { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .compact-btn { padding:8px 12px; font-size:0.92rem; border-radius:8px; }
  .search-input { min-width:220px; }
  .table-avatar { width:40px; height:40px; border-radius:8px; object-fit:cover; }
  .muted { color:#64748b; font-size:0.92rem; }
  .amount { font-weight:800; color:#0b6ef6; }
  .badge-status { font-weight:700; font-size:0.78rem; padding:6px 8px; border-radius:8px; }
  .badge-active { background:#ecfeff; color:#065f46; border:1px solid rgba(6,95,70,0.08); }
  .empty-note { color:#94a3b8; }
  .actions-compact { display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap; }
  .actions-compact .btn { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:8px; font-size:0.88rem; white-space:nowrap; }
  @media (max-width:768px) {
    .page-header { gap:8px; }
    .controls { width:100%; justify-content:space-between; }
    .actions-compact { justify-content:flex-start; }
  }
  @media (max-width:576px) {
    .actions-compact { gap:6px; }
    .actions-compact .btn { padding:6px 8px; font-size:0.82rem; }
  }
</style>

<div class="page-header">
  <div>
    <h3 class="mb-0">Students</h3>
    <div class="page-sub">All registered students. Use the controls to search, filter and register new students.</div>
  </div>

  <div class="controls">
    @if(isset($activeIntake) && $activeIntake)
      <a href="{{ route('secretary.students.create', ['intake' => $activeIntake->id]) }}" class="btn btn-primary compact-btn">
        <i class="bi bi-person-plus me-1"></i> Register student
      </a>
    @else
      <a href="{{ route('secretary.students.create') }}" class="btn btn-outline-primary compact-btn">
        <i class="bi bi-person-plus me-1"></i> Register student
      </a>
    @endif

    <form action="{{ route('secretary.students.index') }}" method="GET" class="d-flex align-items-center">
      <input name="q" value="{{ request('q') }}" class="form-control form-control-sm search-input" placeholder="Search name, email or phone" />
    </form>

    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm compact-btn dropdown-toggle" data-bs-toggle="dropdown">Filter</button>
      <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:260px;">
        <form action="{{ route('secretary.students.index') }}" method="GET">
          <div class="mb-2">
            <label class="form-label small mb-1">Intake</label>
            <select name="intake_id" class="form-select form-select-sm">
              <option value="">All</option>
              @foreach($intakes as $i)
                <option value="{{ $i->id }}" @selected(request('intake_id') == $i->id)>{{ $i->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="{{ route('secretary.students.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="card card-clean">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="muted">Showing</div>
      <div style="font-weight:700">{{ $students->total() }} students</div>
    </div>
    <div class="muted">Updated: {{ \Carbon\Carbon::now()->format('d M Y, H:i') }}</div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-head">
        <tr>
          <th style="width:56px"></th>
          <th>Name</th>
          <th>Intake</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Registered</th>
          <th class="text-end">Amount Due</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($students as $student)
          @php
            $plan = config("pricing.plans.{$student->plan_key}") ?? ['price'=>0,'currency'=>'UGX'];
            $converted = $plan['currency'] === 'USD'
                ? app(\App\Services\ExchangeRateService::class)->usdToUgx($plan['price'])
                : $plan['price'];
            $courseFeeUGX = strtoupper($student->currency ?? 'UGX') === 'UGX'
                ? ($student->course_fee ?? $converted)
                : $converted;
            $totalPaidUGX = $student->payments->sum('amount_converted');
            $dueUGX = max(0, $courseFeeUGX - $totalPaidUGX);
          @endphp

          <tr>
            <td>
              @if(!empty($student->avatar))
                <img src="{{ $student->avatar }}" alt="avatar" class="table-avatar" />
              @else
                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded" style="width:40px;height:40px;font-weight:700;color:#334155;">
                  {{ strtoupper(substr($student->first_name ?? 'U',0,1)) }}
                </div>
              @endif
            </td>

            <td>
              <div style="font-weight:700">{{ $student->first_name }} {{ $student->last_name }}</div>
              <div class="muted small">{{ $student->student_id ?? '' }}</div>
            </td>

            <td>
              <div>{{ optional($student->intake)->name ?? '—' }}</div>
              @if(optional($student->intake)->active)
                <div class="mt-1"><span class="badge-status badge-active">Active intake</span></div>
              @endif
            </td>

            <td>{{ $student->email ?? '—' }}</td>
            <td>{{ $student->phone ?? '—' }}</td>
            <td>{{ \Carbon\Carbon::parse($student->created_at)->format('d M Y') }}</td>

        <td class="text-end">
  <div class="amount">
    @if(($student->amount_due ?? 0) > 0)
      <span style="color:red">UGX {{ number_format($student->amount_due, 2) }}</span>
    @else
      <span style="color:green">No outstanding balance</span>
    @endif
  </div>
  <div class="muted small">Balance</div>
</td>

            <td class="text-end">
              <div class="actions-compact" role="group" aria-label="Actions for student {{ $student->id }}">
                <a href="{{ route('secretary.students.show', $student->id) }}" class="btn btn-sm btn-outline-primary" title="View Student">
                  <i class="bi bi-eye me-1"></i> View
                </a>
                               <a href="{{ route('secretary.students.edit', $student->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit Student">
                  <i class="bi bi-pencil me-1"></i> Edit
                </a>

                <a href="{{ route('secretary.payments.create', ['student' => $student->id]) }}" class="btn btn-sm btn-success" title="Record Payment">
                  <i class="bi bi-currency-dollar me-1"></i> Record Payment
                </a>

                <form action="{{ route('secretary.students.destroy', $student->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete student?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-danger" title="Delete Student">
                    <i class="bi bi-trash me-1"></i> Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center empty-note">No students found</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="muted small">Showing page {{ $students->currentPage() }} of {{ $students->lastPage() }}</div>
    <div>
      {{ $students->withQueryString()->links() }}
    </div>
  </div>
</div>

@endsection