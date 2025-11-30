{{-- resources/views/secretary/intakes/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Intake')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card p-4">
      <h4 class="mb-3">Edit Intake — {{ $intake->name }}</h4>

      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif

      <form action="{{ route('secretary.intakes.update', $intake) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label class="form-label small">Name</label>
          <input name="name" type="text" class="form-control" value="{{ old('name', $intake->name) }}" required>
          @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="row g-2">
          <div class="col-md-6 mb-2">
            <label class="form-label small">Start Date</label>
            <input name="start_date" type="date" class="form-control"
                   value="{{ old('start_date', optional($intake->start_date)->format('Y-m-d')) }}">
            @error('start_date')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6 mb-2">
            <label class="form-label small">End Date</label>
            <input name="end_date" type="date" class="form-control"
                   value="{{ old('end_date', optional($intake->end_date)->format('Y-m-d')) }}">
            @error('end_date')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Ensure a value is always submitted for the checkbox --}}
        <input type="hidden" name="active" value="0">

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                 {{ old('active', $intake->active) ? 'checked' : '' }}>
          <label class="form-check-label small" for="active">Active</label>
        </div>

        <div class="mb-3">
          <label class="form-label small">Description (optional)</label>
          <textarea name="description" rows="3" class="form-control">{{ old('description', $intake->description) }}</textarea>
          @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex justify-content-between">
          <a href="{{ route('secretary.intakes.show', $intake) }}" class="btn btn-outline-secondary">Cancel</a>
          <div>
            <button type="submit" class="btn btn-primary">Save changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection