@php
    $contact = config('portal.home.contact');
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Kontakt']];
@endphp

<x-layouts.app title="Kontakt" width="medium">
    <x-vdbs.layout.page-header title="Kontakt" :lead="$contact['text']" :breadcrumb="$crumbs" />

    @if (($contact['lines'] ?? []) !== [])
        <x-vdbs.cards.contact-block :title="$contact['title']" :lines="$contact['lines']" :level="2" />
    @else
        <x-vdbs.feedback.empty-state size="s" title="Kontaktdaten folgen" icon="mail">
            <p>Die Kontaktdaten werden hier angezeigt, sobald sie hinterlegt sind.</p>
        </x-vdbs.feedback.empty-state>
    @endif
</x-layouts.app>
