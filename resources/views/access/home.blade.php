@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Persönlicher Zugang']];
@endphp

<x-layouts.app title="Persönlicher Zugang" width="wide">
    <x-vdbs.layout.page-header title="Persönlicher Zugang" lead="Angemeldet als {{ $contact->email }}." :breadcrumb="$crumbs">
        <x-slot:actions>
            <form method="post" action="{{ route('access.logout') }}">@csrf
                <x-vdbs.actions.button type="submit" variant="secondary" icon="log-out">Abmelden</x-vdbs.actions.button>
            </form>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.feedback.empty-state title="Zurzeit gibt es hier nichts zu tun" icon="info">
        <p>Der persönliche Zugang ist eingerichtet. Neue Angebote erscheinen an dieser Stelle.</p>
    </x-vdbs.feedback.empty-state>
</x-layouts.app>
