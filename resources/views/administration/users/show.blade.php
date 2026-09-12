@extends('layouts.administration')

@section('title', $displayName)

@section('content')
    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm">
                <p class="page-title__kicker">Benutzerverwaltung</p>
                <h1 class="page-title__title">{{ $displayName }}</h1>
                <p class="page-title__lead">{{ $user->email }}</p>
            </div>

            <div class="page-title__actions">
                <a class="btn btn--secondary" href="{{ route('administration.users.index') }}">
                    Zur Benutzerliste
                </a>
            </div>
        </header>

        @if ($errors->any())
            <x-vdbs.validation-summary role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </x-vdbs.validation-summary>
        @endif

        <dl class="key-facts">
            <div>
                <dt>Status</dt>
                <dd>
                    <x-vdbs.status
                        :type="\App\Modules\Administration\Support\UserStatusPresentation::type($user->status)"
                    >
                        {{ \App\Modules\Administration\Support\UserStatusPresentation::label($user->status) }}
                    </x-vdbs.status>
                </dd>
            </div>
            <div>
                <dt>E-Mail bestätigt</dt>
                <dd>{{ $user->email_verified_at !== null ? 'Ja' : 'Nein' }}</dd>
            </div>
            <div>
                <dt>Letzte Anmeldung</dt>
                <dd>
                    {{ $user->last_login_at?->format('d.m.Y, H:i') ?? 'Noch nie' }}
                    @if ($user->last_login_at !== null)
                        Uhr
                    @endif
                </dd>
            </div>
        </dl>

        <section class="stack">
            <h2>Kontodaten</h2>

            <dl class="metadata-list">
                <div>
                    <dt>E-Mail-Adresse</dt>
                    <dd>{{ $user->email }}</dd>
                </div>
                <div>
                    <dt>Konto erstellt</dt>
                    <dd>{{ $user->created_at->format('d.m.Y, H:i') }} Uhr</dd>
                </div>
                <div>
                    <dt>Zuletzt geändert</dt>
                    <dd>{{ $user->updated_at->format('d.m.Y, H:i') }} Uhr</dd>
                </div>
                <div>
                    <dt>Session-Version</dt>
                    <dd>{{ $user->session_version }}</dd>
                </div>
            </dl>
        </section>

        @if ($user->person !== null)
            <section class="stack">
                <h2>Person</h2>

                <dl class="metadata-list">
                    <div>
                        <dt>Name</dt>
                        <dd>{{ $displayName }}</dd>
                    </div>
                    <div>
                        <dt>Geburtsdatum</dt>
                        <dd>{{ $user->person->birth_date->format('d.m.Y') }}</dd>
                    </div>
                    <div>
                        <dt>Telefon</dt>
                        <dd>{{ $user->person->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Ort</dt>
                        <dd>
                            {{ trim(($user->person->postal_code ?? '').' '.($user->person->city ?? '')) ?: '—' }}
                        </dd>
                    </div>
                </dl>
            </section>
        @endif

        @if ($canManageStatus)
            <section class="stack">
                <header class="stack stack--sm">
                    <h2>Kontostatus verwalten</h2>
                    <p>
                        Statusänderungen werden protokolliert. Eine Deaktivierung
                        invalidiert zusätzlich bestehende Anmeldesitzungen.
                    </p>
                </header>

                @if ($user->is(auth()->user()))
                    <x-vdbs.notice type="warning">
                        Das eigene Administrationskonto kann hier nicht deaktiviert werden.
                    </x-vdbs.notice>
                @elseif ($user->status === \App\Modules\Identity\Enums\UserStatus::Active)
                    <x-vdbs.danger-zone
                        title="Konto deaktivieren"
                        description="Das Konto kann sich danach nicht mehr anmelden. Bestehende Sitzungen werden invalidiert."
                    >
                        <form
                            class="form"
                            method="POST"
                            action="{{ route('administration.users.status.update', $user) }}"
                        >
                            @csrf
                            <input type="hidden" name="status_action" value="disable">

                            <div class="form__field">
                                <label class="form__label" for="administration-disable-comment">
                                    Begründung
                                </label>
                                <textarea
                                    class="form__control"
                                    id="administration-disable-comment"
                                    name="status_comment"
                                    required
                                    maxlength="500"
                                    @error('status_comment') aria-invalid="true" @enderror
                                >{{ old('status_comment') }}</textarea>
                                <p class="form__help">
                                    Die Begründung wird im Audit-Protokoll gespeichert.
                                </p>
                            </div>

                            <button
                                class="btn btn--danger"
                                type="submit"
                                onclick="return confirm('Konto wirklich deaktivieren?')"
                            >
                                Konto deaktivieren
                            </button>
                        </form>
                    </x-vdbs.danger-zone>
                @elseif ($user->status === \App\Modules\Identity\Enums\UserStatus::Disabled)
                    <div class="panel stack">
                        <h3>Konto reaktivieren</h3>
                        <p>
                            Eine Reaktivierung ist nur für bereits bestätigte
                            E-Mail-Adressen möglich.
                        </p>

                        <form
                            class="form"
                            method="POST"
                            action="{{ route('administration.users.status.update', $user) }}"
                        >
                            @csrf
                            <input type="hidden" name="status_action" value="reactivate">

                            <div class="form__field">
                                <label class="form__label" for="administration-reactivate-comment">
                                    Begründung
                                </label>
                                <textarea
                                    class="form__control"
                                    id="administration-reactivate-comment"
                                    name="status_comment"
                                    required
                                    maxlength="500"
                                    @error('status_comment') aria-invalid="true" @enderror
                                >{{ old('status_comment') }}</textarea>
                            </div>

                            <button class="btn" type="submit">
                                Konto reaktivieren
                            </button>
                        </form>
                    </div>
                @else
                    <x-vdbs.notice type="info">
                        Für diesen Kontostatus ist in der Verwaltung keine direkte
                        Statusänderung vorgesehen.
                    </x-vdbs.notice>
                @endif
            </section>
        @endif

        <section class="stack">
            <header class="stack stack--sm">
                <h2>Rollen</h2>
                <p>
                    Aktuelle und historische Rollenzuweisungen des Kontos.
                </p>
            </header>

            @if ($canManageRoles)
                <div class="panel stack">
                    <h3>Rolle manuell zuweisen</h3>

                    <form
                        class="form"
                        method="POST"
                        action="{{ route('administration.users.roles.assign', $user) }}"
                    >
                        @csrf

                        <div class="form__grid form__grid--2">
                            <div class="form__field">
                                <label class="form__label" for="administration-role-key">
                                    Rolle
                                </label>
                                <select
                                    class="form__control"
                                    id="administration-role-key"
                                    name="role_key"
                                    required
                                    @error('role_key') aria-invalid="true" @enderror
                                >
                                    <option value="">Bitte auswählen</option>
                                    @foreach ($availableRoles as $role)
                                        <option
                                            value="{{ $role->key }}"
                                            @selected(old('role_key') === $role->key)
                                        >
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form__field">
                                <label class="form__label" for="administration-role-comment">
                                    Begründung
                                </label>
                                <textarea
                                    class="form__control"
                                    id="administration-role-comment"
                                    name="role_comment"
                                    required
                                    maxlength="500"
                                    @error('role_comment') aria-invalid="true" @enderror
                                >{{ old('role_comment') }}</textarea>
                            </div>
                        </div>

                        <button class="btn" type="submit">
                            Rolle zuweisen
                        </button>
                    </form>
                </div>
            @endif

            @if ($user->roleAssignments->isEmpty())
                <x-vdbs.empty-state
                    title="Keine Rollenzuweisungen"
                    description="Für dieses Konto sind derzeit keine Rollenzuweisungen gespeichert."
                />
            @else
                <div class="record-list">
                    @foreach ($user->roleAssignments as $assignment)
                        @php
                            $assignmentActive =
                                $assignment->starts_at->lte(now())
                                && (
                                    $assignment->ends_at === null
                                    || $assignment->ends_at->gt(now())
                                );
                            $assignmentIsManual =
                                $assignment->source
                                === \App\Modules\Identity\Enums\RoleAssignmentSource::Manual;
                            $protectOwnAdministration =
                                $user->is(auth()->user())
                                && $assignment->role?->key
                                    === \App\Modules\Identity\Enums\RoleKey::Administration->value;
                        @endphp

                        <article class="record-item">
                            <div class="record-item__main">
                                <h3 class="record-item__title">
                                    {{ $assignment->role?->name ?? 'Unbekannte Rolle' }}
                                </h3>
                                <div class="record-item__meta">
                                    <span>
                                        Seit {{ $assignment->starts_at->format('d.m.Y, H:i') }} Uhr
                                    </span>
                                    @if ($assignment->ends_at !== null)
                                        <span>
                                            Bis {{ $assignment->ends_at->format('d.m.Y, H:i') }} Uhr
                                        </span>
                                    @endif
                                    <span>
                                        Quelle:
                                        {{ match ($assignment->source) {
                                            \App\Modules\Identity\Enums\RoleAssignmentSource::Automatic => 'Automatisch',
                                            \App\Modules\Identity\Enums\RoleAssignmentSource::Manual => 'Manuell',
                                            \App\Modules\Identity\Enums\RoleAssignmentSource::Console => 'Konsole',
                                        } }}
                                    </span>
                                    @if ($assignment->grantedBy !== null)
                                        <span>
                                            Vergeben von {{ $assignment->grantedBy->email }}
                                        </span>
                                    @endif
                                    @if ($assignment->comment)
                                        <span>{{ $assignment->comment }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="record-item__actions stack stack--sm">
                                <x-vdbs.status :type="$assignmentActive ? 'success' : 'info'">
                                    {{ $assignmentActive ? 'Aktiv' : 'Historisch' }}
                                </x-vdbs.status>

                                @if (
                                    $canManageRoles
                                    && $assignmentActive
                                    && $assignmentIsManual
                                    && ! $protectOwnAdministration
                                )
                                    <form
                                        class="form"
                                        method="POST"
                                        action="{{ route('administration.users.roles.end', [$user, $assignment]) }}"
                                    >
                                        @csrf

                                        <div class="form__field">
                                            <label
                                                class="form__label"
                                                for="administration-role-end-{{ $assignment->id }}"
                                            >
                                                Begründung
                                            </label>
                                            <input
                                                class="form__control"
                                                id="administration-role-end-{{ $assignment->id }}"
                                                name="end_comment"
                                                type="text"
                                                required
                                                maxlength="500"
                                            >
                                        </div>

                                        <button
                                            class="btn btn--danger btn--sm"
                                            type="submit"
                                            onclick="return confirm('Rollenzuweisung wirklich beenden?')"
                                        >
                                            Zuweisung beenden
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
