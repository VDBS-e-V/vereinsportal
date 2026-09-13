<?php

use App\Modules\Identity\Actions\Profile\DeleteProfileAvatarAction;
use App\Modules\Identity\Actions\Profile\StoreProfileAvatarAction;
use App\Modules\Identity\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.public')]
    class extends Component {
    use WithFileUploads;

    public ?TemporaryUploadedFile $avatar = null;

    public bool $saved = false;

    public bool $removed = false;

    public function saveAvatar(StoreProfileAvatarAction $storeAvatar): void
    {
        $user = auth()->user();

        if (! $user instanceof User || $this->avatar === null) {
            return;
        }

        $this->saved = false;
        $this->removed = false;

        $this->validate([
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $storeAvatar->execute(
            user: $user,
            avatar: $this->avatar,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );

        $this->reset('avatar');
        $this->saved = true;
    }

    public function deleteAvatar(DeleteProfileAvatarAction $deleteAvatar): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->saved = false;
        $this->removed = false;

        $deleteAvatar->execute(
            user: $user,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );

        $this->reset('avatar');
        $this->resetValidation('avatar');
        $this->removed = true;
    }
};

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

    @if ($saved)
        <x-vdbs.notice type="success" role="status">
            Ihr Profilbild wurde gespeichert.
        </x-vdbs.notice>
    @endif

    @if ($removed)
        <x-vdbs.notice type="success" role="status">
            Ihr Profilbild wurde gelöscht.
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

            @if ($avatar !== null)
                <div class="avatar avatar--large vdbs-avatar vdbs-avatar--large">
                    <img src="{{ $avatar->temporaryUrl() }}" alt="Vorschau Ihres Profilbilds">
                </div>
            @elseif ($currentAvatarUrl !== null)
                <div class="avatar avatar--large vdbs-avatar vdbs-avatar--large">
                    <img src="{{ $currentAvatarUrl }}" alt="Ihr Profilbild">
                </div>
            @else
                <p class="form__help">Noch kein Profilbild hinterlegt.</p>
            @endif
        </div>

        <form class="form" wire:submit="saveAvatar">
            <div class="form__field">
                <label class="form__label" for="profile-avatar">Profilbild auswählen</label>
                <input
                    class="form__control"
                    id="profile-avatar"
                    type="file"
                    wire:model="avatar"
                    accept="image/jpeg,image/png,image/webp"
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
                <button
                    class="btn"
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="avatar,saveAvatar"
                    @disabled($avatar === null)
                >
                    Profilbild speichern
                </button>
            </div>
        </form>

        @if ($currentAvatarUrl !== null)
            <div class="portal-page__actions">
                <button
                    class="btn btn--secondary"
                    type="button"
                    wire:click="deleteAvatar"
                    wire:loading.attr="disabled"
                    wire:target="deleteAvatar"
                >
                    Profilbild löschen
                </button>
            </div>
        @endif
    </section>
</div>
