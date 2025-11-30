@extends('layouts.app')
@section('title','Add Employee')
@section('content')

<div class="page-header mb-3">
  <h3 class="mb-0">Add Employee</h3>
</div>

<div class="card card-clean p-3">
  <form action="{{ route('admin.employees.store') }}" method="POST">
    @csrf

    <div class="mb-2">
      <label class="form-label small">Name</label>
      <input name="name" value="{{ old('name') }}" class="form-control form-control-sm" required>
      @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mb-2">
      <label class="form-label small">Email</label>
      <input name="email" type="email" value="{{ old('email') }}" class="form-control form-control-sm" required>
      @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mb-2">
      <label class="form-label small">Position</label>
      <input name="position" value="{{ old('position') }}" class="form-control form-control-sm">
      @error('position') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mt-3">
      <button class="btn btn-primary">Create</button>
      <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
    </div>
  </form>
</div>

@endsection