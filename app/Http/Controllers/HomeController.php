<?php

namespace App\Http\Controllers;

use App\Modules\Articles\Models\Article;
use Illuminate\Contracts\View\View;

/** Öffentliche Startseite: zeigt bis zu drei empfohlene Artikel (markierte zuerst, dann die neuesten). */
class HomeController extends Controller
{
    private const ARTICLES = 3;

    public function index(): View
    {
        $articles = Article::query()->visible()->orderByDesc('featured')->orderByDesc('published_at')->orderByDesc('id')->limit(self::ARTICLES)->get();

        return view('portal.home', ['articles' => $articles]);
    }
}
