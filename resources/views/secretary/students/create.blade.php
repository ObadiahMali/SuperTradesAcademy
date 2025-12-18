@extends('layouts.app')
@section('title','Register Student')
@section('content')

<style>
  .card-register { background: #ffffff; border: 1px solid #e6e9ee; border-radius: 12px; box-shadow: 0 6px 18px rgba(22, 28, 37, 0.06); }
  .ts-dropdown-content { max-height: 220px !important; overflow-y: auto !important; }
  .ts-control, .ts-dropdown { font-size: 0.95rem; padding: 6px 10px; line-height: 1.4; border-radius: 6px; }
  .ts-dropdown .option { padding: 6px 10px; }
  .form-sub { color: #6b7280; font-size: .95rem; }
  .mb-3 { margin-bottom: 1rem; }
  label.form-label { font-weight: 600; color: #111827; margin-bottom: .35rem; display:block; }
  .input-lg { padding: .6rem .75rem; border-radius: 8px; border: 1px solid #d1d5db; width:100%; }
  .form-select { padding: .45rem .75rem; border-radius: 8px; border: 1px solid #d1d5db; background:#fff; }
  .phone-wrap { display:flex; gap:0.5rem; align-items:center; }
  .country-code { min-width:180px; }
  .btn-primary { background: #0f62fe; border-color: #0f62fe; color: #fff; padding: .55rem .9rem; border-radius: 8px; }
  .btn-outline-primary { color: #0f62fe; border-color: #cfe0ff; background: transparent; padding: .45rem .8rem; border-radius: 8px; }
  .error { color:#b91c1c; margin-top:0.25rem; font-size:0.9rem; }
  @media (max-width: 576px) { .col-lg-6 { max-width: 100%; padding: 0 1rem; } .country-code { min-width: 140px; } }
</style>

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

@php
  $intakes = $intakes ?? \App\Models\Intake::orderBy('start_date','desc')->get();
  $plans   = $plans ?? \App\Models\Plan::orderBy('key','asc')->get();
  $selectedIntakeId = $intakeId ?? old('intake_id') ?? null;

  // country list with dial codes (value will be digits only)
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
  $selectedCountry = old('phone_country_code', session('default_phone_country', '256'));
@endphp

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card-register p-4">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h4 class="mb-1">Register Student</h4>
          <div class="form-sub">Fill in the student's details and assign them to an intake and plan.</div>
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
          <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      @endif

      <form id="student-register-form" action="{{ route('secretary.students.store') }}" method="POST" novalidate>
        @csrf
        <input type="hidden" name="intake_id" id="intake_id_hidden" value="{{ $selectedIntakeId }}">

        {{-- Intake --}}
        <div class="mb-3">
          <label class="form-label" for="intake_select">Intake Month</label>
          <select name="intake_id" id="intake_select" class="select-lg form-select" required>
            <option value="">Choose intake</option>
            @foreach($intakes as $i)
              @php
                $label = $i->start_date ? \Carbon\Carbon::parse($i->start_date)->format('F Y') : ($i->name ?? 'No date');
                if (!empty($i->name) && $i->start_date) $label .= ' — ' . $i->name;
              @endphp
              <option value="{{ $i->id }}" @selected((string)old('intake_id', $selectedIntakeId ?? '') === (string)$i->id)>{{ $label }}</option>
            @endforeach
          </select>
          @error('intake_id')<div class="error">{{ $message }}</div>@enderror
        </div>

        {{-- Names --}}
        <div class="mb-3">
          <label class="form-label" for="first_name">First name</label>
          <input id="first_name" name="first_name" value="{{ old('first_name') }}" class="input-lg" placeholder="Given name" required>
          @error('first_name')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
          <label class="form-label" for="last_name">Last name</label>
          <input id="last_name" name="last_name" value="{{ old('last_name') }}" class="input-lg" placeholder="Family name (optional)">
          @error('last_name')<div class="error">{{ $message }}</div>@enderror
        </div>

        {{-- Phone --}}
        <div class="mb-3">
          <label class="form-label">Phone</label>
          <div class="phone-wrap">
            <div class="country-code">
              <select name="phone_country_code" id="phone_country" class="select-lg form-select" aria-label="Country code">
                @foreach($countries as $c)
                  @php $dialDigits = preg_replace('/\D+/', '', $c['dial']); @endphp
                  <option value="{{ $dialDigits }}" {{ (string)$selectedCountry === (string)$dialDigits ? 'selected' : '' }}>
                    {{ $c['label'] }} ({{ $c['dial'] }})
                  </option>
                @endforeach
              </select>
              @error('phone_country_code')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div style="flex:1">
              <input id="phone_local" name="phone" value="{{ old('phone') }}" class="input-lg" placeholder="712345678" inputmode="numeric" pattern="\d*">
              @error('phone')<div class="error">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>

        {{-- Email --}}
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input id="email" name="email" value="{{ old('email') }}" class="input-lg" placeholder="name@example.com">
          @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>

        {{-- Plan (submit plan_key) --}}
        <div class="mb-3">
          <label class="form-label" for="plan_key">Select Plan</label>
          <select name="plan_key" id="plan_key" class="select-lg form-select" required>
            <option value="">Choose a plan</option>
            @foreach($plans as $plan)
              <option value="{{ $plan->key }}" data-price="{{ $plan->price }}" data-currency="{{ $plan->currency }}" @selected(old('plan_key') == $plan->key)>
                {{ $plan->label }} — {{ number_format($plan->price, 2) }} {{ $plan->currency }}
              </option>
            @endforeach
          </select>
          @error('plan_key')<div class="error">{{ $message }}</div>@enderror
        </div>

        {{-- Address --}}
        <div class="mb-3">
          <label class="form-label" for="address_line1">Address line 1</label>
          <input id="address_line1" name="address_line1" value="{{ old('address_line1') }}" class="input-lg form-control">
        </div>
        <div class="mb-3">
          <label class="form-label" for="address_line2">Address line 2</label>
          <input id="address_line2" name="address_line2" value="{{ old('address_line2') }}" class="input-lg form-control">
        </div>
        <div class="row g-2">
          <div class="col">
            <label class="form-label" for="city">City</label>
            <input id="city" name="city" value="{{ old('city') }}" class="input-lg form-control">
          </div>
          <div class="col">
            <label class="form-label" for="region">Region</label>
            <input id="region" name="region" value="{{ old('region') }}" class="input-lg form-control">
          </div>
        </div>
        <div class="row g-2 mt-2">
          <div class="col">
            <label class="form-label" for="postal_code">Postal code</label>
            <input id="postal_code" name="postal_code" value="{{ old('postal_code') }}" class="input-lg form-control">
          </div>
          <div class="col">
            <label class="form-label" for="country">Country</label>
            <input id="country" name="country" value="{{ old('country') }}" class="input-lg form-control">
          </div>
        </div>

        {{-- Hidden canonical phone and fee fields --}}
        <input type="hidden" name="phone_full" id="phone_full" value="{{ old('phone_full') }}">
        <input type="hidden" name="phone_dial" id="phone_dial" value="{{ old('phone_dial', '+256') }}">
        <input type="hidden" name="currency" id="currency" value="{{ old('currency') }}">
        <input type="hidden" name="course_fee" id="course_fee" value="{{ old('course_fee') }}">

        <div class="d-flex justify-content-between align-items-center mt-4">
          <a href="{{ route('secretary.students.index') }}" class="btn btn-outline-primary">Cancel</a>
          <button type="submit" class="btn btn-primary">Register Student</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Keep phone_full and phone_dial updated before submit
  (function () {
    const countrySelect = document.getElementById('phone_country');
    const phoneLocal = document.getElementById('phone_local');
    const phoneFullInput = document.getElementById('phone_full');
    const phoneDialHidden = document.getElementById('phone_dial');
    const form = document.getElementById('student-register-form');

    function digitsOnly(s) {
      return (s || '').toString().replace(/\D+/g, '');
    }

    function updatePhoneFields() {
      const code = digitsOnly(countrySelect.value) || digitsOnly(phoneDialHidden.value) || '256';
      const local = digitsOnly(phoneLocal.value);
      phoneFullInput.value = local ? ('+' + code + local) : '';
      phoneDialHidden.value = '+' + code;
    }

    // initialize
    updatePhoneFields();

    countrySelect.addEventListener('change', updatePhoneFields);
    phoneLocal.addEventListener('input', updatePhoneFields);

    // ensure fields are updated right before submit (in case of JS changes)
    form.addEventListener('submit', function () {
      updatePhoneFields();
    });

    // Initialize Tom Select on selects for nicer UX (optional)
    try {
      new TomSelect('#intake_select', { maxItems: 1, plugins: ['dropdown_input'] });
      new TomSelect('#plan_key', { maxItems: 1, plugins: ['dropdown_input'] });
      new TomSelect('#phone_country', { maxItems: 1, plugins: ['dropdown_input'] });
    } catch (e) {
      // TomSelect not critical — ignore if it fails
      console.warn('TomSelect init failed', e);
    }
  })();
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof TomSelect === 'undefined') return;

  const common = { dropdownClass: 'ts-dropdown-custom match-control', dropdownParent: null, maxOptions: 1000, hideSelected: true, closeAfterSelect: true, render: {} };

  new TomSelect('#intake_select', Object.assign({}, common, { plugins: ['dropdown_input'], allowEmptyOption: true }));
  const phoneSelect = new TomSelect('#phone_country', Object.assign({}, common, { searchField: ['text'], maxOptions: 200 }));
  new TomSelect('#plan_key', Object.assign({}, common, { maxOptions: 50 }));

  const phoneDial = document.getElementById('phone_dial');
  function syncDialFromSelect() {
    const sel = document.querySelector('#phone_country');
    const dial = sel?.selectedOptions?.[0]?.dataset?.dial || '+256';
    if (phoneDial) phoneDial.value = dial;
  }
  phoneSelect?.on('change', syncDialFromSelect);
  syncDialFromSelect();

  const planSelect = document.getElementById('plan_key');
  const currencyInput = document.getElementById('currency');
  const feeInput = document.getElementById('course_fee');
  const intakeHidden = document.getElementById('intake_id_hidden');
  function syncPlanDetails() {
    const selected = planSelect?.selectedOptions?.[0];
    if (!selected) { if(currencyInput) currencyInput.value=''; if(feeInput) feeInput.value=''; return; }
    if (currencyInput) currencyInput.value = selected.dataset.currency || 'UGX';
    if (feeInput) feeInput.value = selected.dataset.price || '0';
  }
  planSelect?.addEventListener('change', syncPlanDetails);
  syncPlanDetails();

  const intakeSelect = document.getElementById('intake_select');
  if (intakeSelect && intakeHidden) {
    intakeSelect.addEventListener('change', function () { intakeHidden.value = intakeSelect.value; });
    intakeHidden.value = intakeSelect.value || intakeHidden.value || '';
  }
});
</script>

@endsection
