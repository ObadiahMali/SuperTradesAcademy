<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Intake;
use App\Models\Plan;
use App\Mail\StudentEmailVerification;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeUserMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDO;

// use Illuminate\Http\Request;
// use Illuminate\Support\Str;
// use Illuminate\Support\Facades\Mail;
// use Illuminate\Support\Facades\Password;
// use Illuminate\Support\Facades\Log;
// use App\Models\Student;
// use App\Models\Plan;
// use App\Services\ExchangeRateService;
// use App\Mail\StudentEmailVerification;
// use App\Mail\WelcomeUserMail;


class StudentController extends Controller
{
    /**
     * List students.
     */
public function index(Request $request)
{
    $q = trim($request->query('q', ''));

    $query = Student::with(['intake', 'payments']);

    if ($q !== '') {
        $qLike = "%{$q}%";

        $query->where(function ($sub) use ($qLike) {
            $sub->where('first_name', 'like', $qLike)
                ->orWhere('last_name', 'like', $qLike)
                ->orWhere('email', 'like', $qLike)
                ->orWhere('phone_full', 'like', $qLike);

            // safe concatenation fallback: use DB::raw with CONCAT for MySQL/Postgres,
            // and a second raw for sqlite. Use bindings to avoid injection.
            $driver = DB::getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

            if ($driver === 'sqlite') {
                $sub->orWhereRaw("first_name || ' ' || last_name LIKE ?", [$qLike]);
            } else {
                $sub->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$qLike]);
            }
        });
    }

    if ($request->filled('intake_id')) {
        $query->where('intake_id', $request->query('intake_id'));
    }

    $perPage = (int) $request->query('per_page', 20);
    $perPage = $perPage > 0 && $perPage <= 200 ? $perPage : 20;

    $students = $query->latest()->paginate($perPage)->withQueryString();

    return view('secretary.students.index', compact('students'));
}




    /**
     * Show form to create a new student.
     */
    public function create()
    {
        $intakes = Intake::orderBy('start_date', 'desc')->get();
        $plans   = Plan::orderBy('key', 'asc')->get();

        // Provide the same country list used in the Blade so the view doesn't need to hardcode it.
        $countries = [
            ['code' => 'UG', 'dial' => '+256', 'label' => 'Uganda'],
            ['code' => 'KE', 'dial' => '+254', 'label' => 'Kenya'],
            ['code' => 'TZ', 'dial' => '+255', 'label' => 'Tanzania'],
            ['code' => 'RW', 'dial' => '+250', 'label' => 'Rwanda'],
            ['code' => 'US', 'dial' => '+1',   'label' => 'United States'],
            ['code' => 'GB', 'dial' => '+44',  'label' => 'United Kingdom'],
            ['code' => 'NG', 'dial' => '+234', 'label' => 'Nigeria'],
            ['code' => 'ZA', 'dial' => '+27',  'label' => 'South Africa'],
            ['code' => 'IN', 'dial' => '+91',  'label' => 'India'],
            ['code' => 'ZM', 'dial' => '+260', 'label' => 'Zambia'],
        ];

        $defaultPhoneCountry = config('app.default_phone_country', '+256');

        return view('secretary.students.create', compact('intakes', 'plans', 'countries', 'defaultPhoneCountry'));
    }

    /**
     * Store new student.
     */
   /**
     * Store a newly created student in storage and send emails.
     */
  public function store(Request $request)
{
    $data = $request->validate([
        'intake_id'            => 'required|exists:intakes,id',
        'first_name'           => 'required|string|max:255',
        'last_name'            => 'nullable|string|max:255',
        'phone'                => 'nullable|string|max:50',
        'phone_country'        => 'nullable|string|max:32',
        'phone_country_code'   => 'nullable|string|max:8',
        'phone_full'           => 'nullable|string|max:32',
        'email'                => 'nullable|email|unique:students,email',
        'plan_key'             => 'required|string',
        'address_line1'        => 'nullable|string|max:255',
        'address_line2'        => 'nullable|string|max:255',
        'city'                 => 'nullable|string|max:120',
        'region'               => 'nullable|string|max:120',
        'postal_code'          => 'nullable|string|max:30',
        'country'              => 'nullable|string|max:120',
    ]);

    // Resolve plan model and config
    $plan = Plan::where('key', $data['plan_key'])->firstOrFail();
    $plansConfig = config('plans.plans') ?? [];
    $configPlan = $plansConfig[$plan->key] ?? null;
    $exchange = app(ExchangeRateService::class);

    if ($configPlan && strtoupper($configPlan['currency'] ?? 'UGX') === 'USD') {
        $data['course_fee'] = $exchange->usdToUgx($configPlan['price']);
        $data['currency'] = 'UGX';
    } else {
        $data['course_fee'] = $configPlan['price'] ?? $plan->price ?? 0;
        $data['currency'] = $configPlan['currency'] ?? $plan->currency ?? 'UGX';
    }

    $data['plan_key'] = $plan->key;

    // Normalize phone
    $rawLocal = preg_replace('/\D+/', '', $data['phone'] ?? '');
    $countryRaw = $data['phone_country_code'] ?? $data['phone_country'] ?? null;
    $countryDigits = $countryRaw ? preg_replace('/\D+/', '', $countryRaw) : null;
    $countryDigits = $countryDigits ?: preg_replace('/\D+/', '', config('app.default_phone_country', '+256')) ?: '256';

    $data['phone'] = $rawLocal ?: null;
    $data['phone_country_code'] = $countryDigits;
    $data['phone_full'] = $rawLocal ? ('+' . ltrim($countryDigits, '+') . $rawLocal) : null;
    $data['phone_display'] = $rawLocal ? ('+' . ltrim($countryDigits, '+') . ' ' . $rawLocal) : null;
    $data['phone_dial'] = '+' . ltrim($countryDigits, '+');

    // Email verification token (optional)
    $plainToken = null;
    if (!empty($data['email'])) {
        $plainToken = Str::random(40);
        $data['email_verification_token'] = hash('sha256', $plainToken);
        $data['email_verification_sent_at'] = now();
    }

    $student = Student::create($data);

    if ($student->email) {
        try {
            // 1) Send verification email if token exists
            if ($plainToken) {
                Mail::to($student->email)
                    ->send(new StudentEmailVerification($student, $plainToken));
            }

            // 2) Optionally generate a password reset link and include it in the welcome mail.
            // If you do not want a reset link, remove the Password::createToken block and pass only $student.
            $token = Password::createToken($student);
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $student->email,
            ], false));

            Mail::to($student->email)
                ->send(new WelcomeUserMail($student, $resetUrl));

            Log::info('Student emails sent', ['student_id' => $student->id, 'email' => $student->email]);

        } catch (\Throwable $e) {
            Log::error('Failed to send student emails', [
                'student_id' => $student->id,
                'email' => $student->email,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // In local environment rethrow so you see the error during development
            if (app()->environment('local')) {
                throw $e;
            }
        }
    }

    return redirect()
        ->route('secretary.students.index')
        ->with('status', 'Student registered successfully and emails sent.');
}


    /**
     * Show a specific student.
     */
    public function show(Student $student)
    {
        $exchange = app(ExchangeRateService::class);
        $plansConfig = config('plans.plans') ?? [];
        $plan = $plansConfig[$student->plan_key] ?? null;

        $planPriceUGX = ($plan && strtoupper($plan['currency'] ?? 'UGX') === 'USD')
            ? $exchange->usdToUgx($plan['price'])
            : ($student->course_fee ?? 0);

        $totalPaidUGX = $student->payments->sum(function ($p) use ($exchange) {
            if (!is_null($p->amount_converted)) {
                return (float) $p->amount_converted;
            }

            $pCurrency = strtoupper($p->currency ?? 'UGX');
            if ($pCurrency === 'USD') {
                return (float) $exchange->usdToUgx($p->amount);
            }

            return (float) $p->amount;
        });

        $phoneDisplay = $student->phone_full
            ?? ($student->buildPhoneFull() ?: (!empty($student->phone) ? ('+' . ($student->phone_country_code ?? '256') . ' ' . $student->phone) : '—'));

        return view('secretary.students.show', [
            'student' => $student,
            'payments' => $student->payments()->latest('paid_at')->get(),
            'plan' => $plan,
            'planLabel' => $plan['label'] ?? '—',
            'planPriceUGX' => $planPriceUGX,
            'totalPaidUGX' => $totalPaidUGX,
            'balanceUGX' => max(0, $planPriceUGX - $totalPaidUGX),
            'phoneDisplay' => $phoneDisplay,
            'subtotalUGX' => $planPriceUGX,
        ]);
    }

    

    /**
     * Show form to edit a student.
     */
    public function edit(Student $student)
    {
        $intakes = Intake::orderBy('start_date', 'desc')->get();
        $plans   = Plan::orderBy('key', 'asc')->get();

        // Provide countries and default to the view so edit form can preselect values
        $countries = [
            ['code' => 'UG', 'dial' => '+256', 'label' => 'Uganda'],
            ['code' => 'KE', 'dial' => '+254', 'label' => 'Kenya'],
            ['code' => 'TZ', 'dial' => '+255', 'label' => 'Tanzania'],
            ['code' => 'RW', 'dial' => '+250', 'label' => 'Rwanda'],
            ['code' => 'US', 'dial' => '+1',   'label' => 'United States'],
            ['code' => 'GB', 'dial' => '+44',  'label' => 'United Kingdom'],
            ['code' => 'NG', 'dial' => '+234', 'label' => 'Nigeria'],
            ['code' => 'ZA', 'dial' => '+27',  'label' => 'South Africa'],
            ['code' => 'IN', 'dial' => '+91',  'label' => 'India'],
            ['code' => 'ZM', 'dial' => '+260', 'label' => 'Zambia'],
        ];

        $defaultPhoneCountry = config('app.default_phone_country', '+256');

        return view('secretary.students.edit', compact('student', 'intakes', 'plans', 'countries', 'defaultPhoneCountry'));
    }

    /**
     * Update student.
     */
    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'intake_id'            => 'required|exists:intakes,id',
            'first_name'           => 'required|string|max:255',
            'last_name'            => 'nullable|string|max:255',
            'phone'                => 'nullable|string|max:50',
            'phone_country'        => 'nullable|string|max:32',
            'phone_country_code'   => 'nullable|string|max:8',
            'phone_full'           => 'nullable|string|max:32',
            'email'                => 'nullable|email|unique:students,email,' . $student->id,
            'plan_key'             => 'required|string',
            'address_line1'        => 'nullable|string|max:255',
            'address_line2'        => 'nullable|string|max:255',
            'city'                 => 'nullable|string|max:120',
            'region'               => 'nullable|string|max:120',
            'postal_code'          => 'nullable|string|max:30',
            'country'              => 'nullable|string|max:120',
        ]);

        $plan = Plan::where('key', $data['plan_key'])->firstOrFail();
        $plansConfig = config('plans.plans') ?? [];
        $configPlan = $plansConfig[$plan->key] ?? null;
        $exchange = app(ExchangeRateService::class);

        if ($configPlan && strtoupper($configPlan['currency'] ?? 'UGX') === 'USD') {
            $data['course_fee'] = $exchange->usdToUgx($configPlan['price']);
            $data['currency'] = 'UGX';
        } else {
            $data['course_fee'] = $configPlan['price'] ?? $plan->price ?? 0;
            $data['currency'] = $configPlan['currency'] ?? $plan->currency ?? 'UGX';
        }

        $data['plan_key'] = $plan->key;

        // Normalize phone on update as well
        $rawLocal = preg_replace('/\D+/', '', $data['phone'] ?? '');
        $countryRaw = $data['phone_country_code'] ?? $data['phone_country'] ?? null;
        $countryDigits = $countryRaw ? preg_replace('/\D+/', '', $countryRaw) : null;
        $countryDigits = $countryDigits ?: preg_replace('/\D+/', '', config('app.default_phone_country', '+256')) ?: '256';

        $data['phone'] = $rawLocal ?: null;
        $data['phone_country_code'] = $countryDigits;
        $data['phone_full'] = $rawLocal ? ('+' . ltrim($countryDigits, '+') . $rawLocal) : null;
        $data['phone_display'] = $rawLocal ? ('+' . ltrim($countryDigits, '+') . ' ' . $rawLocal) : null;
        $data['phone_dial'] = '+' . ltrim($countryDigits, '+');

        $student->update($data);

        return redirect()
            ->route('secretary.students.show', $student)
            ->with('status', 'Student updated successfully.');
    }

    /**
     * Delete a student.
     */
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()
            ->route('secretary.students.index')
            ->with('status', 'Student deleted successfully.');
    }

    /**
     * Verify student email token.
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

 public function search(Request $request)
    {
        $q = trim($request->query('q', ''));

        if ($q === '') {
            return response()->json([], 200);
        }

        $matches = Student::with('intake')
            ->where(function ($sub) use ($q) {
                $sub->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('student_id', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderBy('first_name')
            ->limit(10)
            ->get()
            ->map(function ($s) {
                $name = trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                $intake = optional($s->intake)->name ?? null;
                $phone = $s->phone_full ?? null; // uses accessor if available
                return [
                    'id' => $s->id,
                    'name' => $name ?: 'Unknown',
                    'intake' => $intake,
                    'email' => $s->email,
 'phone' => $phone,
                    'url' => route('secretary.students.show', $s->id),
                ];
            });

        return response()->json($matches->values(), 200);
    }
}



