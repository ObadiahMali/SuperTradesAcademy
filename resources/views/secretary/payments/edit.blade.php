{{-- resources/views/secretary/payments/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Payment')

@section('content')
<div class="container">
    <h3>Edit Payment #{{ $payment->id }}</h3>

    {{-- Display error messages --}}
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('secretary.payments.update', $payment->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Amount</label>
            <input name="amount" type="text" class="form-control" 
                   value="{{ old('amount', $payment->amount) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Currency</label>
            <select name="currency" class="form-control">
                <option value="UGX" {{ old('currency', $payment->currency) === 'UGX' ? 'selected' : '' }}>UGX</option>
                <option value="USD" {{ old('currency', $payment->currency) === 'USD' ? 'selected' : '' }}>USD</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Method</label>
            <input name="method" type="text" class="form-control" 
                   value="{{ old('method', $payment->method) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control">{{ old('notes', $payment->notes) }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('secretary.payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
