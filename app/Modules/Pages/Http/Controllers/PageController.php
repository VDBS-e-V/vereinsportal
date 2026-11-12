<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pages\Enums\PageKey;
use App\Modules\Pages\Models\Page;
use Illuminate\Contracts\View\View;

/** Öffentliche Rechtstext-Seiten (Bereich Start). */
class PageController extends Controller
{
    public function show(string $page): View
    {
        return view('pages.show', ['page' => Page::for(PageKey::from($page))]);
    }
}
