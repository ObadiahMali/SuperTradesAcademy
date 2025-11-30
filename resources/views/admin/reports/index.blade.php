@extends('layouts.app')
@section('title','Reports')
@section('content')

<div class="page-header d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Reports</h3>
  <div class="page-sub">Generate and download financial and operational reports</div>
</div>

<div class="card card-clean p-3">
  <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small">From</label>
      <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
    </div>

    <div class="col-md-3">
      <label class="form-label small">To</label>
      <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
    </div>

    <div class="col-md-3">
      <label class="form-label small">Type</label>
      <select name="type" class="form-select form-select-sm">
        <option value="">All</option>
        <option value="payments" @selected(request('type')=='payments')>Payments</option>
        <option value="expenses" @selected(request('type')=='expenses')>Expenses</option>
      </select>
    </div>

    <div class="col-md-3">
      <button class="btn btn-primary btn-sm">Apply</button>
    </div>
  </form>

  <hr/>

  <div class="mt-2">
    <div class="muted-small">Summary</div>
    <div class="mt-2">
      @if(!empty($summary))
        {{-- render summary rows passed from controller --}}
        @foreach($summary as $row)
          <div class="small-note">{{ $row['label'] }}: {{ $row['value'] }}</div>
        @endforeach
      @else
        <div class="small-note">Use the filters above to generate a report. Export logic can be added in the controller.</div>
      @endif
    </div>
  </div>
</div>

@endsection