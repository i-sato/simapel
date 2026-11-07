<?php

use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeImportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SopProvisionController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TrainingAssignmentController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\WorkTeamController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware(['auth', 'password.change-required'])->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/account/password', [PasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('account.password.update');

    // Tim kerja: terbuka untuk semua pegawai (read-only).
    Route::get('/work-teams', [WorkTeamController::class, 'index'])->name('work-teams.index');
    Route::get('/work-teams/{team}', [WorkTeamController::class, 'show'])->name('work-teams.show');

    // Pelatihan: semua pegawai melihat; hak ubah/finalisasi diperiksa gate dan use case.
    Route::get('/trainings', [TrainingController::class, 'index'])->name('trainings.index');
    Route::get('/trainings/create', [TrainingController::class, 'create'])->middleware('can:create-training')->name('trainings.create');
    Route::post('/trainings', [TrainingController::class, 'store'])->middleware('can:create-training')->name('trainings.store');
    Route::get('/trainings/{training}', [TrainingController::class, 'show'])->name('trainings.show');
    Route::get('/trainings/{training}/edit', [TrainingController::class, 'edit'])->middleware('can:update-training,training')->name('trainings.edit');
    Route::put('/trainings/{training}', [TrainingController::class, 'update'])->middleware('can:update-training,training')->name('trainings.update');
    Route::get('/trainings/{training}/assignments', [TrainingAssignmentController::class, 'edit'])->middleware('can:manage-training-assignments,training')->name('trainings.assignments.edit');
    Route::put('/trainings/{training}/assignments', [TrainingAssignmentController::class, 'update'])->middleware('can:manage-training-assignments,training')->name('trainings.assignments.update');
    Route::post('/trainings/{training}/finalize', [TrainingController::class, 'finalize'])->middleware('can:finalize-training,training')->name('trainings.finalize');

    // Khusus Super Admin.
    Route::middleware('can:manage-organization')->group(function (): void {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/import', [EmployeeImportController::class, 'create'])->name('employees.import.create');
        Route::post('/employees/import', [EmployeeImportController::class, 'store'])->name('employees.import.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::post('/employees/{employee}/activate', [EmployeeController::class, 'activate'])->name('employees.activate');
        Route::post('/employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])->name('employees.deactivate');
        Route::post('/employees/{employee}/password', [EmployeeController::class, 'resetPassword'])->name('employees.password');

        Route::post('/work-teams/{team}/members', [TeamMemberController::class, 'store'])->name('work-teams.members.store');
        Route::put('/work-teams/{team}/members/{employee}', [TeamMemberController::class, 'update'])->name('work-teams.members.update');
        Route::delete('/work-teams/{team}/members/{employee}', [TeamMemberController::class, 'destroy'])->name('work-teams.members.destroy');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/global', [SettingsController::class, 'updateGlobal'])->name('settings.global.update');
        Route::put('/settings/teams/{team}/guide', [SettingsController::class, 'updateTeamGuide'])->name('settings.team-guide.update');

        Route::get('/settings/sop-provisions/create', [SopProvisionController::class, 'create'])->name('sop-provisions.create');
        Route::post('/settings/sop-provisions', [SopProvisionController::class, 'store'])->name('sop-provisions.store');
        Route::post('/settings/sop-provisions/copy', [SopProvisionController::class, 'copy'])->name('sop-provisions.copy');
        Route::get('/settings/sop-provisions/{provision}/edit', [SopProvisionController::class, 'edit'])->name('sop-provisions.edit');
        Route::put('/settings/sop-provisions/{provision}', [SopProvisionController::class, 'update'])->name('sop-provisions.update');
        Route::delete('/settings/sop-provisions/{provision}', [SopProvisionController::class, 'destroy'])->name('sop-provisions.destroy');
        Route::post('/settings/sop-provisions/{provision}/activate', [SopProvisionController::class, 'activate'])->name('sop-provisions.activate');
    });
});
