@php
    use App\Modules\Sso\Services\OAuthClients;
    use App\Modules\Sso\Services\OidcClaims;
    use App\Modules\Sso\Support\ConsentData;

    $person = $user->person?->fullName();
    $setting = app(OAuthClients::class)->settings($client);
    $rows = ConsentData::rows(app(OidcClaims::class)->forUser($user, collect($scopes)->pluck('id')->all()));
    $logo = filled($setting->logo_path) ? route('oidc.logo', $client->getKey()) : null;
    $origin = collect((array) $client->redirect_uris)->map(fn ($uri): ?string => ($parts = parse_url((string) $uri)) && isset($parts['scheme'], $parts['host']) ? $parts['scheme'].'://'.$parts['host'] : null)->filter()->first();
    $org = config('portal.letter.footer');
    $privacy = config('portal.sso_privacy');
    $name = $client->name;
@endphp
<x-sso.page title="Anmeldung bei {{ $name }}" heading="{{ $name }} möchte sich über das Portal anmelden" lead="Sie sind als {{ $person ?: $user->email }} angemeldet." :logo="$logo" :logo-alt="'Logo von '.$name">
    <p><strong>{{ $name }}</strong> möchte:</p>
    <ul>
        @foreach (collect($scopes)->map(fn ($scope) => OidcClaims::SCOPES[$scope->id] ?? $scope->description ?? $scope->id)->values() as $text)
            <li>{{ $text }}</li>
        @endforeach
    </ul>

    <p>Bevor Sie {{ $name }} zum ersten Mal nutzen, erklären wir Ihnen genau, welche Ihrer Daten das Vereinsportal an diesen Dienst weitergibt. Diese Frage kommt nur beim ersten Mal. Ihr Passwort sieht {{ $name }} nie.</p>

    <p><button type="button" class="vdbs-link-button" data-sso-expand>Alles einblenden</button></p>

    <details class="sso-section">
        <summary>§ 1 Verantwortliche Stelle und Datenschutzbeauftragte</summary>
        <div class="sso-section__body">
            <p>Verantwortlich für die Weitergabe Ihrer Daten durch das Vereinsportal ist:</p>
            <p>{{ $org['organization'] ?? 'VDBS e. V.' }}<br>@foreach (($org['address_lines'] ?? []) as $line){{ $line }}<br>@endforeach</p>
            <p>Kontakt: {{ implode(', ', $org['contact_lines'] ?? []) }}</p>
            @if (filled($privacy['dpo_email'] ?? null))
                <p>Die Datenschutzbeauftragte oder den Datenschutzbeauftragten erreichen Sie unter <a href="mailto:{{ $privacy['dpo_email'] }}">{{ $privacy['dpo_email'] }}</a>.</p>
            @else
                <p>Fragen zum Datenschutz richten Sie bitte an die oben genannte Stelle.</p>
            @endif
            <p>Weitere Informationen finden Sie in der <a href="{{ config('portal.legal.privacy_url') }}">Datenschutzerklärung des Vereinsportals</a>.</p>
        </div>
    </details>

    <details class="sso-section">
        <summary>§ 2 Was sind personenbezogene Daten?</summary>
        <div class="sso-section__body">
            <p>Personenbezogene Daten sind alle Informationen, die sich auf eine identifizierte oder identifizierbare Person beziehen, zum Beispiel Ihr Name, Ihre E-Mail-Adresse oder Ihre Rolle im Verein. Informationen, bei denen wir keinen Bezug zu Ihrer Person herstellen können, sind keine personenbezogenen Daten. Die Verarbeitung dieser Daten braucht eine gesetzliche Grundlage oder Ihre Einwilligung.</p>
        </div>
    </details>

    <details class="sso-section" open>
        <summary>§ 3 Datenverarbeitung durch das Vereinsportal</summary>
        <div class="sso-section__body">
            <h3 class="sso-section__title">1. Art und Umfang der Datenverarbeitung</h3>
            <p>Das Vereinsportal verarbeitet Ihre Daten, um Sie bei {{ $name }} anzumelden. Dafür gibt es nach Ihrer Zustimmung die folgenden Angaben an {{ $name }} weiter:</p>
            <ul>
                @foreach ($rows as $row)
                    <li><strong>{{ $row['label'] }}</strong>@if ($row['note']): {{ $row['note'] }}@endif</li>
                @endforeach
            </ul>
            <p>Nicht weitergegeben werden Ihr Passwort, Ihre Anschrift, Ihr Geburtsdatum und Ihre Telefonnummer.</p>
            <h3 class="sso-section__title">2. Rechtsgrundlage</h3>
            <p>Die Weitergabe beruht auf Ihrer Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO. Mit „Zustimmen“ erklären Sie sich einverstanden, dass das Vereinsportal die oben genannten Daten an {{ $name }} übermittelt. Ohne Ihre Zustimmung ist die Anmeldung bei diesem Dienst nicht möglich.</p>
            <h3 class="sso-section__title">3. Speicherdauer</h3>
            <p>Das Vereinsportal speichert Ihre Zustimmung (Dienst, Bereiche, Zeitpunkt), bis Sie sie widerrufen oder Ihr Konto gelöscht wird. Anmeldungen laufen nach einer Stunde ab und werden bei der Abmeldung widerrufen. {{ $name }} speichert Ihre Daten nach seinen eigenen Regeln (siehe § 4).</p>
        </div>
    </details>

    <details class="sso-section" open>
        <summary>§ 4 Empfänger der Daten: {{ $name }}</summary>
        <div class="sso-section__body">
            <p>Ihre Daten gehen an einen Dienst, der an das Vereinsportal angeschlossen ist, damit er Ihnen seine Leistung bereitstellen kann.</p>
            <ul>
                <li><strong>Name:</strong> {{ $name }}</li>
                @if ($origin)<li><strong>Adresse:</strong> {{ $origin }}</li>@endif
                @if (filled($setting->description))<li><strong>Beschreibung:</strong> {{ $setting->description }}</li>@endif
                <li><strong>Wofür der Dienst die Daten nutzt:</strong> {{ filled($setting->purpose) ? $setting->purpose : 'Zur Anmeldung und zur Bereitstellung seiner Leistung für Sie. Genaueres regelt der Dienst selbst.' }}</li>
                @if (filled($setting->contact))<li><strong>Ansprechperson beim Dienst:</strong> {{ $setting->contact }}</li>@endif
                @if (filled($setting->privacy_url))<li><strong>Datenschutzinformationen des Dienstes:</strong> <a href="{{ $setting->privacy_url }}">{{ $setting->privacy_url }}</a></li>@endif
            </ul>
        </div>
    </details>

    <details class="sso-section">
        <summary>§ 5 Ihre Rechte</summary>
        <div class="sso-section__body">
            <p>Sie haben gegenüber dem Verantwortlichen grundsätzlich folgende Rechte hinsichtlich Ihrer personenbezogenen Daten:</p>
            <ul>
                <li>Recht auf Auskunft, Art. 15 DSGVO</li>
                <li>Recht auf Berichtigung, Art. 16 DSGVO</li>
                <li>Recht auf Löschung, Art. 17 DSGVO</li>
                <li>Recht auf Einschränkung der Verarbeitung, Art. 18 DSGVO</li>
                <li>Recht auf Datenübertragbarkeit, Art. 20 DSGVO</li>
                <li>Recht auf Widerspruch gegen die Verarbeitung, Art. 21 DSGVO</li>
            </ul>
            <p><strong>Widerruf Ihrer Zustimmung:</strong> Sie können sie jederzeit ohne Angabe von Gründen widerrufen, mit Wirkung für die Zukunft. Dazu haben Sie zwei Möglichkeiten: Setzen Sie bei der nächsten Anmeldung bei {{ $name }} auf der Anmeldeseite das Häkchen „Meine Zustimmung zur Weitergabe meiner Daten widerrufen“, oder widerrufen Sie in Ihrem Konto unter „Verbundene Anwendungen“. Danach fragt das Portal beim nächsten Mal wieder nach Ihrer Zustimmung.</p>
            <p><strong>Beschwerde:</strong> Sie können sich bei einer Datenschutzaufsichtsbehörde beschweren{{ '' }}@if (filled($privacy['authority_name'] ?? null)). Zuständig ist die {{ $privacy['authority_name'] }}@if (filled($privacy['authority_url'] ?? null)) (<a href="{{ $privacy['authority_url'] }}">{{ $privacy['authority_url'] }}</a>)@endif @endif. Eine automatisierte Entscheidungsfindung findet nicht statt.</p>
        </div>
    </details>

    <h2 class="sso-section__heading">Beabsichtigte Datenübertragung</h2>
    <dl class="sso-data">
        @foreach ($rows as $row)
            <div class="sso-data__row"><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>
        @endforeach
    </dl>

    <p class="vdbs-hint">Ihr Passwort sieht {{ $name }} nie. Sie können Ihre Zustimmung jederzeit widerrufen (siehe § 5).</p>

    <div class="sso-actions">
        <form method="post" action="{{ url('/oauth/authorize') }}">@csrf
            <input type="hidden" name="state" value="{{ $request->input('state') }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <x-vdbs.actions.button type="submit" icon="check">Zustimmen</x-vdbs.actions.button>
        </form>
        <form method="post" action="{{ url('/oauth/authorize') }}">@csrf @method('DELETE')
            <input type="hidden" name="state" value="{{ $request->input('state') }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <x-vdbs.actions.button type="submit" variant="secondary" icon="x">Ablehnen</x-vdbs.actions.button>
        </form>
    </div>
</x-sso.page>
