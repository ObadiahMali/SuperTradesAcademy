<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
class ReportController extends Controller
{
public function index(Request $request)
{
    $from = $request->query('from');
    $to   = $request->query('to');
    $type = $request->query('type', 'all');
    $plan = $request->query('plan');
    $intakeId = $request->query('intake');

    $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
    $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

    $rows = collect();
    $summary = [
        'total_payments' => 0,
        'total_paid_ugx' => 0.0,
        'students_count' => 0,
        'total_expected_ugx' => 0.0,
        'total_paid_students_ugx' => 0.0,
        'total_unpaid_ugx' => 0.0,
    ];

    $plansConfig = config('plans.plans') ?? [];

    // Resolve USD->UGX rate (service preferred, fallback only if service fails)
    $usdToUgxRate = $this->resolveUsdToUgxRate();

    //
    // Payments branch
    //
    if ($type === 'all' || $type === 'payments') {
        $paymentsQuery = Payment::with(['student', 'student.intake']);

        if ($plan) {
            $paymentsQuery->where('plan_key', $plan);
        }

        if ($intakeId) {
            $paymentsQuery->whereHas('student', fn($q) => $q->where('intake_id', $intakeId));
        }

        if ($fromDate && $toDate) {
            $paymentsQuery->whereBetween('paid_at', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $paymentsQuery->where('paid_at', '>=', $fromDate);
        } elseif ($toDate) {
            $paymentsQuery->where('paid_at', '<=', $toDate);
        }

        $payments = $paymentsQuery->orderByDesc('paid_at')->get();

        // accumulators for summary (use computed converted UGX, not raw DB field)
        $paymentsCount = 0;
        $paymentsPaidTotal = 0.0;
        $paymentsStudentIds = [];

        // per-student accumulators to compute expected/paid/due per student
        $studentPayments = []; // student_id => total paid (computed converted UGX)
        $studentMap = [];      // student_id => student model (first encountered)

        foreach ($payments as $p) {
            $student = $p->student;
            $studentId = $p->student_id;
            $intakeName = $student && $student->intake ? $student->intake->name : null;

            // compute original USD amount
            $originalAmountUsd = 0.0;
            $currency = strtoupper($p->currency ?? 'UGX');
            if ($currency === 'USD') {
                $originalAmountUsd = (float) $p->amount;
            } elseif ($currency === 'UGX') {
                $originalAmountUsd = $this->ugxToUsd((float) $p->amount, $usdToUgxRate);
            }

            // compute converted UGX (prefer stored, otherwise compute)
            $convertedUgx = (float) ($p->amount_converted ?? 0.0);
            if ($convertedUgx <= 0) {
                if ($currency === 'UGX') {
                    $convertedUgx = (float) $p->amount;
                } elseif ($currency === 'USD') {
                    $convertedUgx = $this->usdToUgx((float) $p->amount, $usdToUgxRate);
                } else {
                    $convertedUgx = 0.0;
                }
            }

            $planKey = $student?->plan_key;
            $planLabel = $planKey && isset($plansConfig[$planKey])
                ? $plansConfig[$planKey]['label']
                : ($planKey ? ucwords(str_replace('_', ' ', $planKey)) : '—');

            // push a payment row (we'll enrich amount_paid_ugx / amount_due_ugx per student later)
            $rows->push([
                'date' => $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : ($p->created_at?->format('Y-m-d H:i') ?? ''),
                'student_id' => $studentId,
                'student_name' => trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')),
                'plan_key' => $planKey,
                'plan_label' => $planLabel,
                'intake' => $student?->intake?->id ?? null,
                'intake_name' => $intakeName,
                'original_amount' => round($originalAmountUsd, 2),
                'original_currency' => 'USD',
                'converted_ugx' => round($convertedUgx, 2),
                // placeholders; will be replaced with per-student totals below
                'amount_paid_ugx' => round($convertedUgx, 2),
                'amount_due_ugx' => 0.0,
                'method' => $p->method,
                'reference' => $p->receipt_number ?? $p->reference,
            ]);

            // accumulate for summary totals
            $paymentsCount++;
            $paymentsPaidTotal += $convertedUgx;
            if (!empty($studentId)) {
                $paymentsStudentIds[] = $studentId;
                // accumulate per-student paid totals
                $studentPayments[$studentId] = ($studentPayments[$studentId] ?? 0.0) + $convertedUgx;
                // store student model for expected calculation later
                if (!isset($studentMap[$studentId]) && $student) {
                    $studentMap[$studentId] = $student;
                }
            }
        }

        // Now compute expected/paid/due per unique student (so payments summary can include expected/unpaid)
        $paymentsExpectedTotal = 0.0;
        $paymentsStudentsPaidTotal = 0.0;
        $paymentsUnpaidTotal = 0.0;

        foreach ($studentMap as $sid => $student) {
            // Determine expected per student: prefer student.course_fee, fallback to intake->fee
            $expectedPerStudent = (float) ($student->course_fee ?? 0);
            if ($expectedPerStudent <= 0 && isset($student->intake) && isset($student->intake->fee)) {
                $expectedPerStudent = (float) $student->intake->fee;
            }

            $expectedCurrency = strtoupper($student->currency ?? ($student->intake->currency ?? 'UGX'));

            // Expected in UGX
            $expectedUgx = 0.0;
            if ($expectedPerStudent > 0) {
                if ($expectedCurrency === 'USD') {
                    $expectedUgx = $this->usdToUgx($expectedPerStudent, $usdToUgxRate);
                } else {
                    $expectedUgx = $expectedPerStudent;
                }
            }

            $paidUgx = (float) ($studentPayments[$sid] ?? 0.0);
            $dueUgx = max(0.0, $expectedUgx - $paidUgx);

            $paymentsExpectedTotal += $expectedUgx;
            $paymentsStudentsPaidTotal += $paidUgx;
            $paymentsUnpaidTotal += $dueUgx;
        }

        // Update summary to include expected/paid/unpaid for payments filter
        $summary['total_payments'] = $paymentsCount;
        $summary['total_paid_ugx'] = round($paymentsPaidTotal, 2);
        $summary['students_count'] = count(array_unique($paymentsStudentIds));
        // only set these if there were students (otherwise keep 0)
        $summary['total_expected_ugx'] = round($paymentsExpectedTotal, 2);
        $summary['total_paid_students_ugx'] = round($paymentsStudentsPaidTotal, 2);
        $summary['total_unpaid_ugx'] = round($paymentsUnpaidTotal, 2);

        // Enrich each payment row with the per-student paid/due totals (so table shows expected/paid/due alongside each payment)
        $rows = $rows->map(function ($r) use ($studentPayments, $studentMap, $usdToUgxRate) {
            $sid = $r['student_id'] ?? null;
            $paid = (float) ($studentPayments[$sid] ?? 0.0);

            // compute expected for this student if we have the student model
            $expectedUgx = 0.0;
            if (isset($studentMap[$sid])) {
                $s = $studentMap[$sid];
                $expectedPerStudent = (float) ($s->course_fee ?? 0);
                if ($expectedPerStudent <= 0 && isset($s->intake) && isset($s->intake->fee)) {
                    $expectedPerStudent = (float) $s->intake->fee;
                }
                $expectedCurrency = strtoupper($s->currency ?? ($s->intake->currency ?? 'UGX'));
                if ($expectedPerStudent > 0) {
                    if ($expectedCurrency === 'USD') {
                        $expectedUgx = $this->usdToUgx($expectedPerStudent, $usdToUgxRate);
                    } else {
                        $expectedUgx = $expectedPerStudent;
                    }
                }
            }

            $due = max(0.0, $expectedUgx - $paid);

            $r['amount_paid_ugx'] = round($paid, 2);
            $r['amount_due_ugx'] = round($due, 2);
            // keep converted_ugx as the payment's converted value
            return $r;
        })->values();
    }

    //
    // Students branch
    //
    if ($type === 'all' || $type === 'students') {
        $studentsQuery = Student::with('intake');

        if ($plan) {
            $studentsQuery->where('plan_key', $plan);
        }

        if ($intakeId) {
            $studentsQuery->where('intake_id', $intakeId);
        }

        if ($fromDate && $toDate) {
            $studentsQuery->whereBetween('created_at', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $studentsQuery->where('created_at', '>=', $fromDate);
        } elseif ($toDate) {
            $studentsQuery->where('created_at', '<=', $toDate);
        }

        $students = $studentsQuery->orderByDesc('created_at')->get();
        $studentIds = $students->pluck('id')->toArray();

        // Precompute payments sums per student (UGX)
        $paymentsPerStudentQuery = Payment::selectRaw('student_id, SUM(amount_converted) as paid_ugx')
            ->whereIn('student_id', $studentIds)
            ->groupBy('student_id');

        if ($fromDate && $toDate) {
            $paymentsPerStudentQuery->whereBetween('paid_at', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $paymentsPerStudentQuery->where('paid_at', '>=', $fromDate);
        } elseif ($toDate) {
            $paymentsPerStudentQuery->where('paid_at', '<=', $toDate);
        }

        $paymentsSums = $paymentsPerStudentQuery->pluck('paid_ugx', 'student_id')->toArray();

        $totalExpectedUgx = 0.0;
        $totalPaidStudentsUgx = 0.0;

        foreach ($students as $s) {
            $planKey = $s->plan_key;
            $planLabel = $planKey && isset($plansConfig[$planKey])
                ? $plansConfig[$planKey]['label']
                : ($planKey ? ucwords(str_replace('_', ' ', $planKey)) : '—');

            // Determine expected per student: prefer student.course_fee, fallback to intake->fee
            $expectedPerStudent = (float) ($s->course_fee ?? 0);
            if ($expectedPerStudent <= 0 && isset($s->intake) && isset($s->intake->fee)) {
                $expectedPerStudent = (float) $s->intake->fee;
            }

            $expectedCurrency = strtoupper($s->currency ?? ($s->intake->currency ?? 'UGX'));

            // Expected in UGX
            $expectedUgx = 0.0;
            if ($expectedPerStudent > 0) {
                if ($expectedCurrency === 'USD') {
                    $expectedUgx = $this->usdToUgx($expectedPerStudent, $usdToUgxRate);
                } else {
                    $expectedUgx = $expectedPerStudent;
                }
            }

            // Paid UGX (precomputed)
            $paidUgx = (float) ($paymentsSums[$s->id] ?? 0.0);

            // Due UGX
            $dueUgx = max(0.0, $expectedUgx - $paidUgx);

            // Original amount (USD) should show plan/course fee in USD
            $originalAmountUsd = 0.0;
            if ($expectedPerStudent > 0) {
                if ($expectedCurrency === 'USD') {
                    $originalAmountUsd = $expectedPerStudent;
                } else { // UGX stored fee -> convert to USD
                    $originalAmountUsd = $this->ugxToUsd($expectedPerStudent, $usdToUgxRate);
                }
            }

            $rows->push([
                'date' => $s->created_at?->format('Y-m-d H:i') ?? '',
                'student_id' => $s->id,
                'student_name' => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                'plan_key' => $planKey,
                'plan_label' => $planLabel,
                'intake' => $s->intake?->id ?? null,
                'intake_name' => $s->intake?->name ?? null,
                'original_amount' => round($originalAmountUsd, 2),
                'original_currency' => 'USD',
                'converted_ugx' => round($expectedUgx, 2),
                'amount_paid_ugx' => round($paidUgx, 2),
                'amount_due_ugx' => round($dueUgx, 2),
                'method' => '—',
                'reference' => '—',
            ]);

            $totalExpectedUgx += $expectedUgx;
            $totalPaidStudentsUgx += $paidUgx;
        }

        $summary['students_count'] = $students->count();
        $summary['total_expected_ugx'] = $totalExpectedUgx;
        $summary['total_paid_students_ugx'] = $totalPaidStudentsUgx;
        $summary['total_unpaid_ugx'] = max(0.0, $totalExpectedUgx - $totalPaidStudentsUgx);
    }

    // Provide filters back to view including intake
    $filters = ['from' => $from, 'to' => $to, 'type' => $type, 'plan' => $plan, 'intake' => $intakeId];

    // Pass intakes for dropdown
    $intakes = Intake::orderBy('start_date', 'desc')->get(['id', 'name', 'start_date']);

    // Sort rows by date desc and ensure keys exist for blade
    $rows = $rows->sortByDesc('date')->values()->map(function ($r) {
        // ensure intake_name key exists
        $r['intake_name'] = $r['intake_name'] ?? ($r['intake'] ?? null);
        $r['original_currency'] = $r['original_currency'] ?? 'USD';
        $r['original_amount'] = $r['original_amount'] ?? 0;
        $r['converted_ugx'] = $r['converted_ugx'] ?? 0;
        $r['amount_paid_ugx'] = $r['amount_paid_ugx'] ?? ($r['converted_ugx'] ?? 0);
        $r['amount_due_ugx'] = $r['amount_due_ugx'] ?? 0;
        return $r;
    });

    return view('admin.reports.index', compact('rows', 'summary', 'filters', 'intakes'));
}

/**
 * Helpers used above (add to controller)
 */





    



public function export(Request $request)
{
    $from   = $request->query('from');
    $to     = $request->query('to');
    $plan   = $request->query('plan');
    $intake = $request->query('intake'); // optional intake filter

    $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
    $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

    $query = Payment::with(['student', 'student.intake']);

    if ($plan) {
        $query->where('plan_key', $plan);
    }

    if ($intake) {
        $query->whereHas('student', fn($q) => $q->where('intake_id', $intake));
    }

    if ($fromDate && $toDate) {
        $query->whereBetween('paid_at', [$fromDate, $toDate]);
    } elseif ($fromDate) {
        $query->where('paid_at', '>=', $fromDate);
    } elseif ($toDate) {
        $query->where('paid_at', '<=', $toDate);
    }

    // Prepare filename
    $safePlan = $plan ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $plan) . '_' : '';
    $safeIntake = $intake ? 'intake_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $intake) . '_' : '';
    $filename = 'payments_report_' . $safePlan . $safeIntake . now()->format('Ymd_His') . '.csv';

    $plansConfig = config('plans.plans') ?? [];
    $rateService = null;
    try {
        $rateService = app(\App\Services\ExchangeRateService::class);
    } catch (\Throwable $e) {
        $rateService = null;
    }

    //
    // Precompute student summary (Students in range, expected, paid, unpaid)
    //
    $studentsQuery = \App\Models\Student::query()
        ->with('intake')
        ->when($plan, fn($q) => $q->where('plan_key', $plan))
        ->when($intake, fn($q) => $q->where('intake_id', $intake));

    if ($fromDate && $toDate) {
        $studentsQuery->whereBetween('created_at', [$fromDate, $toDate]);
    } elseif ($fromDate) {
        $studentsQuery->where('created_at', '>=', $fromDate);
    } elseif ($toDate) {
        $studentsQuery->where('created_at', '<=', $toDate);
    }

    $students = $studentsQuery->get();
    $studentIds = $students->pluck('id')->toArray();

    // Precompute payments sums per student (UGX) in one query to avoid N+1.
    $paymentsPerStudentQuery = \App\Models\Payment::selectRaw('student_id, SUM(amount_converted) as paid_ugx')
        ->whereIn('student_id', $studentIds)
        ->groupBy('student_id');

    if ($fromDate && $toDate) {
        $paymentsPerStudentQuery->whereBetween('paid_at', [$fromDate, $toDate]);
    } elseif ($fromDate) {
        $paymentsPerStudentQuery->where('paid_at', '>=', $fromDate);
    } elseif ($toDate) {
        $paymentsPerStudentQuery->where('paid_at', '<=', $toDate);
    }

    $paymentsSums = $paymentsPerStudentQuery->pluck('paid_ugx', 'student_id')->toArray();

    $studentsCount = $students->count();
    $totalExpectedUgx = 0.0;
    $totalPaidStudentsUgx = 0.0;

    foreach ($students as $s) {
        // Determine expected per student (try student.course_fee, then intake->fee)
        $expectedPerStudent = (float) ($s->course_fee ?? 0);
        if ($expectedPerStudent <= 0 && isset($s->intake) && isset($s->intake->fee)) {
            $expectedPerStudent = (float) $s->intake->fee;
        }

        $currency = strtoupper($s->currency ?? ($s->intake->currency ?? 'UGX'));
        $expectedUgx = 0.0;
        if ($expectedPerStudent > 0) {
            if ($currency === 'USD') {
                // convert USD -> UGX using service if available, otherwise fallback to config
                try {
                    $expectedUgx = $rateService ? $rateService->usdToUgx($expectedPerStudent) : ((float) $expectedPerStudent * (float) config('exchange.fallback_usd_ugx', 3650));
                } catch (\Throwable $e) {
                    $expectedUgx = (float) $expectedPerStudent * (float) config('exchange.fallback_usd_ugx', 3650);
                }
            } else {
                $expectedUgx = $expectedPerStudent;
            }
        }

        $paidUgx = (float) ($paymentsSums[$s->id] ?? 0.0);

        $totalExpectedUgx += $expectedUgx;
        $totalPaidStudentsUgx += $paidUgx;
    }

    $totalUnpaidUgx = max(0.0, $totalExpectedUgx - $totalPaidStudentsUgx);

    //
    // Stream CSV
    //
    $response = new StreamedResponse(function () use ($query, $plansConfig, $rateService, $totalExpectedUgx, $totalPaidStudentsUgx, $totalUnpaidUgx, $studentsCount) {
        $handle = fopen('php://output', 'w');

        // UTF-8 BOM for Excel
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header (added Intake column)
        fputcsv($handle, [
            'Date',
            'Student ID',
            'Student Name',
            'Plan',
            'Intake',
            'Original Amount (USD)',
            'Converted UGX',
            'Method',
            'Reference',
        ]);

        $totalConverted = 0.0;
        $count = 0;

        $query->orderByDesc('paid_at')->chunk(200, function ($payments) use ($handle, &$totalConverted, &$count, $plansConfig, $rateService) {
            foreach ($payments as $p) {
                $date = $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : ($p->created_at?->format('Y-m-d H:i') ?? '');
                $dateForExcel = $date ? '="' . $date . '"' : '';

                $student = $p->student;
                $studentName = trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? ''));
                $planKey = $student?->plan_key;
                $planLabel = $planKey && isset($plansConfig[$planKey])
                    ? $plansConfig[$planKey]['label']
                    : ($planKey ? ucwords(str_replace('_', ' ', $planKey)) : '—');

                $intakeName = $student && $student->intake ? $student->intake->name : '—';

                // Determine converted UGX (prefer stored amount_converted; compute if missing)
                $convertedUgx = (float) ($p->amount_converted ?? 0.0);
                if ($convertedUgx <= 0) {
                    $currency = strtoupper($p->currency ?? 'UGX');
                    if ($currency === 'UGX') {
                        $convertedUgx = (float) $p->amount;
                    } elseif ($currency === 'USD') {
                        try {
                            $convertedUgx = $rateService ? $rateService->usdToUgx((float) $p->amount) : ((float) $p->amount * (float) config('exchange.fallback_usd_ugx', 3650));
                        } catch (\Throwable $e) {
                            $convertedUgx = (float) $p->amount * (float) config('exchange.fallback_usd_ugx', 3650);
                        }
                    } else {
                        $convertedUgx = 0.0;
                    }
                }

                // Normalize original to USD for the "Original Amount (USD)" column
                $originalAmountUsd = 0.0;
                $currency = strtoupper($p->currency ?? 'UGX');
                if ($currency === 'USD') {
                    $originalAmountUsd = (float) $p->amount;
                } elseif ($currency === 'UGX') {
                    try {
                        $originalAmountUsd = $rateService ? $rateService->ugxToUsd((float) $p->amount) : ((float) $p->amount / (float) config('exchange.fallback_usd_ugx', 3650));
                    } catch (\Throwable $e) {
                        $originalAmountUsd = (float) $p->amount / (float) config('exchange.fallback_usd_ugx', 3650);
                    }
                } else {
                    $originalAmountUsd = 0.0;
                }

                fputcsv($handle, [
                    $dateForExcel,
                    $p->student_id,
                    $studentName,
                    $planLabel,
                    $intakeName,
                    number_format($originalAmountUsd, 2, '.', ''),
                    number_format($convertedUgx, 2, '.', ''),
                    $p->method,
                    $p->receipt_number ?? $p->reference,
                ]);

                $totalConverted += $convertedUgx;
                $count++;
            }
        });

        // Summary footer (include the requested student summary)
        fputcsv($handle, []); // blank line
        fputcsv($handle, ['Summary']);
        fputcsv($handle, ['Total payments', $count]);
        fputcsv($handle, ['Total paid (UGX)', number_format($totalConverted, 2, '.', '')]);
        fputcsv($handle, []); // blank line
        fputcsv($handle, ['Students in range', $studentsCount]);
        fputcsv($handle, ['Total expected (UGX)', number_format($totalExpectedUgx, 2, '.', '')]);
        fputcsv($handle, ['Total paid by students (UGX)', number_format($totalPaidStudentsUgx, 2, '.', '')]);
        fputcsv($handle, ['Total unpaid (UGX)', number_format($totalUnpaidUgx, 2, '.', '')]);

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

    return $response;
}


    /**
     * Try to get USD->UGX rate from service. Return null on failure.
     */
    protected function getUsdToUgxRate(): ?float
    {
        try {
            $service = app(\App\Services\ExchangeRateService::class);
            // service should return null on failure; if it returns a numeric value, use it
            $rate = $service->getUsdToUgxRate();
            return is_numeric($rate) && $rate > 0 ? (float) $rate : null;
        } catch (\Throwable $e) {
            // log if you want: \Log::warning('Rate service failed: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Resolve USD->UGX rate and apply fallback only if service failed.
     */
    protected function resolveUsdToUgxRate(): float
    {
        $rate = $this->getUsdToUgxRate();
        if (is_numeric($rate) && $rate > 0) {
            return (float) $rate;
        }

        // apply fallback from config only when service failed
        return (float) config('exchange.fallback_usd_ugx', 3650.0);
    }

    /**
     * Convert UGX to USD using the service (best-effort).
     * If $rate is provided it will be used for conversions that require it.
     */
    protected function ugxToUsd(float $ugx, ?float $usdToUgxRate = null): float
    {
        // If we have a USD->UGX rate, UGX->USD is inverse
        $rate = $usdToUgxRate ?? $this->getUsdToUgxRate();
        if (!is_numeric($rate) || $rate <= 0) {
            // fallback: try config fallback but avoid forcing it here; use resolve if you want fallback
            $rate = (float) config('exchange.fallback_usx_ugx', 3650.0);
        }
        return $rate > 0 ? round($ugx / $rate, 6) : 0.0;
    }

    /**
     * Convert USD to UGX using provided or resolved rate.
     */
    protected function usdToUgx(float $usd, ?float $usdToUgxRate = null): float
    {
        $rate = $usdToUgxRate ?? $this->getUsdToUgxRate();
        if (!is_numeric($rate) || $rate <= 0) {
            $rate = (float) config('exchange.fallback_usd_ugx', 3650.0);
        }
        return round($usd * $rate, 2);
    }

    /**
     * Normalize a value to float safely.
     */
    protected function safeFloat($value): float
    {
        if ($value === null) return 0.0;
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Build a single payment row array (keeps mapping logic in one place).
     */
    protected function buildPaymentRow(Payment $p, array $plansConfig, float $usdToUgxRate): array
    {
        $student = $p->student;
        $planKey = $student?->plan_key;
        $planLabel = $planKey && isset($plansConfig[$planKey])
            ? $plansConfig[$planKey]['label']
            : ($planKey ? ucwords(str_replace('_', ' ', $planKey)) : '—');

        // Normalize original to USD for display: if stored in UGX convert to USD
        $originalCurrency = 'USD';
        $originalAmount = strtoupper($p->currency ?? 'UGX') === 'UGX'
            ? $this->ugxToUsd((float) $p->amount, $usdToUgxRate)
            : (float) $p->amount;

        // converted_ugx: prefer stored amount_converted, otherwise compute from currency
        $convertedUgx = $this->safeFloat($p->amount_converted);
        if ($convertedUgx <= 0) {
            if (strtoupper($p->currency ?? 'UGX') === 'UGX') {
                $convertedUgx = (float) $p->amount;
            } elseif (strtoupper($p->currency ?? '') === 'USD') {
                $convertedUgx = $this->usdToUgx((float) $p->amount, $usdToUgxRate);
            } else {
                $convertedUgx = 0.0;
            }
        }

        return [
            'date'              => $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : ($p->created_at?->format('Y-m-d H:i') ?? ''),
            'student_id'        => $p->student_id,
            'student_name'      => trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')),
            'plan_key'          => $planKey,
            'plan_label'        => $planLabel,
            'original_amount'   => $originalAmount,
            'original_currency' => $originalCurrency,
            'converted_ugx'     => round($convertedUgx, 2),
            'method'            => $p->method,
            'reference'         => $p->receipt_number ?? $p->reference,
        ];
    }

    /**
     * Build a single student row array and compute expected/paid/due values.
     */
    protected function buildStudentRow(Student $s, array $plansConfig, float $usdToUgxRate, array $paymentsSums): array
    {
        $planKey = $s->plan_key;
        $planLabel = $planKey && isset($plansConfig[$planKey])
            ? $plansConfig[$planKey]['label']
            : ($planKey ? ucwords(str_replace('_', ' ', $planKey)) : '—');

        // Course fee converted to UGX (expected)
        $courseFee = (float) ($s->course_fee ?? 0);
        $currency = strtoupper($s->currency ?? 'UGX');

        $courseFeeUgx = $currency === 'USD'
            ? $this->usdToUgx($courseFee, $usdToUgxRate)
            : $courseFee;

        // Amount paid for this student (UGX) from precomputed sums
        $paidUgx = $this->safeFloat($paymentsSums[$s->id] ?? 0.0);

        // Amount due (UGX)
        $dueUgx = max(0.0, $courseFeeUgx - $paidUgx);

        // Keep original_amount in USD for display (convert if stored in UGX)
        $originalAmountUsd = $currency === 'UGX'
            ? $this->ugxToUsd($courseFee, $usdToUgxRate)
            : $courseFee;

        return [
            'date'              => $s->created_at?->format('Y-m-d H:i') ?? '',
            'student_id'        => $s->id,
            'student_name'      => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
            'plan_key'          => $planKey,
            'plan_label'        => $planLabel,
            'original_amount'   => $originalAmountUsd,
            'original_currency' => 'USD',
            'converted_ugx'     => round($courseFeeUgx, 2),
            'amount_paid_ugx'   => round($paidUgx, 2),
            'amount_due_ugx'    => round($dueUgx, 2),
            'method'            => '—',
            'reference'         => '—',
        ];
    }
}
