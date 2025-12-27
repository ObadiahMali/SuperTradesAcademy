@extends('layouts.app')
@section('title','New Expense')
@section('content')

<style>
  /* New Expense form - professional */
  .page-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
  .page-sub { color:#64748b; font-size:0.95rem; }
  .card-form { border-radius:12px; padding:22px; box-shadow:0 6px 18px rgba(10,25,47,0.06); background:#ffffff; }
  .form-label { font-weight:600; color:#0f172a; font-size:0.95rem; }
  .form-help { color:#64748b; font-size:0.875rem; }
  .input-group-compact .form-control { padding:10px 12px; border-radius:8px; border:1px solid #e6eef8; }
  .textarea-lg { min-height:120px; border-radius:10px; resize:vertical; }
  .muted { color:#94a3b8; font-size:0.9rem; }
  .btn-primary { background:#0b6ef6; border-color:#0b6ef6; color:#fff; padding:10px 14px; border-radius:10px; font-weight:700; }
  .btn-outline { background:transparent; border:1px solid #e6eef8; color:#0f172a; padding:10px 12px; border-radius:10px; }
  .field-row { display:flex; gap:12px; flex-wrap:wrap; }
  .col-flex { flex:1 1 260px; min-width:180px; }
  .error { color:#c2410c; font-size:0.875rem; margin-top:6px; }
  .meta { font-size:0.85rem; color:#475569; margin-top:6px; }
  .label-inline { display:block; margin-bottom:6px; }
  @media (max-width:720px) {
    .field-row { flex-direction:column; }
  }
</style>

<div class="page-header">
  <div>
    <h3 class="mb-0">New Expense</h3>
    <div class="page-sub">Record an operational expense. Provide a clear title, amount and the reason for bookkeeping.</div>
  </div>

  <div class="muted">You can add attachments later from the expense details page</div>
</div>

<div class="card-form">
  <form action="{{ route('secretary.expenses.store') }}" method="POST" novalidate>
    @csrf

    <div class="field-row mb-3">
      <div class="col-flex">
        <label class="form-label label-inline">Title</label>
        <input name="title" value="{{ old('title') }}" class="form-control input-group-compact" placeholder="Short title (e.g., Office rent - May 2025)" required />
        @error('title') <div class="error">{{ $message }}</div> @enderror
        <div class="form-help mt-1">A concise title used in lists and reports</div>
      </div>

      <div style="width:180px">
        <label class="form-label label-inline">Amount (UGX)</label>
        <input name="amount" value="{{ old('amount') }}" type="number" step="0.01" class="form-control input-group-compact text-end" placeholder="0.00" required />
        @error('amount') <div class="error">{{ $message }}</div> @enderror
        <div class="form-help mt-1">Enter numeric value. Use two decimals for consistency</div>
      </div>
    </div>

    <div class="field-row mb-3">
      <div class="col-flex">
        <label class="form-label label-inline">Vendor</label>
        <input name="vendor" value="{{ old('vendor') }}" class="form-control input-group-compact" placeholder="Supplier or vendor name" />
        @error('vendor') <div class="error">{{ $message }}</div> @enderror
        <div class="form-help mt-1">Optional. Helps with vendor reports and reconciliation</div>
      </div>

      <div style="width:220px">
        <label class="form-label label-inline">Date incurred</label>
        <input name="incurred_at" value="{{ old('incurred_at', now()->toDateString()) }}" type="date" class="form-control input-group-compact" />
        @error('incurred_at') <div class="error">{{ $message }}</div> @enderror
        <div class="form-help mt-1">Date the expense was incurred</div>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label label-inline">Category</label>
      <select name="category" class="form-select input-group-compact">
        <option value="">Select category</option>
        <option value="office" @selected(old('category')=='office')>Office</option>
        <option value="supplies" @selected(old('category')=='supplies')>Supplies</option>
        <option value="rent" @selected(old('category')=='rent')>Rent</option>
        <option value="utilities" @selected(old('category')=='utilities')>Utilities</option>
        <option value="other" @selected(old('category')=='other')>Other</option>
      </select>
      @error('category') <div class="error">{{ $message }}</div> @enderror
      <div class="form-help mt-1">Choose the category that best fits this expense</div>
    </div>

    {{-- <div class="mb-3">
      <label class="form-label label-inline">Reason / Description</label>
      <textarea name="description" class="form-control textarea-lg input-group-compact" placeholder="Explain why this expense was necessary, what it covers, and any additional context">{{ old('description') }}</textarea>
      @error('description') <div class="error">{{ $message }}</div> @enderror
      <div class="form-help mt-1">Use 1–3 short sentences. This helps approvals and future audits.</div>
    </div> --}}

    <div class="field-row align-items-center mt-4">
      <div>
        <button type="submit" class="btn-primary">Save expense</button>
        <a href="{{ route('secretary.expenses.index') }}" class="btn-outline ms-2">Cancel</a>
      </div>

      <div class="meta ms-auto">
        <strong>Tip:</strong> Add attachments and approve the expense from the expense details page after saving.
      </div>
    </div>
  </form>
</div>

@endsection