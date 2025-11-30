<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Intake;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Expense;
use App\Models\Employee;
use App\Models\Report;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index(Request $request)
    {
        // Fixed USD rate (replace with an exchange service if available)
        $usdRate = 3600;

        // Basic counts
        $activeIntakeCount = Intake::where('active', true)->count();
        $studentCount = Student::count();
        $employeesCount = Employee::count();
        $reportsCount = Report::count();

        // Expenses
        $totalExpensesAllTime = Expense::sum('amount');
        $startOfMonth = now()->startOfMonth();
        $expensesThisMonth = Expense::where('created_at', '>=', $startOfMonth)->sum('amount');

        // Month-to-date receipts (grouped by currency)
        $ugxThisMonth = Payment::where('currency', 'UGX')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $usdThisMonth = Payment::where('currency', 'USD')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $totalReceiptsUGXMonth = $ugxThisMonth + ($usdThisMonth * $usdRate);

        $currentBalanceUGXMonth = $totalReceiptsUGXMonth - $expensesThisMonth;
        $currentBalanceUSDMonth = $usdRate > 0 ? $currentBalanceUGXMonth / $usdRate : 0;

        // All-time receipts (converted)
        $totalCollectedUGXAll = Payment::where('currency', 'UGX')->sum('amount')
            + (Payment::where('currency', 'USD')->sum('amount') * $usdRate);

        $currentBalanceUGXAll = $totalCollectedUGXAll - $totalExpensesAllTime;
        $currentBalanceUSDAll = $usdRate > 0 ? $currentBalanceUGXAll / $usdRate : 0;

        //
        // Expected / Outstanding
        // Prefer intake.expected_amount; fallback to students' course_fee with currency conversion
        //
        $expectedUGX = Intake::where('active', true)->sum('expected_amount');

        if (empty($expectedUGX)) {
            $activeIntakeIds = Intake::where('active', true)->pluck('id')->toArray();
            $students = Student::whereIn('intake_id', $activeIntakeIds)->get();
            $expectedUGX = 0;
            foreach ($students as $s) {
                $fee = (float) ($s->course_fee ?? 0);
                $currency = strtoupper($s->currency ?? 'UGX');
                $expectedUGX += $currency === 'USD' ? ($fee * $usdRate) : $fee;
            }
        }

        // Convert receipts to UGX for the period and all-time
        $paymentsThisMonth = Payment::where('created_at', '>=', $startOfMonth)->get();
        $collectedUGX = 0;
        foreach ($paymentsThisMonth as $p) {
            $amt = (float) ($p->amount ?? 0);
            $collectedUGX += strtoupper($p->currency ?? 'UGX') === 'USD' ? ($amt * $usdRate) : $amt;
        }

        $paymentsAll = Payment::all();
        $collectedUGXAllTime = 0;
        foreach ($paymentsAll as $p) {
            $amt = (float) ($p->amount ?? 0);
            $collectedUGXAllTime += strtoupper($p->currency ?? 'UGX') === 'USD' ? ($amt * $usdRate) : $amt;
        }

        // Outstanding and collected percentage (month-to-date vs expected)
        $outstandingUGX = max(0, $expectedUGX - $collectedUGX);
        $collectedPct = $expectedUGX > 0 ? round(($collectedUGX / $expectedUGX) * 100, 2) : 0;
        $collectedPct = min(100, max(0, $collectedPct));

        // Recent employees and payments for the view (eager load student)
        $recentEmployees = Employee::latest()->take(6)->get();
        $recentPayments = Payment::with('student')->latest()->take(8)->get();

        // Active intakes with student counts and paid percentage
        $activeIntakes = Intake::where('active', true)
            ->withCount('students')
            ->get()
            ->map(function ($i) use ($usdRate) {
                $paidUGX = Payment::where('intake_id', $i->id)
                    ->where('currency', 'UGX')->sum('amount')
                    + (Payment::where('intake_id', $i->id)->where('currency', 'USD')->sum('amount') * $usdRate);

                $expected = $i->expected_amount ?? 0;
                // fallback to expected_per_student * students_count if available
                if (empty($expected) && isset($i->expected_per_student)) {
                    $expected = ($i->expected_per_student ?? 0) * ($i->students_count ?? 0);
                }

                $i->paid_pct = $expected > 0 ? (int) round(($paidUGX / $expected) * 100) : 0;

                // ensure start_date is Carbon instance for safe formatting in view
                if (!empty($i->start_date) && ! $i->start_date instanceof \Illuminate\Support\Carbon) {
                    $i->start_date = \Illuminate\Support\Carbon::parse($i->start_date);
                }

                return $i;
            });

        return view('admin.dashboard', compact(
            'activeIntakeCount',
            'studentCount',
            'totalExpensesAllTime',
            'ugxThisMonth',
            'usdThisMonth',
            'totalReceiptsUGXMonth',
            'expensesThisMonth',
            'currentBalanceUGXMonth',
            'currentBalanceUSDMonth',
            'totalCollectedUGXAll',
            'currentBalanceUGXAll',
            'currentBalanceUSDAll',
            'expectedUGX',
            'outstandingUGX',
            'collectedPct',
            'employeesCount',
            'recentEmployees',
            'reportsCount',
            'recentPayments',
            'activeIntakes'
        ));
    }
}