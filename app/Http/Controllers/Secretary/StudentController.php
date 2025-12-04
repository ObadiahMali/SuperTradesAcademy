<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Mail\StudentEmailVerification;
use App\Models\Intake;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request)
    {
        $q = $request->query('q');
        $intakeId = $request->query('intake_id');

        $query = Student::with(['payments', 'intake'])->orderBy('created_at', 'desc');

        if ($q) {
            $query->where(function ($s) use ($q) {
                $s->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%")
                  ->orWhere('phone_full', 'like', "%{$q}%");
            });
        }

        if ($intakeId) {
            $query->where('intake_id', $intakeId);
        }

        $students = $query->paginate(20);

        $exchange = app(\App\Services\ExchangeRateService::class);
        $plansConfig = config('plans.plans') ?? [];

        foreach ($students as $student) {
            // Determine canonical course fee in UGX
            if (!empty($student->course_fee) && strtoupper($student->currency ?? 'UGX') === 'UGX') {
                $courseFeeUGX = (float) $student->course_fee;
            } else {
                $configPlan = $plansConfig[$student->plan_key] ?? null;
                $planPrice = $configPlan['price'] ?? 0;
                $planCurrency = strtoupper($configPlan['currency'] ?? ($student->currency ?? 'UGX'));

                if ($planCurrency === 'USD') {
                    $courseFeeUGX = (float) $exchange->usdToUgx($planPrice);
                } else {
                    $courseFeeUGX = (float) $planPrice;
                }
            }

            // Sum payments in UGX
            $totalPaidUGX = 0;
            foreach ($student->payments as $p) {
                if (!is_null($p->amount_converted)) {
                    $totalPaidUGX += (float) $p->amount_converted;
                } else {
                    $pCurrency = strtoupper($p->currency ?? 'UGX');
                    if ($pCurrency === 'USD') {
                        $totalPaidUGX += (float) $exchange->usdToUgx($p->amount);
                    } else {
                        $totalPaidUGX += (float) $p->amount;
                    }
                }
            }

            $student->amount_due = max(0, $courseFeeUGX - $totalPaidUGX);
            $student->display_currency = 'UGX';

            // Friendly plan label for index view
            $student->plan_label = $plansConfig[$student->plan_key]['label'] ?? ($student->plan_key ? ucfirst(str_replace('_', ' ', $student->plan_key)) : '—');

            // Ensure phone display fields exist for the view
            $student->phone_display = $student->phone_full
                ?? (!empty($student->phone_country_code) ? ('+' . ltrim($student->phone_country_code, '+') . ' ' . ($student->phone ?? '')) : ($student->phone ?? '—'));
        }

        $intakes = Intake::orderBy('start_date', 'desc')->get();
        $activeIntake = Intake::where('active', true)->first();

        return view('secretary.students.index', compact('students', 'intakes', 'activeIntake'));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create()
    {
        $intakes = Intake::orderBy('start_date', 'desc')->get();
        $plans = config('plans.plans') ?? [];

        return view('secretary.students.create', compact('intakes', 'plans'));
    }

    /**
     * Show the form for editing the specified student.
     */
    public function edit(Student $student)
    {
        $intake = $student->intake;
        $intakes = Intake::orderBy('start_date', 'desc')->get();
        $plans = config('plans.plans') ?? [];

        return view('secretary.students.edit', compact('student', 'intake', 'intakes', 'plans'));
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Student store payload', $request->all());

        $data = $request->validate([
            'intake_id'           => 'required|exists:intakes,id',
            'first_name'          => 'required|string|max:255',
            'last_name'           => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'phone_country_code'  => 'nullable|string|max:8',
            'email'               => 'nullable|email|unique:students,email',
            'plan_key'            => 'required|string',
            'address_line1'       => 'nullable|string|max:255',
            'address_line2'       => 'nullable|string|max:255',
            'city'                => 'nullable|string|max:120',
            'region'              => 'nullable|string|max:120',
            'postal_code'         => 'nullable|string|max:30',
            'country'             => 'nullable|string|max:120',
        ]);

        $plansConfig = config('plans.plans') ?? [];
        $configPlan = $plansConfig[$data['plan_key']] ?? null;
        $exchange = app(\App\Services\ExchangeRateService::class);

        if ($configPlan) {
            $planPrice = $configPlan['price'] ?? 0;
            $planCurrency = strtoupper($configPlan['currency'] ?? 'UGX');

            if ($planCurrency === 'USD') {
                // store canonical course_fee in UGX for consistency
                $data['course_fee'] = $exchange->usdToUgx($planPrice);
                $data['currency'] = 'UGX';
            } else {
                $data['course_fee'] = $planPrice;
                $data['currency'] = $planCurrency;
            }
        }

        // Normalize phone: keep digits only for phone, keep country code separate if provided
        $rawPhone = preg_replace('/\D+/', '', $data['phone'] ?? '');
        $data['phone'] = $rawPhone;
        if (!empty($data['phone_country_code'])) {
            $data['phone_country_code'] = preg_replace('/\D+/', '', $data['phone_country_code']);
            $data['phone_full'] = '+' . ltrim($data['phone_country_code'], '+') . ' ' . $rawPhone;
        } else {
            $data['phone_full'] = $rawPhone ? $rawPhone : null;
        }

        $plainToken = null;
        if (!empty($data['email'])) {
            $plainToken = Str::random(40);
            $data['email_verification_token'] = hash('sha256', $plainToken);
            $data['email_verification_sent_at'] = now();
        }

        $student = Student::create($data);

        if (!empty($student->email) && $plainToken) {
            try {
                Mail::to($student->email)->queue(new StudentEmailVerification($student, $plainToken));
            } catch (\Throwable $e) {
                \Log::error('Failed to queue student verification email', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('secretary.students.index')
            ->with('status', 'Student registered successfully. Verification email sent if an email was provided.');
    }

    /**
     * Display the specified student.
     */public function show(Student $student)
{
    $exchange = app(\App\Services\ExchangeRateService::class);
    $plansConfig = config('plans.plans') ?? [];

    // Determine plan price in UGX (or course_fee if already UGX)
    $plan = $plansConfig[$student->plan_key] ?? null;
    if ($plan && strtoupper($plan['currency'] ?? 'UGX') === 'USD') {
        $planPriceUGX = $exchange->usdToUgx((float) ($plan['price'] ?? 0));
    } else {
        // if plan not set or not USD, fall back to stored course_fee (assumed UGX)
        $planPriceUGX = (float) ($student->course_fee ?? 0);
    }

    // Total paid in UGX
    $totalPaidUGX = 0;
    foreach ($student->payments()->get() as $p) {
        if (!is_null($p->amount_converted)) {
            $totalPaidUGX += (float) $p->amount_converted;
        } else {
            $pCurrency = strtoupper($p->currency ?? 'UGX');
            if ($pCurrency === 'USD') {
                $totalPaidUGX += (float) $exchange->usdToUgx($p->amount);
            } else {
                $totalPaidUGX += (float) $p->amount;
            }
        }
    }

    $balanceUGX = max(0, $planPriceUGX - $totalPaidUGX);

    $payments = $student->payments()->orderByDesc('paid_at')->get();

    // Prepare phone display
    $phoneDisplay = $student->phone_full
        ?? (!empty($student->phone_country_code)
            ? ('+' . ltrim($student->phone_country_code, '+') . ' ' . ($student->phone ?? ''))
            : ($student->phone ?? '—'));

    // Plan label
    $planLabel = $plan['label'] ?? ($student->plan_key ? ucfirst(str_replace('_', ' ', $student->plan_key)) : '—');

    // Subtotal UGX (converted plan price)
    $subtotalUGX = $planPriceUGX;

    return view('secretary.students.show', compact(
        'student',
        'payments',
        'plan',
        'planLabel',
        'planPriceUGX',
        'totalPaidUGX',
        'balanceUGX',
        'phoneDisplay',
        'subtotalUGX' // <-- new variable for Blade
    ));
}

    /**
     * Verify student's email using token.
     */
    public function verifyEmail(Request $request)
    {
        $id = $request->query('id');
        $token = $request->query('token');

        if (!$id || !$token) {
            return redirect()->route('secretary.students.index')->with('error', 'Invalid verification link.');
        }

        $student = Student::find($id);
        if (!$student || !$student->email) {
            return redirect()->route('secretary.students.index')->with('error', 'Student not found.');
        }

        $hashed = hash('sha256', $token);

        if (!$student->email_verification_token || $student->email_verification_token !== $hashed) {
            return redirect()->route('secretary.students.index')->with('error', 'Invalid or expired verification token.');
        }

        if ($student->email_verification_sent_at && $student->email_verification_sent_at->diffInHours(now()) > 48) {
            return redirect()->route('secretary.students.index')->with('error', 'Verification token expired. Please request a new one.');
        }

        $student->email_verified_at = now();
        $student->email_verification_token = null;
        $student->email_verification_sent_at = null;
        $student->save();

        return redirect()->route('secretary.students.index')->with('status', 'Email verified successfully.');
    }

    /**
     * Resend verification email for a student.
     */
    public function resendVerification(Request $request)
    {
        $request->validate(['student_id' => 'required|exists:students,id']);

        $student = Student::find($request->student_id);
        if (!$student->email) {
            return back()->with('error', 'Student has no email address.');
        }

        if ($student->email_verification_sent_at && $student->email_verification_sent_at->diffInMinutes(now()) < 5) {
            return back()->with('error', 'Please wait a few minutes before resending verification.');
        }

        // generate a new token and update student
        $plainToken = Str::random(40);
        $student->email_verification_token = hash('sha256', $plainToken);
        $student->email_verification_sent_at = now();
        $student->save();

        try {
            Mail::to($student->email)->queue(new StudentEmailVerification($student, $plainToken));
        } catch (\Throwable $e) {
            \Log::error('Failed to queue resend verification email', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to send verification email.');
        }

        return back()->with('status', 'Verification email resent.');
    }

    /**
     * Update the specified student in storage.
     */
    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'intake_id'           => 'required|exists:intakes,id',
            'first_name'          => 'required|string|max:255',
            'last_name'           => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'phone_country_code'  => 'nullable|string|max:8',
            'email'               => 'nullable|email|unique:students,email,' . $student->id,
            'plan_key'            => 'nullable|string',
            'address_line1'       => 'nullable|string|max:255',
            'address_line2'       => 'nullable|string|max:255',
            'city'                => 'nullable|string|max:120',
            'region'              => 'nullable|string|max:120',
            'postal_code'         => 'nullable|string|max:30',
            'country'             => 'nullable|string|max:120',
        ]);

        // Normalize phone and phone_full
        $rawPhone = preg_replace('/\D+/', '', $data['phone'] ?? '');
        $data['phone'] = $rawPhone;
        if (!empty($data['phone_country_code'])) {
            $data['phone_country_code'] = preg_replace('/\D+/', '', $data['phone_country_code']);
            $data['phone_full'] = '+' . ltrim($data['phone_country_code'], '+') . ' ' . $rawPhone;
        } else {
            $data['phone_full'] = $rawPhone ? $rawPhone : null;
        }

        // If plan changed, update course_fee accordingly
        if (!empty($data['plan_key'])) {
            $plansConfig = config('plans.plans') ?? [];
            $configPlan = $plansConfig[$data['plan_key']] ?? null;
            $exchange = app(\App\Services\ExchangeRateService::class);

            if ($configPlan) {
                $planPrice = $configPlan['price'] ?? 0;
                $planCurrency = strtoupper($configPlan['currency'] ?? 'UGX');

                if ($planCurrency === 'USD') {
                    $data['course_fee'] = $exchange->usdToUgx($planPrice);
                    $data['currency'] = 'UGX';
                } else {
                    $data['course_fee'] = $planPrice;
                    $data['currency'] = $planCurrency;
                }
            }
        }

        $student->update($data);

        return redirect()->route('secretary.students.index')->with('status', 'Student updated successfully.');
    }

    /**
     * Remove the specified student from storage.
     */
    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('secretary.students.index')->with('success', 'Student deleted.');
    }
}