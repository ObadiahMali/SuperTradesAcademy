<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\ProfileController;
use App\Models\Student;
use App\Mail\WelcomeUserMail;

/*
|--------------------------------------------------------------------------
| Secretary Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Secretary\DashboardController as SecretaryDashboardController;
use App\Http\Controllers\Secretary\StudentController;
use App\Http\Controllers\Secretary\IntakeController;
use App\Http\Controllers\Secretary\PaymentController;

use App\Http\Controllers\Secretary\ExpenseController;

/*
|--------------------------------------------------------------------------
| Administrator Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PlanController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('welcome'));

/*
|--------------------------------------------------------------------------
| Role-based Dashboard Redirect
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    $role = strtolower(trim((string) $user->role));

    return match ($role) {
        'administrator' => redirect()->route('admin.dashboard'),
        'secretary'     => redirect()->route('secretary.dashboard'),
        default         => tap(
            redirect()->route('admin.users.index')
                ->with('info', 'No dashboard for your role. Redirected to users list.'),
            fn () => Log::warning('Unknown role on dashboard redirect', [
                'user_id' => $user->id,
                'role' => $user->role,
            ])
        ),
    };
})->middleware('auth')->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profile (Breeze)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Secretary Routes
|--------------------------------------------------------------------------
*/
Route::prefix('secretary')
    ->name('secretary.')
    ->middleware('auth')
    ->group(function () {

        Route::get('/dashboard', [SecretaryDashboardController::class, 'index'])
            ->name('dashboard');

        Route::resource('students', StudentController::class);
        Route::resource('intakes', IntakeController::class);
        Route::resource('payments', PaymentController::class);
        Route::resource('expenses', ExpenseController::class);

        Route::patch('expenses/{expense}/toggle-paid',
            [ExpenseController::class, 'togglePaid']
        )->name('expenses.togglePaid');

        Route::get('payments/{payment}/receipt',
            [PaymentController::class, 'receiptForPayment']
        )->name('payments.receipt');

        Route::get('payments/{payment}/receipt/pdf',
            [PaymentController::class, 'receiptPdf']
        )->name('payments.receipt.pdf');

        Route::get('students/verify-email',
            [StudentController::class, 'verifyEmail']
        )->name('students.verifyEmail');
    });

    

/*
|--------------------------------------------------------------------------
| Administrator Routes (FULLY PROTECTED)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('reports/export', [ReportController::class, 'export'])
            ->name('reports.export');

        Route::get('reports', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::resource('reports', ReportController::class)
            ->except(['create', 'store', 'edit', 'update', 'index']);

        Route::resource('employees', EmployeeController::class);

        /*
         |--------------------------------------------------------------------------
         | Users (ADMIN ONLY – single source of truth)
         |--------------------------------------------------------------------------
         */
        Route::resource('users', UserController::class);

        /*
         |--------------------------------------------------------------------------
         | Plans (controller enforces ability)
         |--------------------------------------------------------------------------
         */
        Route::resource('plans', PlanController::class)
            ->only(['index', 'edit', 'update']);
    });

/*
|--------------------------------------------------------------------------
| Backwards-Compatible Secretary Endpoints
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('secretary/students',
        [StudentController::class, 'index']
    )->name('secretary.students.index');

    Route::get('secretary/students/search',
        [StudentController::class, 'search']
    )->name('secretary.students.search');
});

/*
|--------------------------------------------------------------------------
| Debug Mail (Auth Only)
|--------------------------------------------------------------------------
*/
Route::get('/debug-mail/student/{id}', function ($id) {
    $student = Student::findOrFail($id);

    try {
        Mail::to($student->email)->send(new WelcomeUserMail($student));
        Log::info('Debug mail sent', ['student_id' => $student->id]);
        return 'Debug mail sent';
    } catch (\Throwable $e) {
        Log::error('Debug mail failed', ['error' => $e->getMessage()]);
        return 'Debug mail failed';
    }
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
