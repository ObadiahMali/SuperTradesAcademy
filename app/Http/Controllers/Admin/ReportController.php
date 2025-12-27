<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show report form and results (if filters provided). 
     *
     * Expected query params:
     *  - from (YYYY-MM-DD)
     *  - to   (YYYY-MM-DD)
     *  - type (all|payments|students)
     *  - plan (optional plan_key to filter by plan)
     */
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');
        $type = $request->query('type', 'all');
        $plan = $request->query('plan');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

        $rows = collect();
        $summary = [
            'total_payments' => 0,
            'total_paid_ugx' => 0.0,
            'students_count' => 0,
            // student-specific totals (populated when students branch runs)
            'total_expected_ugx' => 0.0,
            'total_paid_students_ugx' => 0.0,
            'total_unpaid_ugx' => 0.0,
        ];

        $plansConfig = config('plans.plans') ?? [];
        $rates = app(\App\Services\ExchangeRateService::class);

        //
        // Payments branch: build rows from payments
        //
        if ($type === 'all' || $type === 'payments') {
            $paymentsQuery = Payment::with('student');

            if ($plan) {
                $paymentsQuery->where('plan_key', $plan);
            }

            if ($fromDate && $toDate) {
                $paymentsQuery->whereBetween('paid_at', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $paymentsQuery->where('paid_at', '>=', $fromDate);
            } elseif ($toDate) {
                $paymentsQuery->where('paid_at', '<=', $toDate);
            }

            $payments = $paymentsQuery->orderByDesc('paid_at')->get();

            $paymentRows = $payments->map(function ($p) use ($plansConfig, $rates) {
                $student = $p->student;
                $planKey = $student?->plan_key;
                $planLabel = $planKey && isset($plansConfig[$planKey])
                    ? $plansConfig[$planKey]['label']
                    : ($planKey ? ucwords(str_replace('_', ' ', $planKey)) : '—');

                // Normalize original to USD
                $originalCurrency = 'USD';
                $originalAmount = strtoupper($p->currency ?? 'UGX') === 'UGX'
                    ? $rates->ugxToUsd((float) $p->amount)
                    : (float) $p->amount;

                return [
                    'date'              => $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : ($p->created_at?->format('Y-m-d H:i') ?? ''),
                    'student_id'        => $p->student_id,
                    'student_name'      => trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')),
                    'plan_key'          => $planKey,
                    'plan_label'        => $planLabel,
                    'original_amount'   => $originalAmount,
                    'original_currency' => $originalCurrency,
                    'converted_ugx'     => (float) ($p->amount_converted ?? 0),
                    'method'            => $p->method,
                    'reference'         => $p->receipt_number ?? $p->reference,
                ];
            });

            $rows = $rows->concat($paymentRows);
            $summary['total_payments'] = $payments->count();
            $summary['total_paid_ugx'] = $payments->sum(fn($p) => (float) ($p->amount_converted ?? 0));
        }

        //
        // Students branch: build rows from students and compute totals
        //
        if ($type === 'all' || $type === 'students') {
            $studentsQuery = Student::query();

            if ($plan) {
                $studentsQuery->where('plan_key', $plan);
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

            // Precompute payments sums per student (UGX) in one query to avoid N+1.
            $paymentsPerStudentQuery = Payment::selectRaw('student_id, SUM(amount_converted) as paid_ugx')
                ->whereIn('student_id', $studentIds)
                ->groupBy('student_id');

            // Apply same date filter to payments sums so "amount paid" matches the selected range.
            if ($fromDate && $toDate) {
                $paymentsPerStudentQuery->whereBetween('paid_at', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $paymentsPerStudentQuery->where('paid_at', '>=', $fromDate);
            } elseif ($toDate) {
                $paymentsPerStudentQuery->where('paid_at', '<=', $toDate);
            }

            // If payments table stores plan_key and you want to filter payments by plan too, uncomment:
            // if ($plan) { $paymentsPerStudentQuery->where('plan_key', $plan); }

            $paymentsSums = $paymentsPerStudentQuery->pluck('paid_ugx', 'student_id')->toArray();
            $totalExpectedUgx = 0.0;
            $totalPaidStudentsUgx = 0.0;

            $studentRows = $students->map(function ($s) use ($plansConfig, $rates, $paymentsSums, &$totalExpectedUgx, &$totalPaidStudentsUgx) {
                $planKey = $s->plan_key;
                $planLabel = $planKey && isset($plansConfig[$planKey])
                    ? $plansConfig[$planKey]['label']
                    : ($planKey ? ucwords(str_replace('_', ' ', $planKey)) : '—');

                // Course fee converted to UGX (expected)
                $courseFee = (float) ($s->course_fee ?? 0);
                $currency = strtoupper($s->currency ?? 'UGX');

                $courseFeeUgx = $currency === 'USD'
                    ? $rates->usdToUgx($courseFee)
                    : $courseFee;

                // Amount paid for this student (UGX) from precomputed sums
                $paidUgx = (float) ($paymentsSums[$s->id] ?? 0.0);

                // Amount due (UGX)
                $dueUgx = max(0.0, $courseFeeUgx - $paidUgx);

                // Accumulate totals
                $totalExpectedUgx += $courseFeeUgx;
                $totalPaidStudentsUgx += $paidUgx;

                // Keep original_amount in USD for display (convert if stored in UGX)
                $originalAmountUsd = $currency === 'UGX'
                    ? $rates->ugxToUsd($courseFee)
                    : $courseFee;

                return [
                    'date'              => $s->created_at?->format('Y-m-d H:i') ?? '',
                    'student_id'        => $s->id,
                    'student_name'      => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                    'plan_key'          => $planKey,
                    'plan_label'        => $planLabel,
                    'original_amount'   => $originalAmountUsd,
                    'original_currency' => 'USD',
                    'converted_ugx'     => $courseFeeUgx,
                    'amount_paid_ugx'   => $paidUgx,
                    'amount_due_ugx'    => $dueUgx,
                    'method'            => '—',
                    'reference'         => '—',
                ];
            });

            // Merge or replace rows depending on requested type
            if ($type === 'students') {
                $rows = $studentRows;
            } else {
                $rows = $rows->concat($studentRows);
            }

            $summary['students_count'] = $students->count();
            $summary['total_expected_ugx'] = $totalExpectedUgx;
            $summary['total_paid_students_ugx'] = $totalPaidStudentsUgx;
            $summary['total_unpaid_ugx'] = max(0.0, $totalExpectedUgx - $totalPaidStudentsUgx);
        }

        $filters = ['from' => $from, 'to' => $to, 'type' => $type, 'plan' => $plan];
        return view('admin.reports.index', compact('rows', 'summary', 'filters'));
    }

    /**
     * Export CSV for payments (supports same filters as index).
     * Writes header, rows, and summary footer. Dates are exported as text for Excel compatibility.
     * If plan filter is provided, it is applied.
     */
    public function export(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');
        $plan = $request->query('plan');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

        $query = Payment::with('student');

        if ($plan) {
            $query->where('plan_key', $plan);
        }

        if ($fromDate && $toDate) {
            $query->whereBetween('paid_at', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->where('paid_at', '>=', $fromDate);
        } elseif ($toDate) {
            $query->where('paid_at', '<=', $toDate);
        }

        $filename = 'payments_report_' . ($plan ? $plan . '_' : '') . now()->format('Ymd_His') . '.csv';
        $plansConfig = config('plans.plans') ?? [];
        $rates = app(\App\Services\ExchangeRateService::class);

        $response = new StreamedResponse(function () use ($query, $plansConfig, $rates) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($handle, ['Date','Student ID','Student Name','Plan','Original Amount (USD)','Converted UGX','Method','Reference']);

            $totalConverted = 0.0;
            $count = 0;

            $query->orderByDesc('paid_at')->chunk(200, function ($payments) use ($handle, &$totalConverted, &$count, $plansConfig, $rates) {
                foreach ($payments as $p) {
                    $date = $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : ($p->created_at?->format('Y-m-d H:i') ?? '');
                    $dateForExcel = $date ? '="' . $date . '"' : '';

                    $student = $p->student;
                    $studentName = trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? ''));
                    $planKey = $student?->plan_key;
                    $planLabel = $planKey && isset($plansConfig[$planKey])
                        ? $plansConfig[$planKey]['label']
                        : ($planKey ? ucwords(str_replace('_', ' ', $planKey)) : '—');

                    // Normalize original to USD
                    $originalAmount = strtoupper($p->currency ?? 'UGX') === 'UGX'
                        ? $rates->ugxToUsd((float) $p->amount)
                        : (float) $p->amount;

                    fputcsv($handle, [
                        $dateForExcel,
                        $p->student_id,
                        $studentName,
                        $planLabel,
                        number_format($originalAmount, 2, '.', ''),
                        number_format((float) ($p->amount_converted ?? 0), 2, '.', ''),
                        $p->method,
                        $p->receipt_number ?? $p->reference,
                    ]);

                    $totalConverted += (float) ($p->amount_converted ?? 0);
                    $count++;
                }
            });

            // Summary footer
            fputcsv($handle, []); // blank line
            fputcsv($handle, ['Summary']);
            fputcsv($handle, ['Total payments', $count]);
            fputcsv($handle, ['Total paid (UGX)', number_format($totalConverted, 2, '.', '')]);

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

    public function show($id)
    {
        abort(404);
    }
}