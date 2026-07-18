<?php

use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\AdminRegistrationController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminTicketScannerController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminWebsiteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ParticipantProfileController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TicketVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'index'])->name('events.index');
Route::get('/profil-pdug', [EventController::class, 'profile'])->name('profile.pdug');
Route::get('/aktor', [EventController::class, 'actors'])->name('actors.index');
Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'auth.throttle:public-login'])->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->middleware('guest')->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware(['guest', 'auth.throttle:participant-register'])->name('register.store');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware(['guest', 'password.reset.throttle'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest')->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->middleware('guest')->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware(['guest', 'auth.throttle:admin-login'])->name('admin.login.store');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/ticket/verify/{token}', TicketVerificationController::class)
    ->middleware('throttle:30,1')
    ->where('token', '[A-Za-z0-9]{40,64}')
    ->name('tickets.verify');
Route::middleware('auth')->group(function (): void {
    Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/tickets', [RegistrationController::class, 'tickets'])->name('tickets.index');
    Route::get('/profil-peserta', [ParticipantProfileController::class, 'show'])->name('participant.profile');
    Route::patch('/profil-peserta/password', [ParticipantProfileController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('participant.password.update');
    Route::get('/activity', [ParticipantProfileController::class, 'activity'])->name('participant.activity');
    Route::delete('/profil-peserta/registrasi/{registration}', [ParticipantProfileController::class, 'cancel'])->name('participant.registrations.cancel');
    Route::post('/events/{event:slug}/registrations', [RegistrationController::class, 'store'])->name('registrations.store');
    Route::get('/registrations/{registration}', [RegistrationController::class, 'show'])->name('registrations.show');
    Route::post('/registrations/{registration}/payment/initialize', [RegistrationController::class, 'initializePayment'])->name('registrations.payment.initialize');
    Route::post('/registrations/{registration}/payment/status', [RegistrationController::class, 'refreshPaymentStatus'])->name('registrations.payment.status');
    Route::get('/registrations/{registration}/payment/state', [RegistrationController::class, 'paymentState'])->name('registrations.payment.state');
    Route::post('/registrations/{registration}/payment/retry', [RegistrationController::class, 'retryPayment'])->name('registrations.payment.retry');
});
Route::post('/payments/midtrans/notification', PaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('payments.midtrans.notification');
Route::middleware(['admin', 'admin.no-cache'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('ticket-scanner', [AdminTicketScannerController::class, 'index'])->name('tickets.scanner');
    Route::post('tickets/check-in', [AdminTicketScannerController::class, 'checkIn'])->name('tickets.checkin');
    Route::get('dashboard', fn () => redirect('/admin'))->name('dashboard');
    Route::resource('legacy/events', AdminEventController::class)->except(['show'])->names('events');
    Route::get('legacy/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('legacy/registrations', [AdminRegistrationController::class, 'index'])->name('registrations.index');
    Route::get('check-in/{registrationCode}', [AdminRegistrationController::class, 'showCheckIn'])->name('registrations.checkin.show');
    Route::patch('registrations/{registration}/status', [AdminRegistrationController::class, 'updateStatus'])->name('registrations.status');
    Route::patch('registrations/{registration}/check-in', [AdminRegistrationController::class, 'checkIn'])->name('registrations.checkin');
    Route::delete('registrations/{registration}', [AdminRegistrationController::class, 'destroy'])->name('registrations.destroy');
    Route::get('legacy/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('laporan/pdf', [AdminReportController::class, 'pdf'])->name('reports.pdf');
    Route::get('legacy/website', [AdminWebsiteController::class, 'edit'])->name('website.edit');
    Route::patch('pengaturan-website', [AdminWebsiteController::class, 'update'])->name('website.update');
    Route::patch('website', [AdminWebsiteController::class, 'update'])->name('website.update.legacy');
});

Route::middleware(['admin', 'admin.no-cache'])->group(function (): void {
    Route::get('/admin/pusat-administrasi', fn () => redirect('/admin'));
    Route::get('/admin/reports', fn () => redirect('/admin/laporan'));
    Route::get('/admin/website', fn () => redirect('/admin/pengaturan-website'));
});

Route::get('/filament-admin/{path?}', function (?string $path = null) {
    return redirect('/admin'.($path ? '/'.$path : ''));
})->middleware(['admin', 'admin.no-cache'])->where('path', '.*');
