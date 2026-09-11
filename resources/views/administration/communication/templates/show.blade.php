@extends('layouts.administration')

@section('title', $template->name)

@section('content')
    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm">
                <p class="page-title__kicker">Kommunikation</p>
                <h1 class="page-title__title">{{ $template->name }}</h1>
                <p class="page-title__lead">{{ $template->key }}</p>
            </div>
            <div class="page-title__actions">
                <x-vdbs.status :type="$template->is_active ? 'success' : 'warning'">
                    {{ $template->is_active ? 'Aktiv' : 'Inaktiv' }}
                </x-vdbs.status>
                <a class="btn btn--secondary" href="{{ route('administration.communication.templates.index') }}">Zur Vorlagenliste</a>
            </div>
        </header>

        @if ($canManage)
            <section class="panel stack">
                <h2>Entwurf bearbeiten</h2>
                <form class="form stack" action="{{ route('administration.communication.templates.draft.update', $template) }}" method="post">
                    @csrf
                    @method('put')

                    <div class="form__field">
                        <label class="form__label" for="draft_subject">Betreff</label>
                        <input class="form__control" id="draft_subject" name="draft_subject" type="text" maxlength="255" value="{{ old('draft_subject', $template->draft_subject) }}" required>
                        @error('draft_subject')
                            <p class="form__error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="draft_html">HTML-Entwurf</label>
                        <textarea class="form__control" id="draft_html" name="draft_html" rows="16" required>{{ old('draft_html', $template->draft_html) }}</textarea>
                        <p class="form__hint">Nur die unten aufgeführten Platzhalter sind zulässig. Beim Veröffentlichen wird HTML sanitisiert.</p>
                        @error('draft_html')
                            <p class="form__error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="page-title__actions">
                        <button class="btn" type="submit">Entwurf speichern</button>
                    </div>
                </form>
            </section>
        @else
            <section class="panel stack">
                <h2>Aktueller Entwurf</h2>
                <dl class="metadata-list">
                    <div><dt>Betreff</dt><dd>{{ $template->draft_subject }}</dd></div>
                    <div><dt>Letzte Änderung</dt><dd>{{ $template->updated_at->format('d.m.Y, H:i') }} Uhr</dd></div>
                </dl>
            </section>
        @endif

        <section class="stack">
            <h2>Platzhalter</h2>
            <div class="record-list">
                @foreach ($template->placeholders as $placeholder)
                    <article class="record-item">
                        <div class="record-item__main">
                            <h3 class="record-item__title">{{ '{{ '.$placeholder->key.' }}' }}</h3>
                            <div class="record-item__meta">
                                <span>{{ $placeholder->label }}</span>
                                <span>{{ $placeholder->is_required ? 'Pflicht' : 'Optional' }}</span>
                                @if ($placeholder->description)
                                    <span>{{ $placeholder->description }}</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        @if ($canManage)
            <section class="panel stack">
                <h2>Veröffentlichung und Status</h2>
                <p>Veröffentlichen erzeugt eine unveränderliche neue Version. Aktivieren steuert, ob Systemprozesse diese Vorlage tatsächlich versenden dürfen.</p>
                <div class="page-title__actions">
                    <form action="{{ route('administration.communication.templates.publish', $template) }}" method="post">
                        @csrf
                        <button class="btn" type="submit">Neue Version veröffentlichen</button>
                    </form>
                    <form action="{{ route('administration.communication.templates.status.update', $template) }}" method="post">
                        @csrf
                        <input type="hidden" name="active" value="{{ $template->is_active ? '0' : '1' }}">
                        <button class="btn btn--secondary" type="submit">{{ $template->is_active ? 'Deaktivieren' : 'Aktivieren' }}</button>
                    </form>
                </div>
            </section>
        @endif

        <section class="stack">
            <h2>Veröffentlichte Versionen</h2>
            @if ($template->versions->isEmpty())
                <x-vdbs.empty-state title="Noch keine veröffentlichte Version" description="Systemversand bleibt dadurch fail-closed." />
            @else
                <div class="record-list">
                    @foreach ($template->versions as $version)
                        <article class="record-item">
                            <div class="record-item__main">
                                <h3 class="record-item__title">Version {{ $version->version }}</h3>
                                <div class="record-item__meta">
                                    <span>{{ $version->subject }}</span>
                                    <span>Veröffentlicht {{ $version->published_at->format('d.m.Y, H:i') }} Uhr</span>
                                    @if ($version->publishedBy !== null)
                                        <span>von {{ $version->publishedBy->email }}</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
