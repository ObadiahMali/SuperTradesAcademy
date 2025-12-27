@extends('layouts.app')

@section('title','Plans')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Plans</h3>
    <div>
      <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-outline-secondary">Refresh</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Key</th>
              <th>Label</th>
              <th class="text-end">Price</th>
              <th>Currency</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($plans as $plan)
              <tr>
                <td class="align-middle">{{ $plan->key }}</td>
                <td class="align-middle">{{ $plan->label }}</td>
                <td class="align-middle text-end">{{ number_format((float) $plan->price, 2) }}</td>
                <td class="align-middle">{{ $plan->currency }}</td>
                <td class="align-middle text-end">
                  {{-- @can('manage-plans') --}}
                    <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-primary">Edit</a>
                  {{-- @endcan --}}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-muted p-3">No plans found</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if(method_exists($plans, 'links'))
    <div class="mt-3">
      {{ $plans->links() }}
    </div>
  @endif
</div>
@endsection