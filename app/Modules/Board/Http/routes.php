<?php

use App\Modules\Board\Http\Controllers\BoardFeedController;
use App\Modules\Board\Http\Controllers\BoardMeetingController;
use Illuminate\Support\Facades\Route;

/*
 * Bereich Vorstand: /vorstand/sitzungen (Sitzungen, Tagesordnung, Anwesenheit, Protokoll; Berechtigung board.meetings, nur Vorstand).
 * Der Kalender-Feed /vorstand/kalender/{schlüssel}.ics ist ohne Anmeldung abrufbar, weil Kalenderprogramme sich nicht anmelden;
 * der geheime Schlüssel in der Adresse schützt ihn und lässt sich im Portal erneuern.
 */
Route::middleware(['web', 'auth', 'can:board.meetings'])->prefix('vorstand/sitzungen')->name('board.meetings.')->group(function (): void {
    Route::get('/', [BoardMeetingController::class, 'index'])->name('index');
    Route::get('/neu', [BoardMeetingController::class, 'create'])->name('create');
    Route::post('/', [BoardMeetingController::class, 'store'])->name('store');
    Route::put('/kalender', [BoardMeetingController::class, 'updateCalendar'])->middleware('throttle:20,10')->name('calendar');
    Route::post('/kalender/erneuern', [BoardMeetingController::class, 'renewFeed'])->middleware('throttle:10,10')->name('feed.renew');
    Route::post('/loeschen', [BoardMeetingController::class, 'destroyMany'])->name('destroy-many');
    Route::get('/{meeting}', [BoardMeetingController::class, 'show'])->whereNumber('meeting')->name('show');
    Route::get('/{meeting}/bearbeiten', [BoardMeetingController::class, 'edit'])->whereNumber('meeting')->name('edit');
    Route::put('/{meeting}', [BoardMeetingController::class, 'update'])->whereNumber('meeting')->name('update');
    Route::put('/einladungen', [BoardMeetingController::class, 'updateInvitations'])->name('invitations.toggle');
    Route::post('/{meeting}/einladen', [BoardMeetingController::class, 'invite'])->whereNumber('meeting')->middleware('throttle:10,10')->name('invite');
    Route::post('/{meeting}/gaeste', [BoardMeetingController::class, 'storeGuest'])->whereNumber('meeting')->middleware('throttle:30,10')->name('guests.store');
    Route::post('/{meeting}/gaeste/loeschen', [BoardMeetingController::class, 'destroyGuests'])->whereNumber('meeting')->name('guests.destroy');
    Route::post('/{meeting}/gaeste/einladen', [BoardMeetingController::class, 'inviteGuests'])->whereNumber('meeting')->middleware('throttle:10,10')->name('guests.invite');
    Route::post('/{meeting}/dokumente', [BoardMeetingController::class, 'storeDocuments'])->whereNumber('meeting')->middleware('throttle:30,10')->name('documents.store');
    Route::post('/{meeting}/dokumente/loeschen', [BoardMeetingController::class, 'destroyDocuments'])->whereNumber('meeting')->name('documents.destroy');
    Route::get('/{meeting}/dokumente/{document}', [BoardMeetingController::class, 'download'])->whereNumber(['meeting', 'document'])->name('documents.download');
    Route::post('/{meeting}/genehmigen', [BoardMeetingController::class, 'approve'])->whereNumber('meeting')->name('approve');
    Route::delete('/{meeting}', [BoardMeetingController::class, 'destroy'])->whereNumber('meeting')->name('destroy');
});

Route::middleware(['web', 'throttle:60,1'])->get('/vorstand/kalender/{token}.ics', BoardFeedController::class)->where('token', '[A-Za-z0-9]{40}')->name('board.feed');
