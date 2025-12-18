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

        // Payment receipt routes (payment-level receipts)
        // Simple alias name: secretary.payments.receipt
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receiptForPayment'])
            ->name('payments.receipt');

        // PDF version
        Route::get('payments/{payment}/receipt/pdf', [PaymentController::class, 'receiptPdf'])
            ->name('payments.receipt.pdf');

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

        // Resourceful routes
        Route::resource('employees', EmployeeController::class);
        Route::resource('reports', ReportController::class)
            ->except(['create', 'store', 'edit', 'update', 'index']); // index/export handled above
             Route::get('users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
    Route::post('users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    });

//     Route::middleware(['auth','is_admin'])->prefix('admin')->name('admin.')->group(function () {
//     Route::get('users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
//     Route::post('users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
// });

/*
|--------------------------------------------------------------------------
| Admin plans
|--------------------------------------------------------------------------
*/
Route::resource('plans', \App\Http\Controllers\Admin\PlanController::class)
    ->only(['index','edit','update'])
    ->names([
        'index' => 'admin.plans.index',
        'edit'  => 'admin.plans.edit',
        'update'=> 'admin.plans.update',
    ]);



    // paginated index (existing)
Route::get('secretary/students', [\App\Http\Controllers\Secretary\StudentController::class, 'index'])
    ->name('secretary.students.index');

// AJAX search endpoint
Route::get('secretary/students/search', [\App\Http\Controllers\Secretary\StudentController::class, 'search'])
    ->name('secretary.students.search');

/*
|--------------------------------------------------------------------------
| Auth routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';