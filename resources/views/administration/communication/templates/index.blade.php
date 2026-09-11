@extends('layouts.administration')

@section('title', 'Kommunikation')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm">
                <p class="page-title__kicker">Kommunikation</p>
                <h1 class="page-title__title">E-Mail-Vorlagen</h1>
                <p class="page-title__lead">Systemvorlagen, Entwürfe und veröffentlichte Versionen verwalten.</p>
            </div>
            <div class="page-title__actions">
                <a class="btn btn--secondary" href="{{ route('administration.communication.deliveries.index') }}">Versandhistorie</a>
            </div>
        </header>

        @if ($templates->isEmpty())
            <x-vdbs.empty-state title="Keine E-Mail-Vorlagen" description="Es wurden noch keine Systemvorlagen angelegt." />
        @else
            <div class="record-list">
                @foreach ($templates as $template)
                    <article class="record-item">
                        <div class="record-item__main">
                            <h2 class="record-item__title">
                                <a href="{{ route('administration.communication.templates.show', $template) }}">{{ $template->name }}</a>
                            </h2>
                            <div class="record-item__meta">
                                <span>{{ $template->key }}</span>
                                <span>{{ $template->versions_count }} veröffentlichte Version(en)</span>
                            </div>
                        </div>
                        <div class="record-item__actions">
                            <x-vdbs.status :type="$template->is_active ? 'success' : 'warning'">
                                {{ $template->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </x-vdbs.status>
                        </div>
                    </article>
                @endforeach
            </div>

            {{ $templates->links() }}
        @endif
    </div>
@endsection
