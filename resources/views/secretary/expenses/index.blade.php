@extends('layouts.app')
@section('title','Expenses')
@section('content')

<style>
  /* Expenses index - polished, scoped */
  .page-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
  .page-sub { color:#64748b; font-size:0.95rem; }
  .card-clean { border-radius:12px; padding:18px; box-shadow:0 6px 18px rgba(10,25,47,0.04); background:#fff; }
  .controls { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
  .compact-btn { padding:8px 12px; font-size:0.92rem; border-radius:8px; }
  .search-input { min-width:220px; }

  .table-head { background:#f8fafc; font-weight:700; color:#0f172a; }
  .muted { color:#64748b; font-size:0.92rem; }
  .amount { font-weight:800; color:#c2410c; }

  .badge-type { font-weight:700; font-size:0.78rem; padding:6px 8px; border-radius:8px; display:inline-block; }
  .badge-fixed { background:#fff8ef; color:#92400e; border:1px solid rgba(146,64,14,0.06); }
  .badge-variable { background:#ecfeff; color:#065f46; border:1px solid rgba(6,95,70,0.06); }

  .empty-note { color:#94a3b8; }
  .small-note { font-size:0.85rem; color:#94a3b8; }

  /* Labeled action buttons */
  .actions { display:flex; justify-content:flex-end; align-items:center; gap:8px; flex-wrap:wrap; }
  .actions .btn { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:8px; font-size:0.88rem; white-space:nowrap; }

  @media (max-width:768px) {
    .controls { width:100%; justify-content:space-between; }
    .search-input { min-width:120px; }
    .actions { justify-content:flex-start; }
  }
  @media (max-width:576px) {
    .actions .btn { padding:6px 8px; font-size:0.82rem; }
  }
</style>

<div class="page-header">
  <div>
    <h3 class="mb-0">Expenses</h3>
    <div class="page-sub">Record and manage operational expenses. Apply filters to narrow results and review totals.</div>
  </div>

  <div class="controls">
    <a href="{{ route('secretary.expenses.create') }}" class="btn btn-primary compact-btn">
      <i class="bi bi-plus-lg me-1"></i> New expense
    </a>

    <form action="{{ route('secretary.expenses.index') }}" method="GET" class="d-flex align-items-center">
      <input name="q" value="{{ request('q') }}" class="form-control form-control-sm search-input" placeholder="Search description, vendor or title" />
    </form>

    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm compact-btn dropdown-toggle" data-bs-toggle="dropdown">Filter</button>
      <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:280px;">
        <form action="{{ route('secretary.expenses.index') }}" method="GET">
          <div class="mb-2">
            <label class="form-label small mb-1">Type</label>
            <select name="type" class="form-select form-select-sm">
              <option value="">All</option>
              <option value="fixed" @selected(request('type') == 'fixed')>Fixed</option>
              <option value="variable" @selected(request('type') == 'variable')>Variable</option>
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

          <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="{{ route('secretary.expenses.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="card card-clean">
  @if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="muted">Showing</div>
      <div style="font-weight:700">{{ $expenses->total() }} expenses</div>
    </div>

    <div class="text-end">
      <div class="muted small">Total (current page)</div>
      <div style="font-weight:700; font-size:1rem" class="amount">
        UGX {{ number_format($expenses->sum('amount'), 2) }}
      </div>
      <div class="small-note">Currency shown as recorded. Convert in reports if needed.</div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-head">
        <tr>
          <th style="width:56px"></th>
          <th>Title & Description</th>
          <th>Vendor</th>
          <th>Category & Type</th>
          <th>Date</th>
          <th class="text-end">Amount</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>

      <tbody>
        @forelse($expenses as $expense)
          <tr>
            <td>
              <div class="bg-light d-inline-flex align-items-center justify-content-center rounded" style="width:40px;height:40px;font-weight:700;color:#334155;">
                {{ strtoupper(substr($expense->vendor ?? $expense->title ?? 'E', 0, 1)) }}
              </div>
            </td>

            <td>
              <div style="font-weight:700">{{ $expense->title ?? '—' }}</div>
              <div class="small-note">{{ \Illuminate\Support\Str::limit($expense->description ?? '—', 80) }}</div>
            </td>

            <td>{{ $expense->vendor ?? '—' }}</td>

            <td>
              <div>
                @if(($expense->type ?? '') === 'fixed')
                  <span class="badge-type badge-fixed">Fixed</span>
                @elseif(($expense->type ?? '') === 'variable')
                  <span class="badge-type badge-variable">Variable</span>
                @else
                  <span class="small-note">—</span>
                @endif
              </div>
              <div class="muted small mt-1">{{ $expense->category ?? '' }}</div>
            </td>

            <td>{{ \Carbon\Carbon::parse($expense->incurred_at ?? $expense->created_at)->format('d M Y') }}</td>

           <td class="text-end">
  <div class="amount">{{ $expense->currency ?? 'UGX' }} {{ number_format($expense->amount, 2) }}</div>

  @if($expense->paid)
    <div class="mt-1">
      <span class="badge bg-success">Paid</span>
      @if($expense->paid_at)
        <small class="text-muted ms-2">{{ $expense->paid_at->format('d M Y') }}</small>
      @endif
    </div>
  @else
    <div class="mt-1">
      <span class="badge bg-danger">Unpaid</span>
    </div>
  @endif
</td>

           <td class="text-end actions">
  <!-- View -->
  <a href="{{ route('secretary.expenses.show', $expense->id) }}" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-eye me-1"></i> View
  </a>

  <!-- Edit -->
  <a href="{{ route('secretary.expenses.edit', $expense->id) }}" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-pencil me-1"></i> Edit
  </a>

  <!-- Duplicate -->
  <a href="{{ route('secretary.expenses.create', ['duplicate' => $expense->id]) }}" class="btn btn-sm btn-success">
    <i class="bi bi-files me-1"></i> Duplicate
  </a>

  <!-- Toggle Paid/Unpaid -->
  <form action="{{ route('secretary.expenses.togglePaid', $expense) }}" method="POST" class="d-inline-block">
    @csrf
    @method('PATCH')
    <button type="submit" class="btn btn-sm {{ $expense->paid ? 'btn-outline-warning' : 'btn-success' }}">
      <i class="bi {{ $expense->paid ? 'bi-x-circle' : 'bi-check2-circle' }} me-1"></i>
      {{ $expense->paid ? 'Mark Unpaid' : 'Mark Paid' }}
    </button>
  </form>

  <!-- Delete -->
  <form action="{{ route('secretary.expenses.destroy', $expense->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete expense?');">
    @csrf
    @method('DELETE')
    <button class="btn btn-sm btn-danger">
      <i class="bi bi-trash me-1"></i> Delete
    </button>
  </form>
</td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center empty-note">No expenses found</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="muted small">Showing page {{ $expenses->currentPage() }} of {{ $expenses->lastPage() }}</div>
    <div>{{ $expenses->withQueryString()->links() }}</div>
  </div>
</div>

@endsection