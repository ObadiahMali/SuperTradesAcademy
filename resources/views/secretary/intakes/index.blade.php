{{-- resources/views/secretary/intakes/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Intakes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-0">Intakes</h3>
    <div class="muted-note small">Manage program intakes and view student counts</div>
  </div>

  <div>
    <a href="{{ route('secretary.intakes.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i> Create Intake
    </a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($intakes->isEmpty())
  <div class="card p-4">
    <div class="text-center">
      <p class="mb-2">No intakes found.</p>
      <a href="{{ route('secretary.intakes.create') }}" class="btn btn-outline-primary">Add first intake</a>
    </div>
  </div>
@else
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th class="text-center">Students</th>
            <th class="text-center">Active</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($intakes as $intake)
            <tr>
              <td>
                <a href="{{ route('secretary.intakes.show', $intake) }}" class="text-decoration-none">
                  {{ $intake->name }}
                </a>
                @if($intake->description)
                  <div class="muted-note small mt-1">{{ Str::limit($intake->description, 80) }}</div>
                @endif
              </td>

              <td class="text-center align-middle">
                <span class="badge bg-secondary">{{ $intake->students_count ?? 0 }}</span>
              </td>

              <td class="text-center align-middle">
                @if($intake->active)
                  <span class="badge bg-success">Yes</span>
                @else
                  <span class="badge bg-light text-muted">No</span>
                @endif
              </td>

              <td class="text-end align-middle">
                <a href="{{ route('secretary.intakes.show', $intake) }}" class="btn btn-sm btn-outline-secondary me-1">
                  View
                </a>
                <a href="{{ route('secretary.intakes.edit', $intake) }}" class="btn btn-sm btn-outline-primary me-1">
                  Edit
                </a>

                <form action="{{ route('secretary.intakes.destroy', $intake) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete this intake? This cannot be undone.');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if(method_exists($intakes, 'links'))
      <div class="card-footer">
        {{ $intakes->links() }}
      </div>
    @endif
  </div>
@endif
@endsection