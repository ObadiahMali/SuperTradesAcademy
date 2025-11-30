{{-- resources/views/secretary/expenses/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Edit Expense</h4>
      <div class="small text-muted">Update expense details</div>
    </div>
    <div>
      <a href="{{ route('secretary.expenses.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>
  </div>

  <div class="card p-3">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('secretary.expenses.update', $expense) }}" method="POST">
      @csrf
      @method('PATCH')

      <div class="mb-3">
        <label class="form-label">Title</label>
        <input type="text" name="title" value="{{ old('title', $expense->title) }}" class="form-control" required>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Amount</label>
          <input type="number" name="amount" step="0.01" value="{{ old('amount', $expense->amount) }}" class="form-control" required>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label">Currency</label>
          <select name="currency" class="form-select">
            <option value="UGX" @selected(old('currency', $expense->currency) == 'UGX')>UGX</option>
            <option value="USD" @selected(old('currency', $expense->currency) == 'USD')>USD</option>
          </select>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label">Spent at</label>
          <input type="datetime-local" name="spent_at" value="{{ old('spent_at', optional($expense->spent_at)->format('Y-m-d\TH:i')) }}" class="form-control">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $expense->notes) }}</textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Paid</label>
        <select name="paid" class="form-select">
          <option value="0" @selected(old('paid', $expense->paid) == 0)>Unpaid</option>
          <option value="1" @selected(old('paid', $expense->paid) == 1)>Paid</option>
        </select>
      </div>

      <div class="d-flex justify-content-between">
        <a href="{{ route('secretary.expenses.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <div>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection