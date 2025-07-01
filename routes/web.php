<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CronController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Gateway\PaymentController;

// Clear cache
Route::get('/clear', function () {
    Artisan::call('optimize:clear');
});

// Cron Route
Route::get('cron', [CronController::class, 'cron'])->name('cron');

// User Support Ticket Routes
Route::prefix('ticket')->name('ticket.')->group(function () {
    Route::get('/', [TicketController::class, 'supportTicket'])->name('index');
    Route::get('new', [TicketController::class, 'openSupportTicket'])->name('open');
    Route::post('create', [TicketController::class, 'storeSupportTicket'])->name('store');
    Route::get('view/{ticket}', [TicketController::class, 'viewTicket'])->name('view');
    Route::post('reply/{ticket}', [TicketController::class, 'replyTicket'])->name('reply');
    Route::post('close/{ticket}', [TicketController::class, 'closeTicket'])->name('close');
    Route::get('download/{ticket}', [TicketController::class, 'ticketDownload'])->name('download');
});

// Payment Deposit Confirmation Route
Route::get('app/deposit/confirm/{hash}', [PaymentController::class, 'appDepositConfirm'])->name('deposit.app.confirm');

// General Site Routes
Route::controller(SiteController::class)->group(function () {
    Route::get('loan', 'loans')->name('loan');
    Route::get('placeholder-image/{size}', 'placeholderImage')->name('placeholder.image');
    Route::post('device/token', 'storeDeviceToken')->name('store.device.token');
    Route::get('/', 'index')->name('home');
});