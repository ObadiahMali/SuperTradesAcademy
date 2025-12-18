@extends('layouts.app')
@section('title','Edit Student')
@section('content')

<style>
  .card-register { background: #ffffff; border: 1px solid #e6e9ee; border-radius: 12px; box-shadow: 0 6px 18px rgba(22, 28, 37, 0.06); padding: 1.25rem; }
  .ts-dropdown-content { max-height: 220px !important; overflow-y: auto !important; }
  .ts-control, .ts-dropdown { font-size: 0.95rem; padding: 6px 10px; line-height: 1.4; border-radius: 6px; }
  .ts-dropdown .option { padding: 6px 10px; }
  .form-sub { color: #6b7280; font-size: .95rem; }
  .mb-3 { margin-bottom: 1rem; }
  label.form-label { font-weight: 600; color: #111827; margin-bottom: .35rem; display:block; }
  .input-lg { padding: .6rem .75rem; border-radius: 8px; border: 1px solid #d1d5db; width:100%; }
  .form-select { padding: .45rem .75rem; border-radius: 8px; border: 1px solid #d1d5db; background:#fff; width:100%; }
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
  // Use passed variables if controller provided them, otherwise fallback
  $intakes = $intakes ?? \App\Models\Intake::orderBy('start_date','desc')->get();
  $plans   = $plans ?? (config('plans.plans') ?? []);
  // country list (same as create)
  $countries = $countries ?? [
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

  // Determine selected country dial digits: prefer old(), then phone_country_code, then phone_dial, then default
  $selectedCountry = old('phone_country_code')
    ?? old('phone_country')
    ?? (isset($student) ? ($student->phone_country_code ?? null) : null)
    ?? (isset($student) ? (preg_replace('/\D+/', '', ($student->phone_dial ?? '')) ?: null) : null)
    ?? preg_replace('/\D+/', '', (config('app.default_phone_country', '+256')));
@endphp

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

      <form id="student-edit-form" action="{{ route('secretary.students.update', $student->id) }}" method="POST" novalidate>
        @csrf
        @method('PUT')

        <input type="hidden" name="intake_id" value="{{ $intakeId }}">

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
              <option value="{{ $i->id }}" {{ (string)old('intake_id', $student->intake_id) === (string)$i->id ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
          @error('intake_id')<div class="error">{{ $message }}</div>@enderror
        </div>

        {{-- Names --}}
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

        {{-- Phone --}}
        <div class="mb-3">
          <label class="form-label">Phone</label>
          <div class="phone-wrap">
            <div class="country-code">
              <select name="phone_country_code" id="phone_country" class="select-lg form-select" aria-label="Country code">
                @foreach($countries as $c)
                  @php $dialDigits = preg_replace('/\D+/', '', $c['dial']); @endphp
                  <option value="{{ $dialDigits }}" {{ (string)$selectedCountry === (string)$dialDigits ? 'selected' : '' }} data-dial="{{ $c['dial'] }}">
                    {{ $c['label'] }} ({{ $c['dial'] }})
                  </option>
                @endforeach
              </select>
              @error('phone_country_code')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div style="flex:1">
              <input id="phone_local" name="phone" value="{{ old('phone', $student->phone) }}" class="input-lg" placeholder="712345678" inputmode="numeric" pattern="\d*">
              @error('phone')<div class="error">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>

        {{-- Email --}}
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input id="email" name="email" value="{{ old('email', $student->email) }}" class="input-lg">
          @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>

       {{-- Plan --}}
        <div class="mb-3">
          <label class="form-label" for="plan_key">Select Plan</label>
          <select name="plan_key" id="plan_key" class="select-lg form-select" required>
            <option value="">Choose a plan</option>
            @foreach($plans as $plan)
              <option value="{{ $plan->key }}"
                data-currency="{{ $plan->currency ?? 'UGX' }}"
                data-price="{{ $plan->price ?? 0 }}"
                {{ (string) old('plan_key', $student->plan_key) === (string) $plan->key ? 'selected' : '' }}>
                {{ $plan->label ?? $plan->key }} — {{ number_format($plan->price ?? 0, 2) }} {{ $plan->currency ?? 'UGX' }}
              </option>
            @endforeach
          </select>
          @error('plan_key')<div class="error">{{ $message }}</div>@enderror
        </div>

        {{-- Hidden canonical phone and fee fields --}}
        <input type="hidden" name="phone_full" id="phone_full" value="{{ old('phone_full', $student->phone_full) }}">
        <input type="hidden" name="phone_dial" id="phone_dial" value="{{ old('phone_dial', $student->phone_dial ?? ('+' . ($student->phone_country_code ?? preg_replace('/\D+/', '', config('app.default_phone_country', '+256'))))) }}">
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
  // Initialize TomSelect if available
  try {
    new TomSelect('#intake_select', { maxItems: 1, plugins: ['dropdown_input'] });
    new TomSelect('#plan_key', { maxItems: 1, plugins: ['dropdown_input'] });
    const phoneSelect = new TomSelect('#phone_country', { maxItems: 1, plugins: ['dropdown_input'] });
  } catch (e) {
    // ignore if TomSelect not loaded
  }

  const countrySelect = document.getElementById('phone_country');
  const phoneLocal = document.getElementById('phone_local');
  const phoneFullInput = document.getElementById('phone_full');
  const phoneDialHidden = document.getElementById('phone_dial');
  const form = document.getElementById('student-edit-form');
  const planSelect = document.getElementById('plan_key');
  const currencyInput = document.getElementById('currency');
  const feeInput = document.getElementById('course_fee');

  function digitsOnly(s) { return (s || '').toString().replace(/\D+/g, ''); }

  function updatePhoneFields() {
    const code = digitsOnly(countrySelect.value) || digitsOnly(phoneDialHidden.value) || digitsOnly('+256');
    const local = digitsOnly(phoneLocal.value);
    phoneFullInput.value = local ? ('+' + code + local) : '';
    phoneDialHidden.value = '+' + code;
  }

  function syncDialFromSelect() {
    const sel = countrySelect;
    const dial = sel?.selectedOptions?.[0]?.dataset?.dial || '+256';
    phoneDialHidden.value = dial;
    updatePhoneFields();
  }

  function syncPlanDetails() {
    const selected = planSelect?.selectedOptions?.[0];
    if (!selected) return;
    const currency = selected.dataset.currency || 'UGX';
    const price = selected.dataset.price || '0';
    if (currencyInput) currencyInput.value = currency;
    if (feeInput) feeInput.value = price;
  }

  countrySelect?.addEventListener('change', syncDialFromSelect);
  phoneLocal?.addEventListener('input', updatePhoneFields);
  planSelect?.addEventListener('change', syncPlanDetails);

  // initialize values on load
  syncDialFromSelect();
  updatePhoneFields();
  syncPlanDetails();

  // ensure phone fields are updated before submit
  form?.addEventListener('submit', function () {
    updatePhoneFields();
    syncPlanDetails();
  });
});
</script>

@endsection