<section class="stack" aria-labelledby="membership-consents-heading">
    <div class="stack stack--xs">
        <h2 id="membership-consents-heading">Zustimmungen</h2>
        <p>Mitgliedschaftsbezogene Zustimmungen werden unabhängig vom Datenschutz-Nachweis der Registrierung geführt. Widerrufe bleiben in der Historie sichtbar.</p>
    </div>

    @if ($membership->consents->isEmpty())
        <p>Noch keine mitgliedschaftsbezogenen Zustimmungen hinterlegt.</p>
    @else
        <div class="stack">
            @foreach ($membership->consents as $consent)
                <article class="record-item">
                    <div class="record-item__main stack stack--xs">
                        <div class="cluster">
                            <h3 class="record-item__title">{{ $consent->label }}</h3>
                            <x-vdbs.status :type="$consent->isActive() ? 'success' : 'neutral'">
                                {{ $consent->isActive() ? 'Aktiv' : 'Widerrufen' }}
                            </x-vdbs.status>
                        </div>
                        <dl class="metadata-list">
                            <div><dt>Schlüssel</dt><dd><code>{{ $consent->consent_key }}</code></dd></div>
                            <div><dt>Version / Stand</dt><dd>{{ $consent->version }}</dd></div>
                            <div><dt>Quelle</dt><dd>{{ $consent->source->label() }}</dd></div>
                            <div><dt>Zugestimmt am</dt><dd>{{ $consent->granted_at->format('d.m.Y H:i') }}</dd></div>
                            @if ($consent->revoked_at !== null)
                                <div><dt>Widerrufen am</dt><dd>{{ $consent->revoked_at->format('d.m.Y H:i') }}</dd></div>
                                <div><dt>Widerrufsgrund</dt><dd>{{ $consent->revocation_reason }}</dd></div>
                            @endif
                            @if ($consent->notes !== null)
                                <div><dt>Hinweis</dt><dd>{{ $consent->notes }}</dd></div>
                            @endif
                        </dl>
                    </div>
                    @if ($canManage && $consent->isActive())
                        <div class="record-item__actions">
                            <details>
                                <summary>Widerruf erfassen</summary>
                                <form class="form stack" method="post" action="{{ route('administration.memberships.consents.revoke', [$membership, $consent]) }}">
                                    @csrf
                                    <div class="form__field">
                                        <label class="form__label" for="consent-revocation-reason-{{ $consent->id }}">Begründung</label>
                                        <textarea class="form__control" id="consent-revocation-reason-{{ $consent->id }}" name="reason" rows="3" maxlength="1000" required></textarea>
                                    </div>
                                    <button class="btn btn--secondary" type="submit">Widerruf speichern</button>
                                </form>
                            </details>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    @if ($canManage)
        <details class="stack">
            <summary>Zustimmung erfassen</summary>
            <form class="form stack" method="post" action="{{ route('administration.memberships.consents.store', $membership) }}">
                @csrf
                <div class="form__field">
                    <label class="form__label" for="membership-consent-key">Zweck-Schlüssel</label>
                    <input class="form__control" id="membership-consent-key" name="consent_key" type="text" maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" value="{{ old('consent_key') }}" required>
                    <p class="form__hint">Stabiler technischer Schlüssel, z. B. <code>foto.veroeffentlichung</code>.</p>
                </div>
                <div class="form__field">
                    <label class="form__label" for="membership-consent-label">Bezeichnung / Zweck</label>
                    <input class="form__control" id="membership-consent-label" name="label" type="text" maxlength="150" value="{{ old('label') }}" required>
                </div>
                <div class="form__field">
                    <label class="form__label" for="membership-consent-version">Version / Stand</label>
                    <input class="form__control" id="membership-consent-version" name="version" type="text" maxlength="50" value="{{ old('version') }}" required>
                </div>
                <div class="form__field">
                    <label class="form__label" for="membership-consent-source">Quelle</label>
                    <select class="form__control" id="membership-consent-source" name="source" required>
                        @foreach ($consentSources as $consentSource)
                            <option value="{{ $consentSource->value }}" @selected(old('source') === $consentSource->value)>{{ $consentSource->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form__field">
                    <label class="form__label" for="membership-consent-granted-at">Zeitpunkt der Zustimmung</label>
                    <input class="form__control" id="membership-consent-granted-at" name="granted_at" type="datetime-local" value="{{ old('granted_at') }}" required>
                </div>
                <div class="form__field">
                    <label class="form__label" for="membership-consent-notes">Hinweis (optional)</label>
                    <textarea class="form__control" id="membership-consent-notes" name="notes" rows="3" maxlength="1000">{{ old('notes') }}</textarea>
                </div>
                <button class="btn" type="submit">Zustimmung erfassen</button>
            </form>
        </details>
    @endif
</section>
