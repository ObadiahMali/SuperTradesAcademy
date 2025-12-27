{{-- resources/views/admin/users/show.blade.php --}}
@extends('layouts.app')

@section('title', 'User details')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">User details</h1>
            <small class="text-muted">Details for {{ $user->name }}</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back to list</a>
            @can('manage-users')
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary">Edit</a>
            @endcan
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $user->name }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $user->email }}</dd>

                <dt class="col-sm-3">Role</dt>
                <dd class="col-sm-9">{{ ucfirst($user->role ?? '—') }}</dd>

                <dt class="col-sm-3">Created</dt>
                <dd class="col-sm-9">{{ $user->created_at?->toDayDateTimeString() ?? '—' }}</dd>

                <dt class="col-sm-3">Last updated</dt>
                <dd class="col-sm-9">{{ $user->updated_at?->toDayDateTimeString() ?? '—' }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection