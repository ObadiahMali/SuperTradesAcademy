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
        $toUgx = function (?string $currency, float $amount) use ($rates): float {
            $currency = strtoupper((string) $currency ?: 'UGX');
            return $currency === 'USD' ? $rates->usdToUgx($amount) : $amount;
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

        // Sum course_fee grouped by currency (students may have course_fee in different currencies)
        $expectedByCurrency = $students
            ->groupBy(fn ($s) => strtoupper($s->currency ?? 'UGX'))
            ->map(fn ($group) => $group->sum(fn ($s) => (float) ($s->course_fee ?? 0)))
            ->toArray();

        $expectedUGXAll = 0.0;
        foreach ($expectedByCurrency as $currency => $total) {
            $expectedUGXAll += $toUgx($currency, (float) $total);
        }

        /** -----------------------------
         * Payments (build queries first, then get collections)
         * ----------------------------- */
        $paymentsBaseQuery = Payment::query();
        if ($intakeId) {
            $paymentsBaseQuery->where('intake_id', $intakeId);
        }

        // Build the period query (do NOT call get() yet if we need to reuse the query)
        $paymentsPeriodQuery = (clone $paymentsBaseQuery)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('paid_at', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->whereNull('paid_at')->whereBetween('created_at', [$start, $end]);
                  });
            });

        // Now fetch collections for calculations
        $paymentsPeriod = $paymentsPeriodQuery->get();
        $paymentsAll = (clone $paymentsBaseQuery)->get();

        // Compute collected sums by currency (period)
        $paymentsByCurrency = [];
        foreach ($paymentsPeriod as $p) {
            $cur = strtoupper($p->currency ?? 'UGX');
            $paymentsByCurrency[$cur] = ($paymentsByCurrency[$cur] ?? 0) + ((float) ($p->amount ?? 0));
        }

        // Compute collected sums by currency (all)
        $paymentsAllByCurrency = [];
        foreach ($paymentsAll as $p) {
            $cur = strtoupper($p->currency ?? 'UGX');
            $paymentsAllByCurrency[$cur] = ($paymentsAllByCurrency[$cur] ?? 0) + ((float) ($p->amount ?? 0));
        }

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

        // All-time collected percentage against expected (guard divide-by-zero)
        $collectedPctAll = 0.0;
        if (!empty($expectedUGXAll) && $expectedUGXAll > 0) {
            $collectedPctAll = round(min(100, ($collectedUGXAll / $expectedUGXAll) * 100), 2);
        }

        /** -----------------------------
         * Balances
         * ----------------------------- */
        $totalReceiptsUGXMonth = $collectedUGXMonth;
        $totalExpensesUGXMonth = $expensesThisMonth;
        $currentBalanceUGXMonth = $totalReceiptsUGXMonth - $totalExpensesUGXMonth;

        $totalReceiptsUGXAll = $collectedUGXAll;
        $totalExpensesUGXAll = $totalExpensesAll;
        $currentBalanceUGXAll = $totalReceiptsUGXAll - $totalExpensesUGXAll;

        // Receipts minus expenses (all time)
        $receiptsMinusExpensesAll = $totalReceiptsUGXAll - $totalExpensesUGXAll;

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
            $expected  = $intake->students->sum(fn ($s) => (float) ($s->course_fee ?? 0));
            $collected = $intake->payments->sum(fn ($p) => (float) ($p->amount ?? 0));
            $intake->paid_pct   = $expected > 0 ? round(($collected / $expected) * 100, 2) : 0;
            $intake->outstanding = max(0, $expected - $collected);
        });

        $activeIntake = $activeIntakes->first(); // null if none
        $activeIntakeCount = $activeIntake ? ($activeIntake->students_count ?? $activeIntake->students()->count()) : 0;

        /** -----------------------------
         * Recent payments (use the query builder, not a collection)
         * ----------------------------- */
        $recentPaymentsRaw = $paymentsPeriodQuery
            ->with('student')
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $recentPayments = $recentPaymentsRaw->map(function ($p) {
            // prefer amount_converted if present (already converted to UGX)
            $amount = null;
            $currency = strtoupper($p->currency ?? 'UGX');

            if (!is_null($p->amount_converted) && is_numeric($p->amount_converted)) {
                $amount = (float) $p->amount_converted;
                $currency = 'UGX';
            } elseif (is_numeric($p->amount)) {
                $amount = (float) $p->amount;
            } else {
                $amount = 0.0;
            }

            try {
                $paidAt = $p->paid_at ? Carbon::parse($p->paid_at) : null;
            } catch (\Throwable $e) {
                $paidAt = null;
            }

            return (object) [
                'id' => $p->id,
                'student' => $p->student,
                'reference' => $p->reference,
                'note' => $p->note,
                'method' => $p->method,
                'amount' => $amount,
                'currency' => $currency,
                'paid_at' => $paidAt,
                'created_at' => $p->created_at,
            ];
        });

        /** -----------------------------
         * Compatibility aliases for blades
         * ----------------------------- */
        $expectedUGX = $expectedUGXMonth;
        $outstandingUGX = $outstandingUGXMonth;
        $collectedPct = $collectedPctMonth;
        $totalExpensesAllTime = $totalExpensesAll;

        $intakes = Intake::withCount('students')->get();

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
            'collectedPctAll',
            'totalExpensesAll',
            'totalReceiptsUGXMonth',
            'totalExpensesUGXMonth',
            'currentBalanceUGXMonth',
            'currentBalanceUSDMonth',
            'totalReceiptsUGXAll',
            'totalExpensesUGXAll',
            'currentBalanceUGXAll',
            'currentBalanceUSDAll',
            'receiptsMinusExpensesAll',
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