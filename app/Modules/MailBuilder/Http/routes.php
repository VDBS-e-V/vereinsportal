<?php

use App\Modules\MailBuilder\Http\Controllers\MailAssetController;
use App\Modules\MailBuilder\Http\Controllers\MailDesignController;
use App\Modules\MailBuilder\Http\Controllers\SystemMailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:mail_system.manage'])->prefix('geschaeftsstelle/mail-builder')->name('mailbuilder.')->group(function (): void {
    Route::get('/systemmails', [SystemMailController::class, 'index'])->name('system.index');
    Route::get('/systemmails/{key}', [SystemMailController::class, 'edit'])->where('key', '[a-z_]+\.[a-z_]+')->name('system.edit');
    Route::put('/systemmails/{key}', [SystemMailController::class, 'update'])->where('key', '[a-z_]+\.[a-z_]+')->middleware('throttle:30,10')->name('system.update');
    Route::post('/systemmails/{key}/zuruecksetzen', [SystemMailController::class, 'reset'])->where('key', '[a-z_]+\.[a-z_]+')->middleware('throttle:20,10')->name('system.reset');
    Route::post('/systemmails/{key}/vorschau', [SystemMailController::class, 'preview'])->where('key', '[a-z_]+\.[a-z_]+')->middleware('throttle:240,1')->name('system.preview');
    Route::post('/systemmails/{key}/testmail', [SystemMailController::class, 'test'])->where('key', '[a-z_]+\.[a-z_]+')->middleware('throttle:10,10')->name('system.test');
    Route::post('/systemmails/{key}/stand/{version}', [SystemMailController::class, 'restore'])->where('key', '[a-z_]+\.[a-z_]+')->whereNumber('version')->middleware('throttle:20,10')->name('system.restore');

    Route::get('/design', [MailDesignController::class, 'edit'])->name('design.edit');
    Route::put('/design', [MailDesignController::class, 'update'])->middleware('throttle:30,10')->name('design.update');
    Route::post('/design/vorschau', [MailDesignController::class, 'preview'])->middleware('throttle:240,1')->name('design.preview');

    Route::get('/bilder', [MailAssetController::class, 'index'])->name('assets.index');
    Route::post('/bilder', [MailAssetController::class, 'store'])->middleware('throttle:20,10')->name('assets.store');
    Route::delete('/bilder/{asset}', [MailAssetController::class, 'destroy'])->whereNumber('asset')->middleware('throttle:20,10')->name('assets.destroy');
});
