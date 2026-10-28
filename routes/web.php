<?php

use Illuminate\Support\Facades\Route;

/*
 * Login/Logout liegen im Access-Modul, die Design-Bibliothek unter /design.
 * Weitere Bereiche kommen modulweise dazu (Onboarding, Offboarding, ...).
 *
 * Siehe Konzept: https://claude.ai/artifact/6T9qoinqTT6m6UoQMXptKs
 */
// Startseite: öffentlich, zeigt Gästen den Zugang und Angemeldeten ihre Bereiche.
Route::view('/', 'portal.home')->middleware('web')->name('home');
