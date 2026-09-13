<?php

use App\Modules\Identity\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

@php
    $profileUser = auth()->user();
    $currentAvatarUrl = $profileUser instanceof User
        ? $profileUser->avatarUrl()
        : null;
@endphp

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Persönlicher Bereich</p>
        <h1>Mein Profil</h1>
        <p class="portal-page__lead">
            Hier gestalten Sie die Inhalte, die zu Ihrem öffentlich sichtbaren Profil im Vereinsportal gehören.
        </p>
    </header>

    @if (session('avatar_status'))
        <x-vdbs.notice type="success" role="status">
            {{ session('avatar_status') }}
        </x-vdbs.notice>
    @endif

    <section class="portal-page__section" aria-labelledby="public-profile-heading">
        <div class="portal-page__section-header">
            <h2 id="public-profile-heading">Öffentliches Profil</h2>
            <p>Dieser Bereich ist eigenständig angelegt und von den Kontoeinstellungen getrennt.</p>
        </div>

        <div class="portal-page__section-header">
            <h3>Profilbild</h3>
            <p>JPEG-, PNG- oder WebP-Datei mit maximal 5 MB.</p>
        </div>

        <div class="form__field">
            <span class="form__label">Aktuelles Bild</span>

            @if ($currentAvatarUrl !== null)
                <div class="avatar avatar--large vdbs-avatar vdbs-avatar--large">
                    <img src="{{ $currentAvatarUrl }}" alt="Ihr Profilbild">
                </div>
            @else
                <p class="form__help">Noch kein Profilbild hinterlegt.</p>
            @endif
        </div>

        <form
            class="form"
            method="POST"
            action="{{ route('my.account.avatar.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            <div class="form__field">
                <label class="form__label" for="profile-avatar">Profilbild auswählen</label>
                <input
                    class="form__control"
                    id="profile-avatar"
                    type="file"
                    name="avatar"
                    accept="image/jpeg,image/png,image/webp"
                    required
                    @error('avatar')
                        aria-invalid="true"
                        aria-describedby="profile-avatar-error"
                    @enderror
                >

                @error('avatar')
                    <p class="form__error" id="profile-avatar-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="portal-page__actions">
                <button class="btn" type="submit">
                    Profilbild speichern
                </button>
            </div>
        </form>

        @if ($currentAvatarUrl !== null)
            <div class="portal-page__actions">
                <form
                    method="POST"
                    action="{{ route('my.account.avatar.delete') }}"
                >
                    @csrf
                    @method('DELETE')

                    <button class="btn btn--secondary" type="submit">
                        Profilbild löschen
                    </button>
                </form>
            </div>
        @endif
    </section>
</div>
