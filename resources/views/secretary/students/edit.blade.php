@extends('layouts.app')
@section('title','Edit Student')
@section('content')

<style>
  .ts-dropdown-content {
    max-height: 220px !important;
    overflow-y: auto !important;
  }

  .ts-control, .ts-dropdown {
    font-size: 0.95rem;
    padding: 6px 10px;
    line-height: 1.4;
  }

  .ts-dropdown .option {
    padding: 6px 10px;
  }
</style>

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card-register">

      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h4 class="mb-1">Edit Student</h4>
          <div class="form-sub">Update the student's details and plan assignment.</div>
        </div>
        <div class="text-end">
          <a href="{{ route('secretary.students.index') }}" class="btn btn-outline-primary">Back to students</a>
        </div>
      </div>

      @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @php
        $intakeId = old('intake_id', $student->intake_id);
      @endphp

      <form action="{{ route('secretary.students.update', $student->id) }}" method="POST" novalidate>
        @csrf
        @method('PUT')

        <input type="hidden" name="intake_id" value="{{ $intakeId }}">

        <div class="mb-3">
          <label class="form-label" for="intake_id">Intake Month</label>
          <select name="intake_id" id="intake_id" class="select-lg" required>
            <option value="">Choose intake</option>
            @foreach($intakes as $i)
              <option value="{{ $i->id }}" {{ (string)old('intake_id', $student->intake_id) === (string)$i->id ? 'selected' : '' }}>
                {{ \Carbon\Carbon::parse($i->start_date)->format('F Y') }}@if($i->name) — {{ $i->name }}@endif
              </option>
            @endforeach
          </select>
          @error('intake_id')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="form-label" for="first_name">First name</label>
          <input id="first_name" name="first_name" value="{{ old('first_name', $student->first_name) }}" class="input-lg" required>
          @error('first_name')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="form-label" for="last_name">Last name</label>
          <input id="last_name" name="last_name" value="{{ old('last_name', $student->last_name) }}" class="input-lg">
          @error('last_name')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Phone</label>
          <div class="phone-wrap">
            <div class="country-code">
              <select name="phone_country" id="phone_country" class="select-lg">
                @php
                  $countries = [
                    ['code'=>'UG','dial'=>'+256','label'=>'Uganda'],
                    ['code'=>'KE','dial'=>'+254','label'=>'Kenya'],
                    ['code'=>'TZ','dial'=>'+255','label'=>'Tanzania'],
                    ['code'=>'RW','dial'=>'+250','label'=>'Rwanda'],
                    ['code'=>'US','dial'=>'+1','label'=>'United States'],
                    ['code'=>'GB','dial'=>'+44','label'=>'United Kingdom'],
                    ['code'=>'NG','dial'=>'+234','label'=>'Nigeria'],
                    ['code'=>'ZA','dial'=>'+27','label'=>'South Africa'],
                    ['code'=>'IN','dial'=>'+91','label'=>'India'],
                    ['code'=>'ZM','dial'=>'+260','label'=>'Zambia'],
                  ];
                  $selectedCountry = old('phone_country', $student->phone_country ?? 'UG');
                @endphp
                @foreach($countries as $c)
                  <option value="{{ $c['code'] }}" data-dial="{{ $c['dial'] }}" {{ $selectedCountry === $c['code'] ? 'selected' : '' }}>
                    {{ $c['label'] }} ({{ $c['dial'] }})
                  </option>
                @endforeach
              </select>
              @error('phone_country')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div style="flex:1">
              <input name="phone" value="{{ old('phone', $student->phone) }}" class="input-lg" placeholder="712345678">
              @error('phone')<div class="error">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input id="email" name="email" value="{{ old('email', $student->email) }}" class="input-lg">
          @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="form-label" for="plan_key">Select Plan</label>
          <select name="plan_key" id="plan_key" class="select-lg" required>
            <option value="">Choose a plan</option>
            @foreach($plans as $key => $plan)
              <option value="{{ $key }}"
                data-currency="{{ $plan['currency'] }}"
                data-price="{{ $plan['price'] }}"
                {{ old('plan_key', $student->plan_key) === $key ? 'selected' : '' }}>
                {{ $plan['label'] }} — {{ $plan['currency'] }} {{ number_format($plan['price'], 2) }}
              </option>
            @endforeach
          </select>
          @error('plan_key')<div class="error">{{ $message }}</div>@enderror
        </div>

        <input type="hidden" name="phone_dial" id="phone_dial" value="{{ old('phone_dial', $student->phone_dial ?? '+256') }}">
        <input type="hidden" name="currency" id="currency" value="{{ old('currency', $student->currency) }}">
        <input type="hidden" name="course_fee" id="course_fee" value="{{ old('course_fee', $student->course_fee) }}">

        <div class="d-flex justify-content-between align-items-center mt-4">
          <a href="{{ route('secretary.students.index') }}" class="btn btn-outline-primary">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Student</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof TomSelect === 'undefined') return;

  const common = {
    dropdownClass: 'ts-dropdown-custom match-control',
    dropdownParent: null,
    maxOptions: 1000,
    hideSelected: true,
    closeAfterSelect: true,
    render: {}
  };

  new TomSelect('#intake_id', Object.assign({}, common, {
    plugins: ['dropdown_input'],
    allowEmptyOption: true,
  }));

  const phoneSelect = new TomSelect('#phone_country', Object.assign({}, common, {
    searchField: ['text'],
    maxOptions: 200
  }));

  new TomSelect('#plan_key', Object.assign({}, common, { maxOptions: 50 }));

  const phoneDial = document.getElementById('phone_dial');
  function syncDialFromSelect() {
    const sel = document.querySelector('#phone_country');
    const dial = sel?.selectedOptions?.[0]?.dataset?.dial || '+256';
    if (phoneDial) phoneDial.value = dial;
  }

  if (phoneSelect && typeof phoneSelect.on === 'function') {
    phoneSelect.on('change', syncDialFromSelect);
  } else {
    document.getElementById('phone_country')?.addEventListener('change', syncDialFromSelect);
  }

  syncDialFromSelect();

  const planSelect = document.getElementById('plan_key');
  const currencyInput = document.getElementById('currency');
  const feeInput = document.getElementById('course_fee');
  function syncPlanDetails() {
    const selected = planSelect?.selectedOptions?.[0];
    if (!selected) return;
    const currency = selected.dataset.currency || 'UGX';
    const price = selected.dataset.price || '0';

    if (currencyInput) currencyInput.value = currency;
    if (feeInput) feeInput.value = price;
  }

  planSelect?.addEventListener('change', syncPlanDetails);
  syncPlanDetails(); // run on page load
});
</script>

@endsection