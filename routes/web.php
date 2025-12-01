<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

/*
 * Secretary Controllers
 */
use App\Http\Controllers\Secretary\DashboardController as SecretaryDashboardController;
use App\Http\Controllers\Secretary\StudentController;
use App\Http\Controllers\Secretary\IntakeController;
use App\Http\Controllers\Secretary\PaymentController;
use App\Http\Controllers\Secretary\ExpenseController;

/*
 * Administrator Controllers
 */
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
*/
Route::get('/dashboard', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    if (auth()->user()->hasRole('administrator')) {
        return redirect()->route('admin.dashboard');
    }

    if (auth()->user()->hasRole('secretary')) {
        return redirect()->route('secretary.dashboard');
    }

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

        // Extra action routes
        Route::patch('expenses/{expense}/toggle-paid', [ExpenseController::class, 'togglePaid'])
            ->name('expenses.togglePaid');

        // Payment receipt routes
        // - payment-specific receipt (accepts Payment $payment)
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receiptForPayment'])
            ->name('payments.receipt.payment');

        Route::get('payments/{payment}/receipt/pdf', [PaymentController::class, 'receiptPdf'])
            ->name('payments.receipt.pdf');

        // Student receipt (accepts Student $student)
        Route::get('students/{student}/receipt', [PaymentController::class, 'receipt'])
            ->name('students.receipt');

        // Verify email
        Route::get('students/verify-email', [StudentController::class, 'verifyEmail'])
            ->name('students.verifyEmail');
    });

/*
|--------------------------------------------------------------------------
| Administrator Routes
|--------------------------------------------------------------------------
|
| Register explicit admin report routes first to avoid being captured by
| resource/wildcard routes.
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Reports: explicit export and index routes first
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        // If you still want resourceful routes for reports (show, destroy, etc),
        // register them after the explicit routes and limit to the actions you need.
        Route::resource('employees', EmployeeController::class);
        Route::resource('reports', ReportController::class)
            ->except(['create', 'store', 'edit', 'update', 'index']); // index/export handled above
    });

/*
|--------------------------------------------------------------------------
| Admin plans (example)
|--------------------------------------------------------------------------
*/
Route::resource('plans', \App\Http\Controllers\Admin\PlanController::class)
    ->only(['index','edit','update'])
    ->names([
        'index' => 'admin.plans.index',
        'edit'  => 'admin.plans.edit',
        'update'=> 'admin.plans.update',
    ]);

/*
|--------------------------------------------------------------------------
| Backwards-compatible named routes (if other views expect these names)
|--------------------------------------------------------------------------
*/
Route::get('secretary/payments/{payment}/receipt-payment', [\App\Http\Controllers\Secretary\PaymentController::class, 'receiptForPayment'])
    ->name('secretary.payments.receipt.payment');

Route::get('secretary/students/{student}/receipt', [\App\Http\Controllers\Secretary\PaymentController::class, 'receipt'])
    ->name('secretary.payments.receipt');

/*
|--------------------------------------------------------------------------
| Auth routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';