@extends('layouts.app')
@section('title','Employees')
@section('content')

<div class="page-header d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Employees</h3>
  <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">Add Employee</a>
</div>

<div class="card card-clean p-3">
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-head">
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Position</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($employees as $employee)
          <tr>
            <td style="font-weight:700">{{ $employee->name }}</td>
            <td>{{ $employee->email }}</td>
            <td class="muted-small">{{ $employee->position }}</td>
            <td class="text-end">
              <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete employee?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center muted-small">No employees found</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-end mt-3">
    {{ $employees->withQueryString()->links() }}
  </div>
</div>

@endsection