@extends('layouts.administration')

@section('title', 'Mitgliedschaft · '.$displayName)

@section('content')
    @php
        $membershipStatus = $membership->status();
        $person = $membership->person;
    @endphp

    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm">
                <p class="page-title__kicker">Mitgliedschaften</p>
                <h1 class="page-title__title">{{ $displayName }}</h1>
                <p class="page-title__lead">Mitgliedschaft ab {{ $membership->starts_on->format('d.m.Y') }}</p>
            </div>
            <div class="page-title__actions">
                @if ($canManage)
                    <a class="btn" href="{{ route('administration.memberships.edit', $membership) }}">Bearbeiten</a>
                    @if ($membership->ends_on === null)
                        <a class="btn btn--secondary" href="{{ route('administration.memberships.end', $membership) }}">Mitgliedschaft beenden</a>
                    @elseif ($membershipStatus === \App\Modules\Membership\Enums\MembershipStatus::Ended)
                        <a class="btn btn--secondary" href="{{ route('administration.persons.memberships.create', $person) }}">Neue Mitgliedschaft</a>
                    @endif
                @endif
                <a class="btn btn--quiet" href="{{ route('administration.memberships.index') }}">Zur Liste</a>
            </div>
        </header>

        <dl class="key-facts">
            <div>
                <dt>Status</dt>
                <dd>
                    <x-vdbs.status :type="\App\Modules\Administration\Support\MembershipStatusPresentation::type($membershipStatus)">
                        {{ \App\Modules\Administration\Support\MembershipStatusPresentation::label($membershipStatus) }}
                    </x-vdbs.status>
                </dd>
            </div>
            <div><dt>Beginn</dt><dd>{{ $membership->starts_on->format('d.m.Y') }}</dd></div>
            <div><dt>Ende</dt><dd>{{ $membership->ends_on?->format('d.m.Y') ?? 'Offen' }}</dd></div>
        </dl>

        <section class="stack">
            <h2>Person</h2>
            <dl class="metadata-list">
                <div><dt>Name</dt><dd>{{ $displayName }}</dd></div>
                <div><dt>E-Mail-Adresse</dt><dd>{{ $person->email }}</dd></div>
                <div><dt>Portalzugang</dt><dd>{{ $person->user !== null ? 'Vorhanden' : 'Kein Konto' }}</dd></div>
            </dl>
            <div><a href="{{ route('administration.persons.show', $person) }}">Personendetail öffnen</a></div>
        </section>

        <section class="stack">
            <h2>Rollenwirkung</h2>
            <p>Bei einem verknüpften Benutzerkonto wird die automatische Rolle <code>member</code> mit demselben Beginn und Ende wie dieser Mitgliedschaftszeitraum geführt.</p>
        </section>
    </div>
@endsection
