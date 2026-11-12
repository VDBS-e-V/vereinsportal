@php
    $key = $page->pageKey();
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => $key->title()]];
@endphp

<x-layouts.app :title="$key->title()" width="medium">
    <x-vdbs.layout.page-header :title="$key->title()" :lead="$key->lead()" :breadcrumb="$crumbs">
        @can('pages.manage')
            <x-slot:actions>
                <x-vdbs.actions.button variant="secondary" :href="route('pages.admin.edit', $key->value)" icon="pencil">Seite bearbeiten</x-vdbs.actions.button>
            </x-slot:actions>
        @endcan
    </x-vdbs.layout.page-header>

    @if ($page->isDefault())
        @can('pages.manage')
            <x-vdbs.feedback.alert variant="warning">Dies ist der Standardtext. Bitte bearbeiten Sie die Seite und prüfen Sie den Text.</x-vdbs.feedback.alert>
        @endcan
    @endif

    <x-vdbs.content.prose>{!! $page->html() !!}</x-vdbs.content.prose>

    @if ($page->updated_at)
        <p style="margin-top:var(--space-6);color:var(--text-muted);font-size:var(--font-size-small)">Stand: {{ $page->updated_at->format('d.m.Y') }}</p>
    @endif
</x-layouts.app>
