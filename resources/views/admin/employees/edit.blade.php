{{-- resources/views/admin/employees/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<div class="container">
  <div class="page-header d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Edit Employee</h3>
      <div class="page-sub">Update employee details</div>
    </div>
    <div>
      <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
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

    <form action="{{ route('admin.employees.update', $employee) }}" method="POST">
      @csrf
      @method('PATCH')

      <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="name" value="{{ old('name', $employee->name) }}" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Position</label>
        <input type="text" name="position" value="{{ old('position', $employee->position) }}" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control" required>
      </div>

      <div class="d-flex justify-content-between">
        <div>
          <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
        <div>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection