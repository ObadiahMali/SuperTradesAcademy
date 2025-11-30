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
    public function index(Request $request, ExchangeRateService $rates)
    {
        $intakeId = $request->query('intake_id');
        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))->startOfDay()
            : now()->startOfMonth();
        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))->endOfDay()
            : now()->endOfMonth();

        //
        // STUDENTS & EXPECTED FEES
        //
        $studentsQuery = Student::query()->with('payments');
        if ($intakeId) {
            $studentsQuery->where('intake_id', $intakeId);
        }
        $students = $studentsQuery->get();
        $studentCount = $students->count();

        $expectedByCurrency = $students
            ->groupBy(fn($s) => strtoupper($s->currency ?? 'UGX'))
            ->map(fn($group) => $group->sum('course_fee'))
            ->toArray();

        $expectedUGX = 0.0;
        foreach ($expectedByCurrency as $currency => $total) {
            $expectedUGX += strtoupper($currency) === 'USD'
                ? $rates->usdToUgx((float)$total)
                : (float)$total;
        }

        //
        // PAYMENTS - month-to-date (scoped) and all-time (unscoped)
        //
        // Month-to-date payments
        $paymentsQuery = Payment::whereBetween('paid_at', [$start, $end]);
        if ($intakeId) {
            $paymentsQuery->where('intake_id', $intakeId);
        }

        $paymentsByCurrency = $paymentsQuery
            ->selectRaw('currency, SUM(COALESCE(amount,0)) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        // All-time payments (no date filter)
        $paymentsAllByCurrency = Payment::selectRaw('currency, SUM(COALESCE(amount,0)) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        // Convert collected amounts to UGX
        $collectedUGX = 0.0; // month-to-date collected in UGX
        foreach ($paymentsByCurrency as $currency => $total) {
            $collectedUGX += strtoupper($currency) === 'USD'
                ? $rates->usdToUgx((float)$total)
                : (float)$total;
        }

        $collectedUGXAllTime = 0.0; // all-time collected in UGX
        foreach ($paymentsAllByCurrency as $currency => $total) {
            $collectedUGXAllTime += strtoupper($currency) === 'USD'
                ? $rates->usdToUgx((float)$total)
                : (float)$total;
        }

        // Raw month totals by currency (not converted)
        $ugxThisMonth = (float) ($paymentsByCurrency['UGX'] ?? 0);
        $usdThisMonth = (float) ($paymentsByCurrency['USD'] ?? 0);

        //
        // EXPENSES - month-to-date and all-time
        //
        $expensesThisMonth = Expense::whereBetween('incurred_at', [$start, $end])
            ->when($intakeId, fn($q) => $q->where('intake_id', $intakeId))
            ->sum('amount');

        $expensesThisMonthCount = Expense::whereBetween('incurred_at', [$start, $end])
            ->when($intakeId, fn($q) => $q->where('intake_id', $intakeId))
            ->count();

        $totalExpensesAllTime = Expense::sum('amount');

        //
        // KPIs: outstanding, collected %
        //
        $outstandingUGX = max(0, $expectedUGX - $collectedUGX);
        $collectedPct = $expectedUGX > 0 ? (int) round(($collectedUGX / $expectedUGX) * 100) : 0;
        $collectedPct = min(100, max(0, $collectedPct));

        //
        // BALANCES: month-to-date and all-time
        //
        // Option A: Month-to-date balance (receipts this period minus expenses this period)
        $totalReceiptsUGXMonth = $collectedUGX;
        $totalExpensesUGXMonth = $expensesThisMonth;
        $currentBalanceUGXMonth = $totalReceiptsUGXMonth - $totalExpensesUGXMonth;

        // Option B: All-time balance (all receipts minus all expenses)
        $totalReceiptsUGXAll = $collectedUGXAllTime;
        $totalExpensesUGXAll = $totalExpensesAllTime;
        $currentBalanceUGXAll = $totalReceiptsUGXAll - $totalExpensesUGXAll;

        // Convert balances to USD using exchange service
        $rateUGXPerUSD = $rates->usdToUgx(1);
        $currentBalanceUSDMonth = $rateUGXPerUSD > 0 ? $currentBalanceUGXMonth / $rateUGXPerUSD : 0;
        $currentBalanceUSDAll = $rateUGXPerUSD > 0 ? $currentBalanceUGXAll / $rateUGXPerUSD : 0;

        //
        // Active intake and other view data
        //
        $activeIntake = Intake::where('active', true)->first();
        $activeIntakeCount = $activeIntake
            ? Student::where('intake_id', $activeIntake->id)->count()
            : 0;

        $intakes = Intake::withCount('students')->get();
        $recentPayments = Payment::with('student')->latest()->limit(8)->get();

        return view('secretary.dashboard', compact(
            // expected / collected KPIs
            'expectedUGX', 'collectedUGX', 'outstandingUGX', 'collectedPct',
            'ugxThisMonth', 'usdThisMonth',

            // expenses (month)
            'expensesThisMonth', 'expensesThisMonthCount',

            // all-time totals
            'totalExpensesAllTime',

            // receipts & balances (month and all-time)
            'totalReceiptsUGXMonth', 'totalExpensesUGXMonth', 'currentBalanceUGXMonth', 'currentBalanceUSDMonth',
            'totalReceiptsUGXAll', 'totalExpensesUGXAll', 'currentBalanceUGXAll', 'currentBalanceUSDAll',

            // other dashboard data
            'studentCount', 'activeIntake', 'activeIntakeCount',
            'intakes', 'recentPayments'
        ));
    }
}