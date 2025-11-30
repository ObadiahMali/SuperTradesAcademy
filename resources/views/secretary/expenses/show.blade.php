{{-- resources/views/secretary/expenses/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Expense Details')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Expense Details</h4>
      <div class="text-muted small">Recorded: {{ optional($expense->created_at)->format('d M Y H:i') }}</div>
    </div>
    <div class="text-end">
      <a href="{{ route('secretary.expenses.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
      <a href="{{ route('secretary.expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary">Edit</a>
      <form action="{{ route('secretary.expenses.destroy', $expense) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this expense?')">Delete</button>
      </form>
    </div>
  </div>

  <div class="card p-3 mb-3">
    <div class="row">
      <div class="col-md-6">
        <p><strong>Title</strong></p>
        <p class="mb-2">{{ $expense->title }}</p>

        <p><strong>Amount</strong></p>
        <p class="mb-2">{{ $expense->currency ?? 'UGX' }} {{ number_format($expense->amount, 2) }}</p>

        <p><strong>Spent at</strong></p>
        <p class="mb-2">{{ optional($expense->spent_at)->format('d M Y H:i') ?? '—' }}</p>
      </div>

      <div class="col-md-6">
        <p><strong>Paid</strong></p>
        <p class="mb-2">
          @if($expense->paid)
            <span class="badge bg-success">Paid</span>
            <small class="text-muted ms-2">at {{ optional($expense->paid_at)->format('d M Y H:i') }}</small>
          @else
            <span class="badge bg-warning text-dark">Unpaid</span>
          @endif
        </p>

        <p><strong>Notes</strong></p>
        <p class="mb-2">{{ $expense->notes ?? '—' }}</p>

        <p><strong>Recorded by</strong></p>
        <p class="mb-2">{{ optional($expense->creator)->name ?? 'System' }}</p>
      </div>
    </div>
  </div>

  {{-- Optional: quick actions --}}
  <div class="d-flex gap-2">
    @if(!$expense->paid)
      <form action="{{ route('secretary.expenses.togglePaid', $expense) }}" method="POST" class="d-inline">
        @csrf
        @method('PATCH')
        <button class="btn btn-success btn-sm">Mark as Paid</button>
      </form>
    @else
      <form action="{{ route('secretary.expenses.togglePaid', $expense) }}" method="POST" class="d-inline">
        @csrf
        @method('PATCH')
        <button class="btn btn-outline-warning btn-sm">Mark as Unpaid</button>
      </form>
    @endif

    <a href="{{ route('secretary.expenses.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
@endsection