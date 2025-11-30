<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Route;

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
        $to = $request->query('to');

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
        $endOfMonth = Carbon::now()->endOfMonth();

        $thisMonthTotals = Payment::whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->get()
            ->pluck('total', 'currency')
            ->map(fn($v) => (float) $v);

        return view('secretary.payments.index', [
            'payments' => $payments,
            'totalsByCurrency' => $totalsByCurrency,
            'thisMonthTotals' => $thisMonthTotals,
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

        $receiptNumber = 'STA-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
        $rateService = app(ExchangeRateService::class);

        $amount = (float) $data['amount'];
        $currency = strtoupper($data['currency']);
        $convertedAmount = $currency === 'USD'
            ? $rateService->usdToUgx($amount)
            : $amount;

        $payment = new Payment([
            'student_id' => $student->id,
            'intake_id' => $student->intake_id,
            'amount' => $amount,
            'currency' => $currency,
            'amount_converted' => $convertedAmount,
            'converted_currency' => 'UGX',
            'method' => $data['method'],
            'paid_at' => now(),
            'receipt_number' => $receiptNumber,
            'plan_key' => $student->plan_key,
            'created_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        $payment->save();

        if (Route::has('secretary.students.show')) {
            return redirect()->route('secretary.students.show', $student)->with('success', 'Payment recorded.');
        }

        return redirect()->route('secretary.payments.index')->with('success', 'Payment recorded.');
    }

    /**
     * Display a single payment.
     */
    public function show(Payment $payment)
    {
        $payment->load('student.intake', 'creator');
        return view('secretary.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit(Payment $payment)
    {
        $payment->load('student');
        return view('secretary.payments.edit', compact('payment'));
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'amount'   => 'required|numeric|min:0',
            'currency' => 'required|in:UGX,USD',
            'method'   => 'required|string|max:100',
            'notes'    => 'nullable|string',
        ]);

        $rateService = app(ExchangeRateService::class);

        $payment->amount = (float) $data['amount'];
        $payment->currency = strtoupper($data['currency']);
        $payment->amount_converted = $payment->currency === 'USD'
            ? $rateService->usdToUgx($payment->amount)
            : $payment->amount;
        $payment->method = $data['method'];
        $payment->notes = $data['notes'] ?? null;
        $payment->save();

        return redirect()->route('secretary.payments.show', $payment)->with('success', 'Payment updated.');
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

        return view('secretary.students.receipt', compact(
            'student','payments','planLabel','originalDisplay','totalPaidUGX','balanceUGX'
        ));
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

        $receiptNumber = $payment->receipt_number ?? $payment->reference ?? '—';
        $receiptDate = $payment->paid_at ?? $payment->created_at ?? now();

        return view('secretary.students.receipt', compact(
            'student','payments','planLabel','originalDisplay','totalPaidUGX','balanceUGX','receiptNumber','receiptDate'
        ));
    }

    /**
     * Generate and download a PDF receipt for a payment.
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
            $payment->verification_hash = substr(hash('sha256', $payment->id . $payment->created_at), 0, 10);
            $payment->save();
        }

        $receipt = (object)[
            'number' => $payment->receipt_number,
            'issued_at' => $payment->paid_at ?? $payment->created_at,
            'verification_code' => $payment->verification_hash,
            'received_by_name' => $payment->created_by ? optional($payment->creator)->name : 'System',
        ];

        $lineItems = method_exists($payment, 'lineItems') ? $payment->lineItems : [];

        $qr = QrCode::format('png')->size(120)->generate($receipt->verification_code);
        $qrBase64 = 'data:image/png;base64,' . base64_encode($qr);

        $totalPaid = $student->payments->sum(fn($p) => (float) ($p->amount_converted ?? $p->amount));
        $courseFee = $student->course_fee ?? 0;
        $amountDue = max(0, $courseFee - $totalPaid);

        $planKey = $student->plan_key ?? 'physical_mentorship';
        $plan = config("plans.plans.$planKey") ?? [];
        $currency = $plan['currency'] ?? 'UGX';

        $pdf = Pdf::loadView('secretary.payments.receipt', compact(
            'payment', 'student', 'intake', 'receipt',
            'lineItems', 'qrBase64', 'amountDue', 'courseFee', 'totalPaid', 'plan', 'currency'
        ));

        return $pdf->download($receipt->number . '.pdf');
    }
}