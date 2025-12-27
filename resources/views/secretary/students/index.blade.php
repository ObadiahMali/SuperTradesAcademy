{{-- resources/views/secretary/students/index.blade.php --}}
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
 <div class="controls d-flex align-items-center gap-2">
  @if(!empty($activeIntake))
    <a href="{{ route('secretary.students.create', ['intake' => $activeIntake->id]) }}" class="btn btn-primary compact-btn">
      <i class="bi bi-person-plus me-1"></i> Register student
    </a>
  @else
    <a href="{{ route('secretary.students.create') }}" class="btn btn-outline-primary compact-btn">
      <i class="bi bi-person-plus me-1"></i> Register student
    </a>
  @endif

  <form method="GET" action="{{ route('secretary.students.index') }}" class="row g-2 align-items-center mb-0">
    <div class="col-auto">
      <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, email or phone" autocomplete="off">
    </div>

    <div class="col-auto d-flex gap-2">
      <button class="btn btn-outline-secondary">Search</button>

      {{-- Refresh: clears query and reloads the index --}}
      <a href="{{ route('secretary.students.index') }}" class="btn btn-outline-secondary" title="Refresh list">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
      </a>
    </div>
  </form>
</div>

</div>

<div class="card card-clean">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="muted">Showing</div>
      <div style="font-weight:700">{{ $students->total() ?? 0 }} students</div>
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
          <th>Plan</th>
          <th>Registered</th>
          <th class="text-end">Amount Due</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>

      <tbody>
        @forelse($students as $student)
          @php
            // safe display name
            $studentName = $student->full_name ?: trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

            // plan label only (no price)
            $planLabel = config("pricing.plans.{$student->plan_key}.label")
                         ?? config("plans.plans.{$student->plan_key}.label")
                         ?? ($student->plan_key ? ucfirst(str_replace('_',' ',$student->plan_key)) : '—');

            // compute due in UGX: use course_fee if set, otherwise 0; convert payments to UGX if needed
            $rates = app(\App\Services\ExchangeRateService::class);
            $courseFeeUGX = strtoupper($student->currency ?? 'UGX') === 'USD'
                ? $rates->usdToUgx((float) ($student->course_fee ?? 0))
                : (float) ($student->course_fee ?? 0);

            $totalPaidUGX = 0;
            foreach ($student->payments ?? collect() as $p) {
                $amt = $p->amount_converted ?? null;
                if ($amt === null) {
                    $amt = strtoupper($p->currency ?? 'UGX') === 'USD' ? $rates->usdToUgx((float)$p->amount) : (float)$p->amount;
                }
                $totalPaidUGX += $amt;
            }
            $dueUGX = max(0, $courseFeeUGX - $totalPaidUGX);

            // phone display: prefer phone_full, otherwise combine country code + phone, fallback to dash
            if (!empty($student->phone_full)) {
                $phoneDisplay = $student->phone_full;
            } elseif (!empty($student->phone_country_code) || !empty($student->phone)) {
                $dial = !empty($student->phone_country_code) ? ('+' . ltrim($student->phone_country_code, '+')) : '';
                $phoneDisplay = trim($dial . ' ' . ($student->phone ?? ''));
            } else {
                $phoneDisplay = '—';
            }
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
              <div style="font-weight:700">{{ $studentName }}</div>
              <div class="muted small">{{ $student->student_id ?? '' }}</div>
            </td>

            <td>
              <div>{{ optional($student->intake)->name ?? '—' }}</div>
              @if(optional($student->intake)->active)
                <div class="mt-1"><span class="badge-status badge-active">Active intake</span></div>
              @endif
            </td>

            <td>{{ $student->email ?? '—' }}</td>

            <td>{{ $phoneDisplay }}</td>

            <td>
              <div>{{ $planLabel }}</div>
            </td>

            <td>{{ optional($student->created_at)->format('d M Y') ?? '—' }}</td>

            <td class="text-end">
              <div class="amount">
                @if($dueUGX > 0)
                  <span style="color:red">UGX {{ number_format($dueUGX, 2) }}</span>
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

    @php
      $isAdmin = auth()->check() && (method_exists(auth()->user(), 'hasRole')
        ? auth()->user()->hasRole('administrator')
        : (auth()->user()->role === 'administrator'));
    @endphp

    @if($isAdmin)
      <a href="{{ route('secretary.students.edit', $student->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit Student">
        <i class="bi bi-pencil me-1"></i> Edit
      </a>

      <form action="{{ route('secretary.students.destroy', $student->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete student?');">
        @csrf
        @method('DELETE')
        <button class="btn btn-sm btn-danger" title="Delete Student">
          <i class="bi bi-trash me-1"></i> Delete
        </button>
      </form>
    @else
      <button type="button" class="btn btn-sm btn-outline-secondary disabled no-pointer" aria-disabled="true" title="Edit unavailable">
        <i class="bi bi-pencil me-1"></i> Edit
      </button>

      <button type="button" class="btn btn-sm btn-danger disabled no-pointer" aria-disabled="true" title="Delete unavailable">
        <i class="bi bi-trash me-1"></i> Delete
      </button>
    @endif

    <a href="{{ route('secretary.payments.create', ['student' => $student->id]) }}" class="btn btn-sm btn-success" title="Record Payment">
      <i class="bi bi-currency-dollar me-1"></i> Record Payment
    </a>
  </div>
