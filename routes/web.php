<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventParticipantController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ParticipantTypeController;

// Public Registration Routes
use App\Http\Controllers\Public\EventRegistrationController;

Route::get('/', [App\Http\Controllers\Public\PageController::class, 'home'])->name('home');
Route::get('/events', [App\Http\Controllers\Public\PageController::class, 'events'])->name('events.index');
Route::get('/portal/check', [App\Http\Controllers\Public\PageController::class, 'checkPortal'])->name('portal.check');

// Temporary route for Shared Hosting without Terminal
Route::get('/run-cmd', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return 'Storage link created & Migration success!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

use App\Http\Controllers\Public\RegistrationController;
Route::get('/register-account', [RegistrationController::class, 'showRegistrationForm'])->name('register');
Route::post('/register-account', [RegistrationController::class, 'register'])->name('register.submit');

Route::get('/register/event/{event:slug}', [EventRegistrationController::class, 'show'])
    ->name('event.register');

Route::post('/register/event/{event:slug}/check', [EventRegistrationController::class, 'checkParticipant'])
    ->name('event.register.check');

Route::post('/register/event/{event:slug}', [EventRegistrationController::class, 'store'])
    ->name('event.register.store');

Route::get('/ticket/{event:slug}/{qrToken}', [EventRegistrationController::class, 'ticket'])
    ->name('event.ticket');

Route::get('/events/{event:slug}/attendance-success/{qrToken}', [EventRegistrationController::class, 'attendanceSuccess'])
    ->name('event.attendance.success');

Route::post('/check-tickets', [EventRegistrationController::class, 'checkTickets'])
    ->name('event.check_tickets');

Route::get('/id-card/{event:slug}/{qrToken}', [EventRegistrationController::class, 'idCard'])
    ->name('event.id_card');

Route::get('/id-card/image/{event:slug}/{qrToken}', [EventRegistrationController::class, 'idCardImage'])
    ->name('event.id_card_image');

Route::post('/id-card/generate/{event:slug}/{qrToken}', [EventRegistrationController::class, 'generateIdCard'])
    ->name('event.id_card_generate');

// Certificate Public Routes
use App\Http\Controllers\Public\CertificateController;
Route::controller(CertificateController::class)->prefix('certificates')->name('certificates.')->group(function() {
    Route::get('/', 'index')->name('index');
    Route::post('/search', 'search')->name('search');
    Route::get('/{event:slug}/{qrToken}', 'show')->name('show');
    Route::get('/{event:slug}/{qrToken}/download', 'download')->name('download');
    Route::post('/{event:slug}/{qrToken}/report', 'report')->name('report');
});

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login',[AuthController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.submit');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

});

Route::prefix('admin')->middleware('admin.auth')->name('admin.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::post('/dashboard/run-queue', [DashboardController::class, 'runQueue'])
        ->name('dashboard.run-queue');

    Route::resource('events', EventController::class)->except(['show']);
    
    Route::get('events/{event}/print-qr', [EventController::class, 'printQr'])
        ->name('events.print-qr');

    Route::patch('events/{event}/set-status', [EventController::class, 'setStatus'])
        ->name('events.set-status');

    Route::get('events/{event}/participants', [EventParticipantController::class, 'index'])
            ->name('events.participants.index');
            
    Route::get('events/{event}/participants/print', [EventParticipantController::class, 'print'])
            ->name('events.participants.print');

    Route::get('events/{event}/participants/create', [EventParticipantController::class, 'create'])
        ->name('events.participants.create');

    Route::post('events/{event}/participants', [EventParticipantController::class, 'store'])
        ->name('events.participants.store');

    Route::get('events/{event}/participants/{eventParticipant}/edit', [EventParticipantController::class, 'edit'])
        ->name('events.participants.edit');

    Route::put('events/{event}/participants/{eventParticipant}', [EventParticipantController::class, 'update'])
        ->name('events.participants.update');

    Route::delete('events/{event}/participants/{eventParticipant}', [EventParticipantController::class, 'destroy'])
        ->name('events.participants.destroy');

    // Attendance Scan (Secure Kiosk)
    Route::get('/scan-attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'scan'])->name('attendance.scan');
    Route::post('/scan-attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'store'])->name('attendance.store');

    // Master Data Peserta
    Route::resource('participants', \App\Http\Controllers\Admin\ParticipantController::class)
        ->except(['create', 'edit', 'show']);

    // Reports
    Route::controller(\App\Http\Controllers\Admin\EventReportController::class)->group(function() {
        Route::get('events/{event}/report', 'show')->name('reports.show')->middleware('can:view,event');
        Route::get('events/{event}/report/export', 'export')->name('reports.export')->middleware('can:view,event');
        Route::get('events/{event}/report/print', 'print')->name('reports.print')->middleware('can:view,event');
    });

    // Participant Types
    Route::get('events/{event}/types', [ParticipantTypeController::class, 'index'])->name('events.types.index');
    Route::post('events/{event}/types', [ParticipantTypeController::class, 'store'])->name('events.types.store');
    Route::put('events/{event}/types/{type}', [ParticipantTypeController::class, 'update'])->name('events.types.update');
    Route::delete('events/{event}/types/{type}', [ParticipantTypeController::class, 'destroy'])->name('events.types.destroy');

    // Profile & Password (All Roles)
    Route::get('/profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'showPasswordForm'])->name('profile.password');
    Route::post('/profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Settings & User Management (Super Admin Only)
    Route::middleware('role:super_admin')->group(function() {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        
        // User Management
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['create', 'edit', 'show']);
        Route::post('users/{user}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('users.reset-password');

        // Transfer Ownership
        Route::post('events/{event}/transfer', [\App\Http\Controllers\Admin\EventController::class, 'transfer'])->name('events.transfer');
    });

    // ID Card Batch Operations
    Route::prefix('events/{event}/id-cards')->name('events.id-cards.')->group(function() {
        Route::get('/participants', [\App\Http\Controllers\Admin\IdCardController::class, 'getParticipants'])->name('get-participants');
        Route::get('/download-batch', [\App\Http\Controllers\Admin\IdCardController::class, 'downloadBatch'])->name('download-batch');
        Route::post('/generate-batch', [\App\Http\Controllers\Admin\IdCardController::class, 'generateBatch'])->name('generate-batch');
        Route::post('/generate-single/{qrToken}', [\App\Http\Controllers\Admin\IdCardController::class, 'generateSingle'])->name('generate-single');
        
        // ID Card Template per Event
        Route::get('/template', [\App\Http\Controllers\Admin\IdCardTemplateController::class, 'show'])->name('template.show');
        Route::post('/template', [\App\Http\Controllers\Admin\IdCardTemplateController::class, 'update'])->name('template.update');
    });

    // Certificate Admin Routes
    Route::get('/certificates/events', [\App\Http\Controllers\Admin\CertificateController::class, 'listEvents'])->name('certificates.list'); 

    Route::prefix('events/{event}/certificates')->name('events.certificates.')->controller(\App\Http\Controllers\Admin\CertificateController::class)->group(function() {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'update')->name('update');
        Route::post('/signature', 'storeSignature')->name('signature.store');
        Route::delete('/signature/{signature}', 'destroySignature')->name('signature.destroy');
        Route::post('/report/{report}/resolve', 'resolveReport')->name('report.resolve');
    });

});



