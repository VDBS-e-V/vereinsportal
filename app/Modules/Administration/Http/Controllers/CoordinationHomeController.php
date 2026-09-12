<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class CoordinationHomeController extends Controller
{
    public function __invoke(): View
    {
        return view('administration.coordination-home', [
            'breadcrumbs' => [],
        ]);
    }
}
