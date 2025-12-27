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
use App\Services\ExchangeRateService;

class AdminDashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index(Request $request, ExchangeRateService $rates)
    {
        // Basic counts
        $activeIntakeCount = Intake::where('active', true)->count();
        $studentCount = Student::count();
        $employeesCount = Employee::count();
        $reportsCount = Report::count();

        // Expenses (all-time) and month start
        $totalExpensesAllTime = (float) Expense::sum('amount');
        $startOfMonth = now()->startOfMonth();
        $expensesThisMonth = (float) Expense::where('created_at', '>=', $startOfMonth)->sum('amount');

        // Month-to-date receipts (grouped by currency) for display
        $ugxThisMonth = (float) Payment::where('currency', 'UGX')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $usdThisMonth = (float) Payment::where('currency', 'USD')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $totalReceiptsUGXMonth = $ugxThisMonth + ($usdThisMonth ? $rates->usdToUgx($usdThisMonth) : 0);

        $currentBalanceUGXMonth = $totalReceiptsUGXMonth - $expensesThisMonth;
        $currentBalanceUSDMonth = $rates->usdToUgx(1) > 0 ? $currentBalanceUGXMonth / $rates->usdToUgx(1) : 0;

        // Quick all-time receipts converted (legacy/quick total)
        $totalCollectedUGXAll = (float) Payment::where('currency', 'UGX')->sum('amount')
            + ($rates->usdToUgx((float) Payment::where('currency', 'USD')->sum('amount')));

        $currentBalanceUGXAll = $totalCollectedUGXAll - $totalExpensesAllTime;
        $currentBalanceUSDAll = $rates->usdToUgx(1) > 0 ? $currentBalanceUGXAll / $rates->usdToUgx(1) : 0;

        //
        // Canonical all-time expected and collected (mirror Secretary logic)
        //
        $activeIntakeIds = Intake::where('active', true)->pluck('id')->toArray();

        // EXPECTED (all-time, UGX) — sum students' course_fee grouped by currency then convert
        $studentsInActive = Student::when(!empty($activeIntakeIds), fn($q) => $q->whereIn('intake_id', $activeIntakeIds))
            ->get(['id', 'course_fee', 'currency']);

        $expectedByCurrency = $studentsInActive
            ->groupBy(fn($s) => strtoupper($s->currency ?? 'UGX'))
            ->map(fn($group) => $group->sum(fn($s) => (float) ($s->course_fee ?? 0)))
            ->toArray();

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

        // Fallback to intake.expected_amount if student-based expected is zero
        if (empty($expectedUGXAll)) {
            $expectedUGXAll = (float) Intake::where('active', true)->sum('expected_amount');
        }

        // COLLECTED (all-time, UGX) — prefer persisted amount_converted, otherwise convert amount
        $paymentsAllQuery = Payment::when(!empty($activeIntakeIds), fn($q) => $q->whereIn('intake_id', $activeIntakeIds));

        // Sum persisted converted amounts (fast DB sum)
        $convertedPersistedSum = (float) $paymentsAllQuery->whereNotNull('amount_converted')->sum('amount_converted');

        // Convert remaining payments on the fly
        $toConvert = (clone $paymentsAllQuery)->whereNull('amount_converted')->get(['amount', 'currency']);
        $convertedOnTheFly = $toConvert->sum(function ($p) use ($rates) {
            $amt = (float) ($p->amount ?? 0);
            return strtoupper($p->currency ?? 'UGX') === 'USD' ? $rates->usdToUgx($amt) : $amt;
        });

        $collectedUGXAll = $convertedPersistedSum + $convertedOnTheFly;

        // All-time outstanding and percent (against expected target)
        $outstandingUGXAll = max(0, $expectedUGXAll - $collectedUGXAll);
        $collectedPctAll = $expectedUGXAll > 0
            ? round(min(100, ($collectedUGXAll / $expectedUGXAll) * 100), 2)
            : 0.0;
        $collectedPctAll = min(100, max(0, $collectedPctAll));

        // For month-to-date tiles (kept for compatibility)
        $paymentsThisMonth = Payment::when(!empty($activeIntakeIds), fn($q) => $q->whereIn('intake_id', $activeIntakeIds))
            ->where('created_at', '>=', $startOfMonth)
            ->get(['amount', 'currency', 'amount_converted']);

        $collectedUGXMonth = 0.0;
        foreach ($paymentsThisMonth as $p) {
            if (!is_null($p->amount_converted)) {
                $collectedUGXMonth += (float) $p->amount_converted;
                continue;
            }
            $amt = (float) ($p->amount ?? 0);
            $collectedUGXMonth += strtoupper($p->currency ?? 'UGX') === 'USD' ? $rates->usdToUgx($amt) : $amt;
        }

        $outstandingUGXMonth = max(0, $expectedUGXAll - $collectedUGXMonth);
        $collectedPctMonth = $expectedUGXAll > 0 ? round(($collectedUGXMonth / $expectedUGXAll) * 100, 2) : 0;
        $collectedPctMonth = min(100, max(0, $collectedPctMonth));

        // Recent employees and payments for the view (eager load student)
        $recentEmployees = Employee::latest()->take(6)->get();
        $recentPayments = Payment::with('student')->latest()->take(8)->get();

        // Active intakes with student counts and paid percentage (all-time)
        $activeIntakes = Intake::where('active', true)
            ->withCount('students')
            ->with(['students', 'payments'])
            ->get();

        $activeIntakes->each(function ($i) use ($rates) {
            // expected for intake from its students (group by currency then convert)
            $expectedByCurrency = $i->students
                ->groupBy(fn($s) => strtoupper($s->currency ?? 'UGX'))
                ->map(fn($group) => $group->sum(fn($s) => (float) ($s->course_fee ?? 0)))
                ->toArray();

            $expectedUGXLocal = 0.0;
            foreach ($expectedByCurrency as $cur => $tot) {
                $expectedUGXLocal += strtoupper($cur) === 'USD' ? app(ExchangeRateService::class)->usdToUgx((float) $tot) : (float) $tot;
            }

            // paid for intake (prefer amount_converted)
            $paidUGX = (float) $i->payments->sum(function ($p) use ($rates) {
                if (!is_null($p->amount_converted)) return (float) $p->amount_converted;
                $amt = (float) ($p->amount ?? 0);
                return strtoupper($p->currency ?? 'UGX') === 'USD' ? $rates->usdToUgx($amt) : $amt;
            });

            $i->paid_pct = $expectedUGXLocal > 0 ? round(($paidUGX / $expectedUGXLocal) * 100, 2) : 0;
            $i->outstanding = max(0, $expectedUGXLocal - $paidUGX);

            if (!empty($i->start_date) && ! $i->start_date instanceof \Illuminate\Support\Carbon) {
                $i->start_date = \Illuminate\Support\Carbon::parse($i->start_date);
            }
        });

        // Backwards-compatible aliases for existing blades that expect older variable names
        // (these ensure existing templates continue to work)
        $expectedUGX = $expectedUGXAll;
        $collectedUGXAllTime = $collectedUGXAll;

        // Pass variables to view exactly as your blade expects
        return view('admin.dashboard', compact(
            'activeIntakeCount',
            'studentCount',
            'employeesCount',
            'reportsCount',
            'totalExpensesAllTime',
            'ugxThisMonth',
            'usdThisMonth',
            'totalReceiptsUGXMonth',
            'expensesThisMonth',
            'currentBalanceUGXMonth',
            'currentBalanceUSDMonth',
            // keep legacy quick total (if used elsewhere)
            'totalCollectedUGXAll',
            'currentBalanceUGXAll',
            'currentBalanceUSDAll',
            // canonical all-time variables (from Secretary logic)
            'expectedUGXAll',    // canonical expected (all-time, UGX)
            'collectedUGXAll',   // canonical collected (all-time, UGX)
            'collectedPctAll',   // percent collected (all-time)
            'outstandingUGXAll', // outstanding (all-time, UGX)
            // backwards-compatible aliases
            'expectedUGX',           // alias -> expectedUGXAll
            'collectedUGXAllTime',   // alias name used in some blades
            // month-to-date compatibility variables
            'collectedUGXMonth',
            'outstandingUGXMonth',
            'collectedPctMonth',
            'recentEmployees',
            'recentPayments',
            'activeIntakes'
        ));
    }
}