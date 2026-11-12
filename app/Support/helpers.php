<?php

use App\Modules\DesignLibrary\Support\PlaceholderImages;
use App\Support\Html\RichText;
use Illuminate\Support\HtmlString;

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
    function rich_text(?string $value): HtmlString
    {
        return new HtmlString(RichText::render($value));
    }
}
