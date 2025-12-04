<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Expense;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with KPIs, balances, intakes, and recent payments.
     */
    public function index(Request $request, ExchangeRateService $rates)
    {
        $intakeId = $request->query('intake_id');

        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))->startOfDay()
            : now()->startOfMonth();

        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))->endOfDay()
            : now()->endOfMonth();

        // Helper to convert currency amounts to UGX
        $toUgx = function (string $currency, float $amount) use ($rates): float {
            return strtoupper($currency) === 'USD' ? $rates->usdToUgx($amount) : $amount;
        };

        /** -----------------------------
         * Students & expected fees
         * ----------------------------- */
        $studentsQuery = Student::query();
        if ($intakeId) {
            $studentsQuery->where('intake_id', $intakeId);
        }
        $students = $studentsQuery->get();
        $studentCount = $students->count();

        $expectedByCurrency = $students
            ->groupBy(fn ($s) => strtoupper($s->currency ?? 'UGX'))
            ->map(fn ($group) => $group->sum('course_fee'))
            ->toArray();

        $expectedUGXAll = 0.0;
        foreach ($expectedByCurrency as $currency => $total) {
            $expectedUGXAll += $toUgx($currency, (float) $total);
        }

        /** -----------------------------
         * Payments
         * ----------------------------- */
        $paymentsBase = Payment::query();
        if ($intakeId) {
            $paymentsBase->where('intake_id', $intakeId);
        }

        $paymentsPeriod = (clone $paymentsBase)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('paid_at', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->whereNull('paid_at')->whereBetween('created_at', [$start, $end]);
                  });
            });

        $paymentsByCurrency = $paymentsPeriod
            ->selectRaw('currency, SUM(COALESCE(amount,0)) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $paymentsAllByCurrency = (clone $paymentsBase)
            ->selectRaw('currency, SUM(COALESCE(amount,0)) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $collectedUGXMonth = 0.0;
        foreach ($paymentsByCurrency as $currency => $total) {
            $collectedUGXMonth += $toUgx($currency, (float) $total);
        }

        $collectedUGXAll = 0.0;
        foreach ($paymentsAllByCurrency as $currency => $total) {
            $collectedUGXAll += $toUgx($currency, (float) $total);
        }

        $ugxThisMonth = (float) ($paymentsByCurrency['UGX'] ?? 0);
        $usdThisMonth = (float) ($paymentsByCurrency['USD'] ?? 0);

        /** -----------------------------
         * Expenses
         * ----------------------------- */
        $expensesThisMonth = Expense::when($intakeId, fn ($q) => $q->where('intake_id', $intakeId))
            ->whereBetween('incurred_at', [$start, $end])
            ->sum('amount');

        $expensesThisMonthCount = Expense::when($intakeId, fn ($q) => $q->where('intake_id', $intakeId))
            ->whereBetween('incurred_at', [$start, $end])
            ->count();

        $totalExpensesAll = Expense::when($intakeId, fn ($q) => $q->where('intake_id', $intakeId))
            ->sum('amount');

        /** -----------------------------
         * KPIs
         * ----------------------------- */
        $expectedUGXMonth = $expectedUGXAll;
        $outstandingUGXMonth = max(0, $expectedUGXMonth - $collectedUGXMonth);
        $collectedPctMonth = $expectedUGXMonth > 0
            ? round(($collectedUGXMonth / $expectedUGXMonth) * 100, 2)
            : 0.0;
        $collectedPctMonth = min(100.0, max(0.0, $collectedPctMonth));

        /** -----------------------------
         * Balances
         * ----------------------------- */
        $totalReceiptsUGXMonth = $collectedUGXMonth;
        $totalExpensesUGXMonth = $expensesThisMonth;
        $currentBalanceUGXMonth = $totalReceiptsUGXMonth - $totalExpensesUGXMonth;

        $totalReceiptsUGXAll = $collectedUGXAll;
        $totalExpensesUGXAll = $totalExpensesAll;
        $currentBalanceUGXAll = $totalReceiptsUGXAll - $totalExpensesUGXAll;

        $rateUGXPerUSD = $rates->usdToUgx(1);
        $currentBalanceUSDMonth = $rateUGXPerUSD > 0 ? $currentBalanceUGXMonth / $rateUGXPerUSD : 0.0;
        $currentBalanceUSDAll = $rateUGXPerUSD > 0 ? $currentBalanceUGXAll / $rateUGXPerUSD : 0.0;

        /** -----------------------------
         * Intakes
         * ----------------------------- */
        $activeIntakes = Intake::where('active', true)
            ->withCount('students')
            ->with(['students', 'payments'])
            ->get();

        $activeIntakes->each(function ($intake) {
            $expected  = $intake->students->sum('course_fee');
            $collected = $intake->payments->sum('amount');
            $intake->paid_pct   = $expected > 0 ? round(($collected / $expected) * 100, 2) : 0;
            $intake->outstanding = max(0, $expected - $collected);
        });

        $activeIntake = $activeIntakes->first(); // null if none
        $activeIntakeCount = $activeIntake ? ($activeIntake->students_count ?? $activeIntake->students()->count()) : 0;

        /** -----------------------------
         * Compatibility aliases for blades
         * ----------------------------- */
        $expectedUGX = $expectedUGXMonth;
        $outstandingUGX = $outstandingUGXMonth;
        $collectedPct = $collectedPctMonth;
        $totalExpensesAllTime = $totalExpensesAll;

        $intakes = Intake::withCount('students')->get();

        $recentPayments = (clone $paymentsPeriod)
            ->with('student')
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        /** -----------------------------
         * Return view
         * ----------------------------- */
        return view('secretary.dashboard', compact(
            'expectedUGXMonth',
            'collectedUGXMonth',
            'outstandingUGXMonth',
            'collectedPctMonth',
            'ugxThisMonth',
            'usdThisMonth',
            'expensesThisMonth',
            'expensesThisMonthCount',
            'expectedUGXAll',
            'collectedUGXAll',
            'totalExpensesAll',
            'totalReceiptsUGXMonth',
            'totalExpensesUGXMonth',
            'currentBalanceUGXMonth',
            'currentBalanceUSDMonth',
            'totalReceiptsUGXAll',
            'totalExpensesUGXAll',
            'currentBalanceUGXAll',
            'currentBalanceUSDAll',
            'studentCount',
            'activeIntakes',
            'activeIntake',
            'activeIntakeCount',
            'expectedUGX',
            'outstandingUGX',
            'collectedPct',
            'intakes',
            'totalExpensesAllTime',
            'recentPayments'
        ));
    }
}