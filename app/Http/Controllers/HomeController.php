<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/** Öffentliche Startseite: zeigt Gästen den Zugang und Angemeldeten ihre Bereiche. */
class HomeController extends Controller
{
    public function index(): View
    {
        return view('portal.home');
    }
}
