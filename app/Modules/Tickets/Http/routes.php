<?php

use App\Modules\Tickets\Http\Controllers\TicketAdminController;
use App\Modules\Tickets\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/*
 * Tickets: Jede angemeldete Person legt Tickets an und schreibt im Ticket mit dem Team (/tickets, Bereich Konto).
 * Bearbeitende (Berechtigung tickets.handle) sehen und bearbeiten alle Tickets unter /verwaltung/tickets (Geschäftsstelle).
 */
Route::middleware(['web', 'auth'])->prefix('tickets')->name('support.')->group(function (): void {
    Route::get('/', [TicketController::class, 'index'])->name('mine');
    Route::get('/neu', [TicketController::class, 'create'])->name('create');
    Route::post('/', [TicketController::class, 'store'])->middleware('throttle:10,10')->name('store');
    Route::get('/{ticket}', [TicketController::class, 'show'])->whereNumber('ticket')->name('show');
    Route::post('/{ticket}/nachrichten', [TicketController::class, 'message'])->whereNumber('ticket')->middleware('throttle:60,10')->name('message');
    Route::post('/{ticket}/schliessen', [TicketController::class, 'close'])->whereNumber('ticket')->name('close');
});

Route::middleware(['web', 'auth', 'can:tickets.handle'])->prefix('verwaltung/tickets')->name('tickets-admin.')->group(function (): void {
    Route::get('/', [TicketAdminController::class, 'index'])->name('index');
    Route::post('/aktion', [TicketAdminController::class, 'bulk'])->middleware('throttle:60,10')->name('bulk');
    Route::put('/{ticket}', [TicketAdminController::class, 'update'])->whereNumber('ticket')->middleware('throttle:120,10')->name('update');
});
