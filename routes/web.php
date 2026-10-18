<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HR;
use App\Http\Controllers\Me;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(Auth::check() ? 'dashboard' : 'login'));

// --- Authentication (hand-rolled) ---
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// --- Role-aware dashboard redirect ---
Route::get('dashboard', function () {
    return redirect()->route(Auth::user()->isAdmin() ? 'hr.dashboard' : 'me.dashboard');
})->middleware(['auth', 'active'])->name('dashboard');

// =========================== HR / Admin ===========================
Route::middleware(['auth', 'active', 'role:admin'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('dashboard', [HR\DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/metrics', [HR\DashboardController::class, 'metrics'])->name('dashboard.metrics');

    Route::post('employees/{employee}/toggle', [HR\EmployeeController::class, 'toggleActive'])->name('employees.toggle');
    Route::resource('employees', HR\EmployeeController::class);

    Route::resource('departments', HR\DepartmentController::class)->except('show');
    Route::resource('positions', HR\JobPositionController::class)->parameters(['positions' => 'position'])->except('show');

    Route::post('shifts/{shift}/assign', [HR\ShiftController::class, 'assign'])->name('shifts.assign');
    Route::resource('shifts', HR\ShiftController::class)->except('show');

    Route::get('attendance', [HR\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/{attendance}/edit', [HR\AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::put('attendance/{attendance}', [HR\AttendanceController::class, 'update'])->name('attendance.update');
    Route::get('attendance-reports/{period?}', [HR\AttendanceReportController::class, 'index'])->name('attendance.reports');

    Route::get('leave', [HR\LeaveRequestController::class, 'index'])->name('leave.index');
    Route::post('leave/{leaveRequest}/approve', [HR\LeaveRequestController::class, 'approve'])->name('leave.approve');
    Route::post('leave/{leaveRequest}/reject', [HR\LeaveRequestController::class, 'reject'])->name('leave.reject');
    Route::resource('leave-types', HR\LeaveTypeController::class)->parameters(['leave-types' => 'leaveType'])->except('show');

    Route::get('late-arrivals', [HR\LateArrivalController::class, 'index'])->name('late-arrivals.index');
    Route::post('late-arrivals/{report}/review', [HR\LateArrivalController::class, 'review'])->name('late-arrivals.review');

    Route::get('payroll', [HR\PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll-export', [HR\PayrollController::class, 'export'])->name('payroll.export');
    Route::post('payroll/generate', [HR\PayrollController::class, 'generate'])->name('payroll.generate');
    Route::get('payroll/{payroll}', [HR\PayrollController::class, 'show'])->name('payroll.show');
    Route::post('payroll/{payroll}/finalize', [HR\PayrollController::class, 'finalize'])->name('payroll.finalize');
    Route::post('payroll/{payroll}/pay', [HR\PayrollController::class, 'markPaid'])->name('payroll.pay');
    Route::get('payroll/{payroll}/payslip', [HR\PayrollController::class, 'payslip'])->name('payroll.payslip');

    Route::resource('salary-structures', HR\SalaryStructureController::class)
        ->parameters(['salary-structures' => 'salaryStructure'])->except(['show', 'destroy']);

    Route::get('audit', [HR\AuditLogController::class, 'index'])->name('audit.index');
});

// =========================== Employee self-service ===========================
Route::middleware(['auth', 'active'])->prefix('me')->name('me.')->group(function () {
    Route::get('dashboard', [Me\DashboardController::class, 'index'])->name('dashboard');

    Route::get('attendance', [Me\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/clock-in', [Me\AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('attendance/clock-out', [Me\AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

    Route::get('leave', [Me\LeaveController::class, 'index'])->name('leave.index');
    Route::get('leave/create', [Me\LeaveController::class, 'create'])->name('leave.create');
    Route::post('leave', [Me\LeaveController::class, 'store'])->name('leave.store');
    Route::post('leave/{leaveRequest}/cancel', [Me\LeaveController::class, 'cancel'])->name('leave.cancel');
    Route::get('leave-balances', [Me\LeaveController::class, 'balances'])->name('leave.balances');
    Route::get('leave-balance-lookup', [Me\LeaveController::class, 'balanceLookup'])->name('leave.balance-lookup');

    Route::get('late-arrivals/create', [Me\LateArrivalController::class, 'create'])->name('late-arrivals.create');
    Route::post('late-arrivals', [Me\LateArrivalController::class, 'store'])->name('late-arrivals.store');

    Route::get('payroll', [Me\PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/{payroll}', [Me\PayrollController::class, 'show'])->name('payroll.show');
    Route::get('payroll/{payroll}/payslip', [Me\PayrollController::class, 'payslip'])->name('payroll.payslip');

    Route::get('profile', [Me\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [Me\ProfileController::class, 'update'])->name('profile.update');
});
