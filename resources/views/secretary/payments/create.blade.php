@extends('layouts.app')
@section('title', 'Add Payment')
@section('content')
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card p-4">
      @if(isset($student) && $student)
        <h4 class="mb-3">Add Payment for {{ $student->first_name }} {{ $student->last_name }}</h4>
      @else
        <h4 class="mb-3">Add Payment</h4>
      @endif

      <form action="{{ route('secretary.payments.store') }}" method="POST">
        @csrf

        @if(isset($student) && $student)
          <input type="hidden" name="student_id" value="{{ $student->id }}">
        @endif

        <div class="mb-2">
          <label class="form-label small">Amount</label>
          <input
            name="amount"
            type="number"
            step="0.01"
            value="{{ old('amount', optional($student)->price ?? '') }}"
            class="form-control"
            required
          >
          @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="mb-2">
          <label class="form-label small">Currency</label>
          <select name="currency" class="form-select" required>
            <option value="UGX" {{ old('currency', optional($student)->currency ?? 'UGX') == 'UGX' ? 'selected' : '' }}>UGX</option>
            <option value="USD" {{ old('currency', optional($student)->currency ?? '') == 'USD' ? 'selected' : '' }}>USD</option>
          </select>
          @error('currency')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="mb-2">
          <label class="form-label small">Payment Method</label>
          <select name="method" class="form-select" required>
            <option value="Cash" {{ old('method') == 'Cash' ? 'selected' : '' }}>Cash</option>
            <option value="Mobile Money" {{ old('method') == 'Mobile Money' ? 'selected' : '' }}>Mobile Money</option>
            <option value="Bank Transfer" {{ old('method') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
          </select>
          @error('method')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="mb-2">
          <label class="form-label small">Notes</label>
          <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>

        <div class="mb-3 d-flex justify-content-between">
          @if(isset($student) && $student)
            <a href="{{ route('secretary.students.show', $student) }}" class="btn btn-outline-secondary">Cancel</a>
          @else
            <a href="{{ route('secretary.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
          @endif

          <button class="btn btn-success">Record Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection