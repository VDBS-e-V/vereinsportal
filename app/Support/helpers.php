<?php

use App\Modules\DesignLibrary\Support\PlaceholderImages;

if (! function_exists('placeholder')) {
    /**
     * URL eines Platzhalterbildes, z. B. placeholder('hero') oder placeholder('teaser', 2).
     * Konvention: public/images/placeholder/<art>-<nn>.<endung>.
     */
    function placeholder(string $kind, int $n = 1): string
    {
        return PlaceholderImages::make()->url($kind, $n);
    }
}

if (! function_exists('rich_text')) {
    /** Formatierter Text (Editor-HTML oder älterer Markdown-Text) als sicheres HTML zur Ausgabe mit {!! !!}. */
    function rich_text(?string $value): \Illuminate\Support\HtmlString
    {
        return new \Illuminate\Support\HtmlString(\App\Support\Html\RichText::render($value));
    }
}
