@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Artikel', 'href' => route('articles.public.index')], ['label' => $article->title]];
@endphp

<x-layouts.app :title="$article->title" width="medium">
    <x-vdbs.layout.page-header :title="$article->title" :lead="$article->teaser" :breadcrumb="$crumbs" :eyebrow="$article->category" />

    @if ($preview)
        <x-vdbs.feedback.alert variant="warning">Vorschau: Dieser Artikel ist noch nicht öffentlich sichtbar.</x-vdbs.feedback.alert>
    @endif

    @if (filled($article->image_path) && filled($article->image_credit))
        <x-vdbs.media.image :src="route('articles.public.image', $article->slug)" :alt="$article->image_alt ?? ''" :credit="$article->image_credit" credit-prefix="Quelle" style="margin-bottom:var(--space-6)" />
    @endif

    <p style="color:var(--text-muted);font-size:var(--font-size-small)">@if ($article->published_at)<time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->format('d.m.Y') }}</time>@endif</p>

    <x-vdbs.content.prose>{!! $article->html() !!}</x-vdbs.content.prose>

    <p style="margin-top:var(--space-8)"><x-vdbs.actions.button variant="tertiary" :href="route('articles.public.index')">Alle Artikel anzeigen</x-vdbs.actions.button></p>
</x-layouts.app>
