<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Persönlicher Bereich</p>
        <h1>Mein Profil</h1>
        <p class="portal-page__lead">
            Hier werden künftig alle Einstellungen gebündelt, mit denen Sie Ihr öffentlich sichtbares Profil im Vereinsportal gestalten.
        </p>
    </header>

    <section class="portal-page__section" aria-labelledby="public-profile-heading">
        <div class="portal-page__section-header">
            <h2 id="public-profile-heading">Öffentliches Profil</h2>
        </div>

        <x-vdbs.notice type="info" role="status">
            Die Bearbeitung des öffentlichen Profils wird mit den zugehörigen Profilfunktionen ergänzt. Der Bereich ist bereits eigenständig angelegt und von den Kontoeinstellungen getrennt.
        </x-vdbs.notice>
    </section>
</div>
