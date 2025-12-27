@extends('layouts.app')

@section('title', 'Payment Details')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4>Payment #{{ $payment->id }}</h4>
      <div class="text-muted small">Recorded: {{ $payment->created_at->format('d M Y H:i') }}</div>
    </div>
    <div>
      <a href="{{ route('secretary.payments.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
      {{-- <a href="{{ route('secretary.payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary">Edit</a> --}}
      <form action="{{ route('secretary.payments.destroy', $payment) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        {{-- <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment?')">Delete</button> --}}
      </form>
    </div>
  </div>

  <div class="card p-3">
    <div class="row">
      <div class="col-md-6">
        <p><strong>Student ID</strong>: {{ $payment->student_id }}</p>
        <p><strong>Amount</strong>: {{ $payment->currency }} {{ number_format($payment->amount, 2) }}</p>
        <p><strong>Method</strong>: {{ $payment->method ?? '—' }}</p>
      </div>
      <div class="col-md-6">
        <p><strong>Notes</strong>: {{ $payment->notes ?? '—' }}</p>
        <p><strong>Recorded by</strong>: {{ $payment->created_by ?? '—' }}</p>
      </div>
    </div>
  </div>
</div>
@endsection