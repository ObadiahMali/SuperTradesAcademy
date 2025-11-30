<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

// Secretary Controllers
use App\Http\Controllers\Secretary\DashboardController as SecretaryDashboardController;
use App\Http\Controllers\Secretary\StudentController;
use App\Http\Controllers\Secretary\IntakeController;
use App\Http\Controllers\Secretary\PaymentController;
use App\Http\Controllers\Secretary\ExpenseController;

// Administrator Controllers
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ReportController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Role-Redirect Dashboard
|--------------------------------------------------------------------------
|
| Redirect users to the appropriate dashboard based on role.
|
*/

Route::get('/dashboard', function () {

    if (! auth()->check()) {
        return redirect()->route('login');
    }

    // Redirect Administrator → admin.dashboard
    if (auth()->user()->hasRole('administrator')) {
        return redirect()->route('admin.dashboard');
    }

    // Redirect Secretary → secretary.dashboard
    if (auth()->user()->hasRole('secretary')) {
        return redirect()->route('secretary.dashboard');
    }

    // If user has no valid role
    abort(403, 'No dashboard available for your role.');

})->middleware(['auth'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profile (Laravel Breeze)
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
|
| Consolidated secretary group. All secretary routes are declared once here,
| including resource controllers and extra action routes (receipts, toggle).
|
*/

Route::prefix('secretary')
    ->name('secretary.')
    ->middleware(['auth'])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [SecretaryDashboardController::class, 'index'])->name('dashboard');

        // Resource controllers
        Route::resource('students', StudentController::class);
        Route::resource('intakes', IntakeController::class);
        Route::resource('payments', PaymentController::class);
        Route::resource('expenses', ExpenseController::class);

        // Extra action routes that complement the resources
        Route::patch('expenses/{expense}/toggle-paid', [ExpenseController::class, 'togglePaid'])
            ->name('expenses.togglePaid');

        // Payment receipt routes (HTML and PDF)
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])
            ->name('payments.receipt');

        Route::get('payments/{payment}/receipt/pdf', [PaymentController::class, 'receiptPdf'])
            ->name('payments.receiptPdf');
    });

/*
|--------------------------------------------------------------------------
| Administrator Routes
|--------------------------------------------------------------------------
|
| Admin routes grouped under /admin with name prefix admin.
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('employees', EmployeeController::class);
        Route::resource('reports', ReportController::class);
    });

/*
|--------------------------------------------------------------------------
| Auth Routes (Breeze)
|--------------------------------------------------------------------------
*/

// routes/web.php (inside admin group)
Route::resource('plans', \App\Http\Controllers\Admin\PlanController::class)
    ->only(['index','edit','update'])
    ->names([
        'index' => 'admin.plans.index',
        'edit'  => 'admin.plans.edit',
        'update'=> 'admin.plans.update',
    ]);

// Payment-specific receipt (accepts Payment $payment)
Route::get('secretary/payments/{payment}/receipt-payment', [\App\Http\Controllers\Secretary\PaymentController::class, 'receiptForPayment'])
    ->name('secretary.payments.receipt.payment');

// Existing student receipt route (keeps working as-is)
Route::get('secretary/students/{student}/receipt', [\App\Http\Controllers\Secretary\PaymentController::class, 'receipt'])
    ->name('secretary.payments.receipt');

    

    Route::get('secretary/students/verify-email', [StudentController::class, 'verifyEmail'])
    ->name('secretary.students.verifyEmail');
require __DIR__.'/auth.php';