</td>

          </tr>
        @empty
          <tr>
            <td colspan="9" class="text-center empty-note">No students found</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

 <div class="d-flex justify-content-between align-items-center mt-3">
  <div class="muted small">Showing page {{ $students->currentPage() }} of {{ $students->lastPage() }}</div>

  <div class="mt-3">
    {{ $students->withQueryString()->links('pagination::bootstrap-5') }}
  </div>
</div>

</div>


<script>
(function () {
  const input = document.getElementById('student-search');
  const box = document.getElementById('student-suggestions');
  if (!input || !box) return;

  let timer = null;

  function clearBox() {
    box.innerHTML = '';
    box.style.display = 'none';
  }

  function render(items) {
    box.innerHTML = '';
    if (!items || items.length === 0) {
      clearBox();
      return;
    }

    items.forEach(it => {
      const a = document.createElement('a');
      a.href = it.url || '#';
      a.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
      a.innerHTML = `
        <div class="ms-1 me-auto">
          <div style="font-weight:700">${escapeHtml(it.name)}</div>
          <div class="small text-muted">${escapeHtml(it.intake || '')} ${it.email ? '· ' + escapeHtml(it.email) : ''}</div>
        </div>
        <div class="text-end small text-muted">${escapeHtml(it.phone || '')}</div>
      `;
      // If you prefer clicking to fill input instead of navigate, replace with click handler:
      // a.addEventListener('click', function(e){ e.preventDefault(); input.value = it.name; clearBox(); });
      box.appendChild(a);
    });

    box.style.display = 'block';
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"'`=\/]/g, function (s) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
      })[s];
    });
  }

  input.addEventListener('input', function () {
    const q = this.value.trim();
    if (timer) clearTimeout(timer);
    if (q.length < 2) { // require at least 2 chars
      clearBox();
      return;
    }

    timer = setTimeout(() => {
      fetch(`{{ route('secretary.students.search') }}?q=${encodeURIComponent(q)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(resp => resp.ok ? resp.json() : Promise.reject(resp))
      .then(data => render(data))
      .catch(() => clearBox());
    }, 300);
  });

  // hide suggestions when clicking outside
  document.addEventListener('click', function (e) {
    if (!box.contains(e.target) && e.target !== input) {
      clearBox();
    }
  });

  // keyboard navigation (optional)
  input.addEventListener('keydown', function (e) {
    const items = box.querySelectorAll('a.list-group-item');
    if (!items.length) return;
    let idx = Array.from(items).findIndex(i => i.classList.contains('active'));
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (idx >= 0) items[idx].classList.remove('active');
      idx = Math.min(items.length - 1, idx + 1);
      items[idx].classList.add('active');
      items[idx].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (idx >= 0) items[idx].classList.remove('active');
      idx = Math.max(0, idx - 1);
      items[idx].classList.add('active');
      items[idx].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
      const active = box.querySelector('a.list-group-item.active');
      if (active) {
        e.preventDefault();
        window.location = active.href;
      }
    }
  });
})();
</script>
@endsection