<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Schema;
// use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
// use App\Services\ExchangeRateService;
use App\Mail\PaymentReceiptMail;
// use App\Models\Payment;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments with filters and totals.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['student.intake', 'student'])->latest();

        if ($q = $request->query('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('reference', 'like', "%{$q}%")
                   ->orWhere('method', 'like', "%{$q}%")
                   ->orWhere('amount', 'like', "%{$q}%")
                   ->orWhereHas('student', function ($s) use ($q) {
                       $s->where('first_name', 'like', "%{$q}%")
                         ->orWhere('last_name', 'like', "%{$q}%")
                         ->orWhere('student_id', 'like', "%{$q}%");
                   });
            });
        }

        if ($currency = $request->query('currency')) {
            $query->where('currency', $currency);
        }

        $from = $request->query('from');
        $to   = $request->query('to');

        if ($from || $to) {
            $fromDate = $from ? Carbon::parse($from)->startOfDay()->toDateTimeString() : null;
            $toDate   = $to   ? Carbon::parse($to)->endOfDay()->toDateTimeString()   : null;

            $query->where(function ($qb) use ($fromDate, $toDate) {
                if ($fromDate && $toDate) {
                    $qb->whereBetween('paid_at', [$fromDate, $toDate])
                       ->orWhereBetween('created_at', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $qb->where(function ($q) use ($fromDate) {
                        $q->where('paid_at', '>=', $fromDate)
                          ->orWhere('created_at', '>=', $fromDate);
                    });
                } elseif ($toDate) {
                    $qb->where(function ($q) use ($toDate) {
                        $q->where('paid_at', '<=', $toDate)
                          ->orWhere('created_at', '<=', $toDate);
                    });
                }
            });
        }

        $totalsQuery = (clone $query);
        $payments = $query->paginate(10)->withQueryString();

        $totalsByCurrency = $totalsQuery
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->get()
            ->pluck('total', 'currency')
            ->map(fn($v) => (float) $v);

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        $thisMonthTotals = Payment::whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->get()
            ->pluck('total', 'currency')
            ->map(fn($v) => (float) $v);

        // Recent payments with student eager-loaded
        $recentPayments = Payment::with('student')
            ->orderByDesc('paid_at')
            ->limit(8)
            ->get();

        return view('secretary.payments.index', [
            'payments'         => $payments,
            'totalsByCurrency' => $totalsByCurrency,
            'thisMonthTotals'  => $thisMonthTotals,
            'recentPayments'   => $recentPayments,
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(Request $request)
    {
        $student = null;
        if ($id = $request->query('student')) {
            $student = Student::find($id);
        }

        return view('secretary.payments.create', compact('student'));
    }

    /**
     * Store a newly created payment in storage.
     */
  public function store(Request $request)
{
    $data = $request->validate([
        'student_id' => 'required|exists:students,id',
        'amount'     => 'required|numeric|min:0',
        'currency'   => 'required|in:UGX,USD',
        'method'     => 'required|string|max:100',
        'notes'      => 'nullable|string',
    ]);

    $student = Student::findOrFail($data['student_id']);

    // Generate receipt number
    $receiptNumber = 'STA-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));

    // Convert amount to UGX if needed
    $rateService = app(ExchangeRateService::class);
    $amount = (float) $data['amount'];
    $currency = strtoupper($data['currency']);
    $convertedAmount = $currency === 'USD'
        ? $rateService->usdToUgx($amount)
        : $amount;

    // Build base attributes for persisted columns only
    $attrs = [
        'student_id'     => $student->id,
        'intake_id'      => $student->intake_id,
        'amount'         => $amount,
        'currency'       => $currency,
        'method'         => $data['method'],
        'paid_at'        => now(),
        'receipt_number' => $receiptNumber,
        'plan_key'       => $student->plan_key,
        'created_by'     => Auth::id(),
        'notes'          => $data['notes'] ?? null,
    ];

    // Persist amount_converted and converted_currency if columns exist
    if (Schema::hasColumn('payments', 'amount_converted')) {
        $attrs['amount_converted'] = $convertedAmount;
    }
    if (Schema::hasColumn('payments', 'converted_currency')) {
        $attrs['converted_currency'] = 'UGX';
    }

    $payment = Payment::create($attrs);

    // Persist verification_hash only if column exists, otherwise set transient attribute
    $verification = substr(hash('sha256', Str::uuid()->toString()), 0, 12);
    if (Schema::hasColumn('payments', 'verification_hash')) {
        $payment->verification_hash = $verification;
        $payment->save();
    } else {
        $payment->setAttribute('verification_hash', $verification);
    }

    // Persist created_by_name if column exists, otherwise set transient attribute
    $creatorName = Auth::user()->name ?? 'System';
    if (Schema::hasColumn('payments', 'created_by_name')) {
        $payment->created_by_name = $creatorName;
        $payment->save();
    } else {
        $payment->setAttribute('created_by_name', $creatorName);
    }

    // --- Recalculate outstanding balance in UGX ---
    // Sum all payments for this student in UGX (prefer stored converted amount)
    $totalPaidUgx = $student->payments()
        ->get()
        ->map(function ($p) use ($rateService) {
            if (isset($p->amount_converted) && $p->amount_converted !== null) {
                return (float) $p->amount_converted;
            }
            $pAmount = (float) ($p->amount ?? 0);
            return strtoupper($p->currency ?? 'UGX') === 'USD'
                ? $rateService->usdToUgx($pAmount)
                : $pAmount;
        })
        ->sum();

    // Determine course fee in UGX (use stored course_fee if present)
    $courseFeeUgx = (float) ($student->course_fee ?? 0);

    // If course_fee is missing, try to resolve from plan config (best-effort)
    if (empty($courseFeeUgx) && !empty($student->plan_key)) {
        $plan = \App\Models\Plan::where('key', $student->plan_key)->first();
        if ($plan) {
            // If plan price is in USD in config, convert; otherwise assume UGX
            $plansConfig = config('plans.plans') ?? [];
            $configPlan = $plansConfig[$plan->key] ?? null;
            if ($configPlan && strtoupper($configPlan['currency'] ?? 'UGX') === 'USD') {
                $courseFeeUgx = $rateService->usdToUgx($configPlan['price']);
            } else {
                $courseFeeUgx = (float) ($configPlan['price'] ?? $plan->price ?? 0);
            }
        }
    }

    $outstandingUgx = max(0, $courseFeeUgx - $totalPaidUgx);

    // Send receipt email (immediate send for now; switch to queue() + ShouldQueue later)
    if (!empty($student->email)) {
        try {
            Mail::to($student->email)->send(new PaymentReceiptMail($student, $payment, $outstandingUgx));
        } catch (\Throwable $e) {
            Log::error('Failed to send payment receipt', [
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // Redirect back to student profile if route exists
    if (Route::has('secretary.students.show')) {
        return redirect()
            ->route('secretary.students.show', $student->id)
            ->with('success', 'Payment recorded and receipt emailed.');
    }

    // Fallback redirect
    return redirect()
        ->route('secretary.payments.index')
        ->with('success', 'Payment recorded and receipt emailed.');
}

    /**
     * Display a single payment.
     */
 public function show(Payment $payment)
{
    $payment->load('student.intake', 'creator');

    $student = $payment->student;
    $phoneDisplay = $student->phone_full
        ?? $student->phone_display
        ?? (!empty($student->phone) ? ('+' . ($student->phone_country_code ?? '256') . ' ' . $student->phone) : null);

    // Ensure transient display attributes exist so views don't show dashes
    if (empty($payment->receipt_number)) {
        $payment->setAttribute('receipt_number', $payment->reference ?? null);
    }

    if (empty($payment->verification_hash)) {
        $payment->setAttribute('verification_hash', null);
    }

    $payment->setAttribute(
        'created_by_name',
        $payment->created_by ? optional($payment->creator)->name : (Auth::user()->name ?? 'System')
    );

    $receiptNumber = $payment->receipt_number ?? null;

    return view('secretary.payments.show', compact('payment', 'receiptNumber'))
        ->with('phoneDisplay', $phoneDisplay);
}

    /**
     * Show the form for editing the specified payment.
     */
   public function edit(Payment $payment)
{
    // Load relations used by the edit form
    $payment->load(['student', 'intake']); // add any other relations you need

    // Server-side authorization: allow only administrators
    $user = auth()->user();
    $isAdmin = $user && (
        (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('administrator'))) ||
        ($user->role ?? '') === 'admin' ||
        ($user->role ?? '') === 'administrator'
    );

    if (! $isAdmin) {
        // Option A: abort with 403
        abort(403, 'You are not authorized to edit payments.');

        // Option B: redirect back with message (uncomment if you prefer)
        // return redirect()->route('secretary.payments.index')->with('error', 'You are not authorized to edit payments.');
    }

    // Ensure the view exists to avoid a 500
    if (! \Illuminate\Support\Facades\View::exists('secretary.payments.edit')) {
        abort(404, 'Edit view for payments not found. Please create resources/views/secretary/payments/edit.blade.php');
    }

    return view('secretary.payments.edit', compact('payment'));
}

    /**
     * Update the specified payment in storage.
     */
public function update(Request $request, Payment $payment)
{
    // -------------------------------
    // Authorization: only admins allowed
    // -------------------------------
    $user = auth()->user();
    $isAdmin = $user && (
        (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('administrator'))) ||
        ($user->role ?? '') === 'admin' ||
        ($user->role ?? '') === 'administrator'
    );

    if (! $isAdmin) {
        return redirect()->route('secretary.payments.index')
                         ->with('error', 'You are not authorized to update payments.');
    }

    // -------------------------------
    // Validation
    // -------------------------------
    $data = $request->validate([
        'amount'   => 'required|numeric|min:0',
        'currency' => 'required|in:UGX,USD',
        'method'   => 'required|string|max:100',
        'notes'    => 'nullable|string',
    ]);

    // -------------------------------
    // Exchange rate service (optional)
    // -------------------------------
    $rateService = null;
    try {
        $rateService = app(\App\Services\ExchangeRateService::class);
    } catch (\Throwable $e) {
        \Log::warning('ExchangeRateService not available: ' . $e->getMessage());
    }

    // -------------------------------
    // Update Payment
    // -------------------------------
    try {
        $payment->amount = (float) $data['amount'];
        $payment->currency = strtoupper($data['currency']);
        $payment->method = $data['method'];
        $payment->notes = $data['notes'] ?? null;

        // Convert amount if needed
        if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'amount_converted')) {
            if ($payment->currency === 'USD' && $rateService) {
                $payment->amount_converted = $rateService->usdToUgx($payment->amount);
            } else {
                $payment->amount_converted = $payment->amount;
            }
        }

        $payment->save();

        return redirect()->route('secretary.payments.show', $payment)
                         ->with('success', 'Payment updated successfully.');

    } catch (\Throwable $e) {
        \Log::error('Payment update failed', [
            'payment_id' => $payment->id,
            'user_id'    => $user->id ?? null,
            'payload'    => $data,
            'error'      => $e->getMessage(),
        ]);

        return redirect()->back()->withInput()->with('error', 'Failed to update payment. Please try again.');
    }
}



    /**
     * Display the receipt view for a student (web).
     */
    public function receipt(Student $student)
    {
        $student->load('payments', 'intake');

        $payments = $student->payments;

        $planKey = $student->plan_key ?? 'physical_mentorship';
        $planCfg = config("plans.plans.$planKey") ?? null;
        $planLabel = $planCfg['label'] ?? ($planKey ?? 'Unknown');

        if ($planCfg && isset($planCfg['price'])) {
            $originalDisplay = strtoupper($planCfg['currency'] ?? 'UGX') . ' ' . number_format($planCfg['price'], 2);
        } else {
            $originalDisplay = 'UGX ' . number_format($student->course_fee ?? 0, 2);
        }

        $totalPaidUGX = $payments->sum(fn($p) => (float) ($p->amount_converted ?? 0));
        $balanceUGX = max((float) ($student->course_fee ?? 0) - $totalPaidUGX, 0);

        $phoneDisplay = $student->phone_full
            ?? ($student->phone_display ?? (!empty($student->phone) ? ('+' . ($student->phone_country_code ?? '256') . ' ' . $student->phone) : null));

        return view('secretary.students.receipt', compact(
            'student','payments','planLabel','originalDisplay','totalPaidUGX','balanceUGX'
        ))->with('phoneDisplay', $phoneDisplay);
    }

    /**
     * Display a receipt view for a specific payment (accepts Payment $payment).
     */
  public function receiptForPayment(Payment $payment)
{
    $payment->load('student.intake', 'creator');

    $student = $payment->student;
    $payments = $student->payments;

    $planKey = $student->plan_key ?? 'physical_mentorship';
    $planCfg = config("plans.plans.$planKey") ?? null;
    $planLabel = $planCfg['label'] ?? ($planKey ?? 'Unknown');

    if ($planCfg && isset($planCfg['price'])) {
        $originalDisplay = strtoupper($planCfg['currency'] ?? 'UGX') . ' ' . number_format($planCfg['price'], 2);
    } else {
        $originalDisplay = 'UGX ' . number_format($student->course_fee ?? 0, 2);
    }

    $totalPaidUGX = $payments->sum(fn($p) => (float) ($p->amount_converted ?? 0));
    $balanceUGX = max((float) ($student->course_fee ?? 0) - $totalPaidUGX, 0);

    // Compute a stable display receipt number (prefer stored receipt_number, then reference)
    $receiptNumber = $payment->receipt_number ?? $payment->reference ?? null;

    // Ensure the model has transient attributes the view may expect
    $payment->setAttribute('receipt_number', $receiptNumber);
    $payment->setAttribute('verification_hash', $payment->verification_hash ?? null);
    $payment->setAttribute('created_by_name', optional($payment->creator)->name ?? auth()->user()->name ?? 'System');

    $receiptDate = $payment->paid_at ?? $payment->created_at ?? now();
    $verificationCode = $payment->verification_hash ?? null;
    $receivedByName = $payment->created_by ? optional($payment->creator)->name : (auth()->user()->name ?? 'System');

    // reuse student-normalized phone
    $phoneDisplay = $student->phone_full
        ?? ($student->phone_display ?? (!empty($student->phone) ? ('+' . ($student->phone_country_code ?? '256') . ' ' . $student->phone) : null));

    // No QR generation — pass null if not needed
    $qrBase64 = null;

    // Pass the $payment model itself so the view can reference $payment->...
    return view('secretary.payments.receipt', compact(
        'payment',
        'student',
        'payments',
        'planLabel',
        'originalDisplay',
        'totalPaidUGX',
        'balanceUGX',
        'receiptNumber',
        'receiptDate',
        'verificationCode',
        'receivedByName',
        'qrBase64'
    ))->with('phoneDisplay', $phoneDisplay);
}

    /**
     * Generate and download a PDF receipt for a payment (no QR).
     */
    public function receiptPdf(Payment $payment)
    {
        $payment->load('student.intake', 'creator');
        $student = $payment->student;
        $intake = $student->intake ?? null;

        if (!$payment->receipt_number) {
            $payment->receipt_number = 'REC-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT);
            $payment->save();
        }

        if (!$payment->verification_hash) {
            $verification = substr(hash('sha256', $payment->id . $payment->created_at), 0, 10);
            if (Schema::hasColumn('payments', 'verification_hash')) {
                $payment->verification_hash = $verification;
                $payment->save();
            } else {
                $payment->setAttribute('verification_hash', $verification);
            }
        }

        $receipt = (object)[
            'number' => $payment->receipt_number,
            'issued_at' => $payment->paid_at ?? $payment->created_at,
            'verification_code' => $payment->verification_hash,
            'received_by_name' => $payment->created_by ? optional($payment->creator)->name : 'System',
        ];

        $lineItems = method_exists($payment, 'lineItems') ? $payment->lineItems : [];

        // No QR generation; qrBase64 left null
        $qrBase64 = null;

        $totalPaid = $student->payments->sum(fn($p) => (float) ($p->amount_converted ?? $p->amount));
        $courseFee = $student->course_fee ?? 0;
        $amountDue = max(0, $courseFee - $totalPaid);

        $planKey = $student->plan_key ?? 'physical_mentorship';
        $plan = config("plans.plans.$planKey") ?? [];
        $currency = $plan['currency'] ?? 'UGX';

        // reuse phoneDisplay
        $phoneDisplay = $student->phone_full
            ?? ($student->phone_display ?? (!empty($student->phone) ? ('+' . ($student->phone_country_code ?? '256') . ' ' . $student->phone) : null));

        $pdf = Pdf::loadView('secretary.payments.receipt', compact(
            'payment', 'student', 'intake', 'receipt',
            'lineItems', 'qrBase64', 'amountDue', 'courseFee', 'totalPaid', 'plan', 'currency'
        ))->with('phoneDisplay', $phoneDisplay);

        return $pdf->download($receipt->number . '.pdf');
    }

    /**
     * Delete a payment.
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()->route('secretary.payments.index')->with('success', 'Payment deleted.');
    }
}