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
        // $this->middleware('role:admin'); // enable if you have role middleware
    }

    /**
     * Show report form and results (if filters provided).
     *
     * Expected query params:
     *  - from (YYYY-MM-DD)
     *  - to   (YYYY-MM-DD)
     *  - type (all|payments|students)
     */
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');
        $type = $request->query('type', 'all');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

        $rows = collect();
        $summary = [
            'total_payments' => 0,
            'total_paid_ugx' => 0.0,
            'students_count' => 0,
        ];

        if ($type === 'all' || $type === 'payments') {
            $paymentsQuery = Payment::with('student');

            if ($fromDate && $toDate) {
                $paymentsQuery->whereBetween('paid_at', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $paymentsQuery->where('paid_at', '>=', $fromDate);
            } elseif ($toDate) {
                $paymentsQuery->where('paid_at', '<=', $toDate);
            }

            $payments = $paymentsQuery->orderByDesc('paid_at')->get();

            $rows = $payments->map(function ($p) {
                return [
                    'date' => $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : ($p->created_at?->format('Y-m-d H:i') ?? ''),
                    'student_id' => $p->student_id,
                    'student_name' => trim(($p->student?->first_name ?? '') . ' ' . ($p->student?->last_name ?? '')),
                    'original_amount' => (float) $p->amount,
                    'original_currency' => $p->currency,
                    'converted_ugx' => (float) ($p->amount_converted ?? 0),
                    'method' => $p->method,
                    'reference' => $p->receipt_number ?? $p->reference,
                ];
            });

            $summary['total_payments'] = $payments->count();
            $summary['total_paid_ugx'] = $payments->sum(fn($p) => (float) ($p->amount_converted ?? 0));
        }

        if ($type === 'all' || $type === 'students') {
            $studentsQuery = Student::query();

            if ($fromDate && $toDate) {
                $studentsQuery->whereBetween('created_at', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $studentsQuery->where('created_at', '>=', $fromDate);
            } elseif ($toDate) {
                $studentsQuery->where('created_at', '<=', $toDate);
            }

            $summary['students_count'] = $studentsQuery->count();
        }

        $filters = ['from' => $from, 'to' => $to, 'type' => $type];

        return view('admin.reports.index', compact('rows', 'summary', 'filters'));
    }

    /**
     * Export CSV for payments (supports same filters as index).
     * Writes header, rows, and summary footer. Dates are exported as text for Excel compatibility.
     */
    public function export(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');
        $type = $request->query('type', 'payments');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

        $query = Payment::with('student');

        if ($fromDate && $toDate) {
            $query->whereBetween('paid_at', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->where('paid_at', '>=', $fromDate);
        } elseif ($toDate) {
            $query->where('paid_at', '<=', $toDate);
        }

        $filename = 'payments_report_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($handle, ['Date','Student ID','Student Name','Original Amount','Currency','Converted UGX','Method','Reference']);

            $totalConverted = 0.0;
            $count = 0;

            $query->orderByDesc('paid_at')->chunk(200, function ($payments) use ($handle, &$totalConverted, &$count) {
                foreach ($payments as $p) {
                    $date = $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : ($p->created_at?->format('Y-m-d H:i') ?? '');
                    // Force Excel to treat date as text
                    $dateForExcel = $date ? '="' . $date . '"' : '';

                    $studentName = trim(($p->student?->first_name ?? '') . ' ' . ($p->student?->last_name ?? ''));

                    fputcsv($handle, [
                        $dateForExcel,
                        $p->student_id,
                        $studentName,
                        number_format((float) $p->amount, 2, '.', ''),
                        $p->currency,
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

    /**
     * Optional show method to satisfy resource routes.
     */
    public function show($id)
    {
        // If you use resource routes for reports, implement as needed.
        // For now, return 404 to avoid accidental capture of explicit routes like /export.
        abort(404);
    }
}