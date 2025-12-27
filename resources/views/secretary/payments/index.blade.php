@extends('layouts.app')
@section('title','Payments')
@section('content')

<style>
  /* Payments index - polished */
  .page-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
  .page-sub { color:#64748b; font-size:0.95rem; }

  .card-clean { border-radius:12px; padding:16px; box-shadow:0 1px 6px rgba(2,6,23,0.04); }
  .controls { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .compact-btn { padding:8px 12px; font-size:0.92rem; border-radius:8px; }
  .search-input { min-width:220px; }

  .table-head { background:#f8fafc; font-weight:700; color:#0f172a; }
  .muted { color:#64748b; font-size:0.92rem; }
  .amount { font-weight:800; color:#0b6ef6; }

  .badge-raw { font-weight:700; font-size:0.78rem; padding:6px 8px; border-radius:8px; }
  .badge-ugx { background:#ecfeff; color:#065f46; border:1px solid rgba(6,95,70,0.06); }
  .badge-usd { background:#fff8ef; color:#92400e; border:1px solid rgba(146,64,14,0.06); }

  .table-avatar { width:36px; height:36px; border-radius:8px; object-fit:cover; }
  .empty-note { color:#94a3b8; }

  /* Action buttons group */
  .actions-group { display:flex; gap:8px; justify-content:flex-end; align-items:center; flex-wrap:wrap; }
  .actions-group .btn { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:8px; font-size:0.88rem; white-space:nowrap; }

  @media (max-width:768px) {
    .controls { width:100%; justify-content:space-between; }
    .actions-group { justify-content:flex-start; }
  }

  @media (max-width:576px) {
    .actions-group { gap:6px; }
    .actions-group .btn { padding:6px 8px; font-size:0.82rem; }
  }
</style>

<div class="page-header">
  <div>
    <h3 class="mb-0">Payments</h3>
    <div class="page-sub">All receipts and incoming payments. Search, filter and export.</div>
  </div>

  <div class="controls">
    <a href="{{ route('secretary.payments.index') }}" class="btn btn-outline-secondary compact-btn">
      <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </a>

   <form action="{{ route('secretary.payments.index') }}" method="GET" class="d-flex align-items-center gap-2">
  <input name="q" value="{{ request('q') }}" class="form-control form-control-sm search-input" placeholder="Search reference, student or amount" autocomplete="off" />
  <button type="submit" class="btn btn-outline-secondary btn-sm">Search</button>
</form>



    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm compact-btn dropdown-toggle" data-bs-toggle="dropdown">Filter</button>
      <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:260px;">
        <form action="{{ route('secretary.payments.index') }}" method="GET">
          <div class="mb-2">
            <label class="form-label small mb-1">Currency</label>
            <select name="currency" class="form-select form-select-sm">
              <option value="">Any</option>
              <option value="UGX" @selected(request('currency')=='UGX')>UGX</option>
              <option value="USD" @selected(request('currency')=='USD')>USD</option>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label small mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" />
          </div>

          <div class="mb-2">
            <label class="form-label small mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" />
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="{{ route('secretary.payments.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
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
      <div style="font-weight:700">{{ $payments->total() }} payments</div>
    </div>

    <div class="text-end">
      <div class="muted small">Totals</div>
      @php
        $totalUGX = $payments->where('currency','UGX')->sum('amount') + ($ugxThisMonth ?? 0);
        $totalUSD = $payments->where('currency','USD')->sum('amount') + ($usdThisMonth ?? 0);
      @endphp
      <div style="font-weight:700">
        <span class="badge-raw badge-ugx me-2">UGX {{ number_format($totalUGX,2) }}</span>
        <span class="badge-raw badge-usd">USD {{ number_format($totalUSD,2) }}</span>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-head">
        <tr>
          <th style="width:56px"></th>
          <th>Reference</th>
          <th>Student</th>
          <th>Intake</th>
          <th>Date</th>
          <th class="text-end">Amount</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>

      <tbody>
        @forelse($payments as $payment)
          <tr>
            <td>
              @if(optional($payment->student)->avatar)
                <img src="{{ $payment->student->avatar }}" alt="avatar" class="table-avatar" />
              @else
                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;font-weight:700;color:#334155;">
                  {{ strtoupper(substr(optional($payment->student)->first_name ?? 'U',0,1)) }}
                </div>
              @endif
            </td>

            <td>
              <div style="font-weight:700">{{ $payment->reference ?? '—' }}</div>
              <div class="muted small">{{ $payment->method ?? '' }}</div>
            </td>

            <td>
              <div style="font-weight:700">{{ optional($payment->student)->first_name ?? 'Unknown' }} {{ optional($payment->student)->last_name ?? '' }}</div>
              <div class="muted small">{{ optional($payment->student)->student_id ?? '' }}</div>
            </td>

            <td>{{ optional($payment->student->intake)->name ?? '—' }}</td>

            <td>{{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y, H:i') : '—' }}</td>

            <td class="text-end">
              <div class="amount">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</div>
              <div class="muted small">{{ $payment->status ?? '' }}</div>
            </td>

<td class="text-end">
 <div class="actions-group" role="group" aria-label="Actions for payment {{ $payment->id }}">
    @if(\Illuminate\Support\Facades\Route::has('secretary.payments.show'))
      <a href="{{ route('secretary.payments.show', $payment->id) }}" class="btn btn-sm btn-outline-secondary" title="Details">
        <i class="bi bi-eye me-1"></i> Details
      </a>
    @endif

    {{-- Edit: visible only to administrators --}}
    @if(\Illuminate\Support\Facades\Route::has('secretary.payments.edit') && auth()->check() && (method_exists(auth()->user(), 'hasRole') ? auth()->user()->hasRole('administrator') : (auth()->user()->role === 'administrator')))
      <a href="{{ route('secretary.payments.edit', $payment->id) }}" class="btn btn-sm btn-outline-primary ms-1" title="Edit payment">
        <i class="bi bi-pencil me-1"></i> Edit
      </a>
    @else
      <button type="button" class="btn btn-sm btn-outline-primary ms-1" disabled title="Edit unavailable">
        <i class="bi bi-pencil me-1"></i> Edit
      </button>
    @endif

    @if(\Illuminate\Support\Facades\Route::has('secretary.students.show') && optional($payment->student)->id)
      <a href="{{ route('secretary.students.show', $payment->student->id) }}" class="btn btn-sm btn-outline-primary" title="Student">
        <i class="bi bi-person-lines-fill me-1"></i> Student
      </a>
    @else
      <button type="button" class="btn btn-sm btn-outline-primary" disabled title="Student unavailable">
        <i class="bi bi-person-lines-fill me-1"></i> Student
      </button>
    @endif

    @if(\Illuminate\Support\Facades\Route::has('secretary.payments.receipt'))
      <a href="{{ route('secretary.payments.receipt', $payment->id) }}" class="btn btn-sm btn-outline-success" title="Receipt">
        <i class="bi bi-receipt me-1"></i> Receipt
      </a>
    @else
      <button type="button" class="btn btn-sm btn-outline-success" disabled title="Receipt unavailable">
        <i class="bi bi-receipt me-1"></i> Receipt
      </button>
    @endif

    {{-- Delete button: visible only if route exists and user is authorized (admin) --}}
    @if(\Illuminate\Support\Facades\Route::has('secretary.payments.destroy') && auth()->check() && (method_exists(auth()->user(), 'hasRole') ? auth()->user()->hasRole('administrator') : (auth()->user()->role === 'administrator')))
      <form action="{{ route('secretary.payments.destroy', $payment->id) }}" method="POST" class="d-inline-block ms-1" onsubmit="return confirm('Are you sure you want to delete this payment?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger" title="Delete payment">
          <i class="bi bi-trash me-1"></i> Delete
        </button>
      </form>
    @else
      <button type="button" class="btn btn-sm btn-danger ms-1" disabled title="Delete unavailable">
        <i class="bi bi-trash me-1"></i> Delete
      </button>
    @endif
</div>

</td>

          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center empty-note">No payments found</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="muted small">Showing page {{ $payments->currentPage() }} of {{ $payments->lastPage() }}</div>

      <div class="mt-3">
          {{ $payments->withQueryString()->links('pagination::bootstrap-5') }}
      </div>             
   
  </div>
</div>

@endsection