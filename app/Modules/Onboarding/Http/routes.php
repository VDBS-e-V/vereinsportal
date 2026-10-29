<?php

use App\Modules\Onboarding\Http\Controllers\MemberOnboardingController;
use App\Modules\Onboarding\Http\Controllers\OnboardingDocumentController;
use App\Modules\Onboarding\Http\Controllers\TeamOnboardingAdminController;
use App\Modules\Onboarding\Http\Controllers\TeamSelfController;
use Illuminate\Support\Facades\Route;

/*
 * Onboarding in zwei getrennten Abläufen.
 *  Mitglieder (Bereich Vorstand): /vorstand/willkommenspaket, Dokumente Satzung und Willkommensbrief; memberships.view und memberships.manage.
 *  Teamende, Verwaltung (Bereich Koordination): /koordination/teamende; team.view und team.manage.
 *  Teamende selbst (Bereich Teamende): /teamende/einarbeitung; team.onboarding.
 * Berechtigungen: Support\Capabilities im Modul AccessGovernance.
 */
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::prefix('vorstand')->group(function (): void {
        Route::get('/willkommenspaket', [MemberOnboardingController::class, 'index'])->middleware('can:memberships.view')->name('onboarding.member');
        Route::post('/willkommenspaket', [MemberOnboardingController::class, 'upload'])->middleware('can:memberships.manage')->name('onboarding.member.upload');
        Route::post('/mitgliedschaften/{membership}/willkommen', [MemberOnboardingController::class, 'send'])->whereNumber('membership')->middleware(['can:memberships.manage', 'throttle:10,10'])->name('membership.welcome');
    });

    Route::prefix('koordination/teamende')->name('team-onboarding.')->group(function (): void {
        Route::middleware('can:team.manage')->group(function (): void {
            Route::post('/dokumente', [TeamOnboardingAdminController::class, 'upload'])->name('documents.upload');
            Route::post('/{user}/schritte/{step}', [TeamOnboardingAdminController::class, 'mark'])->whereNumber('user')->name('mark');
            Route::delete('/{user}/schritte/{step}', [TeamOnboardingAdminController::class, 'reopen'])->whereNumber('user')->name('reopen');
        });

        Route::middleware('can:team.view')->group(function (): void {
            Route::get('/', [TeamOnboardingAdminController::class, 'index'])->name('index');
            Route::get('/dokumente', [TeamOnboardingAdminController::class, 'documents'])->name('documents');
            Route::get('/{user}', [TeamOnboardingAdminController::class, 'show'])->whereNumber('user')->name('show');
        });
    });

    Route::prefix('teamende/einarbeitung')->name('team-self.')->middleware('can:team.onboarding')->group(function (): void {
        Route::get('/', [TeamSelfController::class, 'index'])->name('index');
        Route::post('/{step}/bestaetigen', [TeamSelfController::class, 'confirm'])->middleware('throttle:20,10')->name('confirm');
    });

    Route::get('/onboarding/dokumente/{document}', [OnboardingDocumentController::class, 'download'])->whereNumber('document')->name('onboarding.documents.download');
});
