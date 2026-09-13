<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

@php
    $contactEmail = (string) config('mail.from.address');
@endphp

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Diese Seite</p>
        <h1>Impressum</h1>
        <p class="portal-page__lead">Anbieterinformationen für das VDBS Vereinsportal.</p>
    </header>

    <section class="portal-page__section">
        <div class="article">
            <h2>Verantwortlicher Verein</h2>
            <p>Verband für Demokratiebildung und Bibliotheken an Schulen e.V.</p>
            <p>
                Portalbezogene Rückfragen können an
                <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                gerichtet werden.
            </p>
        </div>

        <x-vdbs.notice type="info">
            Die vollständigen rechtlichen Pflichtangaben werden vor Veröffentlichung
            mit dem freigegebenen Vereinstext aus der Design-/Redaktionsvorlage abgeglichen.
        </x-vdbs.notice>
    </section>
</div>
