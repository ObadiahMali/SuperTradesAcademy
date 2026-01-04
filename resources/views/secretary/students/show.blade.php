{{-- resources/views/secretary/students/show.blade.php --}}
@extends('layouts.app')

@section('title', trim($student->first_name . ' ' . $student->last_name))

@section('content')
@php
    use Carbon\Carbon;

    // Resolve plan from database (single source of truth)
    $plan = \App\Models\Plan::where('key', $student->plan_key)->first();
    $planLabel = $plan->label ?? $student->plan_key ?? 'Unknown';

    // Original price display (USD or UGX, as stored)
    if ($plan) {
        $origCurrency = strtoupper($plan->currency ?? 'USD');
        $origPrice    = $origCurrency === 'USD' ? number_format((float) $plan->price, 0) : number_format((float) $plan->price, 2);
        $originalDisplay = "{$origCurrency} {$origPrice}";
    } else {
        $originalDisplay = 'UGX ' . number_format($student->course_fee ?? 0, 0);
    }

    // Exchange rate service (used only in view to compute UGX equivalents)
    $rates = app(\App\Services\ExchangeRateService::class);

    // Determine effective plan price in UGX (priority: plan -> student.course_fee)
    if ($plan) {
        $planCurrency = strtoupper($plan->currency ?? 'UGX');
        if ($planCurrency === 'USD') {
            $planPriceUGX = (float) $rates->usdToUgx((float) $plan->price);
        } else {
            $planPriceUGX = (float) $plan->price;
        }
    } else {
        // fallback to student.course_fee (assumed UGX)
        $planPriceUGX = is_numeric($student->course_fee) ? (float) $student->course_fee : 0.0;
    }

    // Compute total paid in UGX (prefer amount_converted; otherwise convert USD payments)
    $totalPaidUGX = 0.0;
    if (isset($payments) && $payments instanceof \Illuminate\Support\Collection) {
        foreach ($payments as $p) {
            if (!is_null($p->amount_converted) && is_numeric($p->amount_converted) && (float)$p->amount_converted > 0) {
                $totalPaidUGX += (float) $p->amount_converted;
                continue;
            }
            $pCurrency = strtoupper($p->currency ?? 'UGX');
            $pAmount = is_numeric($p->amount) ? (float) $p->amount : 0.0;
            if ($pCurrency === 'USD') {
                $totalPaidUGX += (float) $rates->usdToUgx($pAmount);
            } else {
                $totalPaidUGX += $pAmount;
            }
        }
    }

    // Balance in UGX
    $balanceUGX = max(0.0, $planPriceUGX - $totalPaidUGX);

    $formatDate = fn ($d) => $d ? Carbon::parse($d)->format('d M Y H:i') : '—';

    // Phone display
    if (!empty($student->phone_full)) {
        $phoneDisplay = $student->phone_full;
    } elseif (!empty($student->phone_country_code) || !empty($student->phone)) {
        $dial = !empty($student->phone_country_code) ? ('+' . ltrim($student->phone_country_code, '+')) : '';
        $phoneDisplay = trim($dial . ' ' . ($student->phone ?? ''));
    } else {
        $phoneDisplay = '—';
    }
@endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div>
        <h3 class="mb-0">{{ $student->first_name }} {{ $student->last_name }}</h3>
        <div class="muted-small">
            Intake: {{ $student->intake->name ?? '—' }} |
            Mentorship: {{ $planLabel }} |
            Price: {{ $originalDisplay }}
            {{-- @if(isset($planPriceUGX) && $planPriceUGX > 0 && (!isset($planCurrency) || $planCurrency !== 'UGX'))
                — UGX {{ number_format($planPriceUGX, 0) }}
            @endif --}}
        </div>
    </div>

    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('secretary.payments.create', ['student' => $student->id]) }}"
           class="btn btn-success">
            Add Payment
        </a>
    </div>
</div>

<div class="row g-3">
    {{-- Student info --}}
    <div class="col-12 col-lg-4">
        <div class="card p-3 h-100">
            <h6 class="muted-small">Student Info</h6>

            <div class="mt-2">
                <div><strong>ID:</strong> {{ $student->id }}</div>
                <div><strong>Phone:</strong> {{ $phoneDisplay }}</div>
                <div><strong>Email:</strong> {{ $student->email ?? '—' }}</div>
                <div class="mt-1">
                    <strong>Status:</strong>
                    <span class="badge bg-light text-dark">{{ $student->status ?? '—' }}</span>
                </div>

                <hr>

                <div><strong>Total Price (UGX):</strong> UGX {{ number_format($planPriceUGX, 0) }}</div>
                <div><strong>Total Paid:</strong> UGX {{ number_format($totalPaidUGX, 0) }}</div>
                <div>
                    <strong>Balance:</strong>
                    @if($balanceUGX <= 0)
                        <span class="text-success">Paid in full</span>
                    @else
                        <span class="text-danger">UGX {{ number_format($balanceUGX, 0) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Payments --}}
    <div class="col-12 col-lg-8">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Payments</h6>
                <div class="muted-small">Showing {{ $payments->count() }} payment(s)</div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Original</th>
                            <th class="d-none d-sm-table-cell">Converted (UGX)</th>
                            <th class="d-none d-md-table-cell">Method</th>
                            <th class="d-none d-md-table-cell">Reference</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($payments as $p)
                            <tr>
                                <td>{{ $formatDate($p->paid_at ?? $p->created_at) }}</td>
                                <td>{{ strtoupper($p->currency ?? 'UGX') }} {{ number_format((float)$p->amount, 2) }}</td>
                                <td class="d-none d-sm-table-cell">
                                    UGX {{ number_format((float)($p->amount_converted ?? ($p->currency === 'USD' ? $rates->usdToUgx((float)$p->amount) : $p->amount)), 0) }}
                                </td>
                                <td class="d-none d-md-table-cell">{{ $p->method ?? '—' }}</td>
                                <td class="d-none d-md-table-cell">{{ $p->reference ?? $p->receipt_number ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('secretary.payments.receipt', $p->id) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary">
                                        Print
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center muted-small">
                                    No payments recorded yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end gap-4 mt-3">
                <div class="text-end">
                    <div class="muted-small">Subtotal (UGX)</div>
                    <div class="fw-bold">UGX {{ number_format($planPriceUGX, 0) }}</div>
                </div>
                <div class="text-end">
                    <div class="muted-small">Paid</div>
                    <div class="fw-bold">UGX {{ number_format($totalPaidUGX, 0) }}</div>
                </div>
                <div class="text-end">
                    <div class="muted-small">Balance</div>
                    <div class="fw-bold text-{{ $balanceUGX <= 0 ? 'success' : 'danger' }}">
                        {{ $balanceUGX <= 0 ? 'Paid' : 'UGX ' . number_format($balanceUGX, 0) }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection