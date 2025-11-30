@extends('layouts.app')

@section('title','Edit Plan')

@section('content')
<div class="container">
  <div class="mb-3 d-flex justify-content-between align-items-center">
    <h3 class="mb-0">Edit Plan: {{ $plan->key }}</h3>
    <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label class="form-label">Label</label>
          <input name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label', $plan->label) }}" required>
          @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Price</label>
          <div class="input-group">
            <input name="price" type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $plan->price) }}" required>
            <span class="input-group-text">{{ $plan->currency }}</span>
            @error('price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Currency</label>
          <input name="currency" class="form-control @error('currency') is-invalid @enderror" value="{{ old('currency', $plan->currency) }}" required>
          @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button class="btn btn-primary" type="submit">Save</button>
        <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary">Cancel</a>
      </form>
    </div>
  </div>
</div>
@endsection