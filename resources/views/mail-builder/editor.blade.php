@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Systemmails', 'href' => route('mailbuilder.system.index')], ['label' => $mail->name]];
    $json = fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<x-layouts.app :title="$mail->name" width="wide">
    <x-vdbs.layout.page-header :title="$mail->name" :lead="$mail->description" :breadcrumb="$crumbs">
        <x-slot:meta>
            @if ($current['customized']) <x-vdbs.data.badge tone="success">Geändert</x-vdbs.data.badge> @else <x-vdbs.data.badge>Standardtext</x-vdbs.data.badge> @endif
            @if ($mail->security) <x-vdbs.data.badge tone="warning">Sicherheit</x-vdbs.data.badge> @endif
        </x-slot:meta>
    </x-vdbs.layout.page-header>

    @if ($readonly)
        <x-vdbs.feedback.alert variant="warning" title="Nur lesen">Diese Mail ist sicherheitsrelevant. Nur die Administration darf sie ändern; Sie sehen den Text und die Vorschau.</x-vdbs.feedback.alert>
    @endif

    @error('doc')
        <x-vdbs.feedback.alert variant="error" title="Die Mail wurde nicht gespeichert">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    <form method="POST" action="{{ route('mailbuilder.system.update', $mail->key) }}" id="mb-form" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="doc" value="{{ $json($current['doc']) }}" data-mb-doc>

        <div class="mb__panel" style="margin-bottom: var(--space-5);">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="200" :value="old('subject', $current['subject'])" :disabled="$readonly" :hint="'Platzhalter wie {url} sind möglich.'" />
                <x-vdbs.forms.input name="preheader" label="Vorschautext" :optional="true" maxlength="200" :value="old('preheader', $current['preheader'])" :disabled="$readonly" hint="Steht im Posteingang hinter dem Betreff." />
            </x-vdbs.forms.form-grid>
        </div>

        <div class="mb" data-mail-builder
            data-readonly="{{ $readonly ? '1' : '0' }}"
            data-preview-url="{{ route('mailbuilder.system.preview', $mail->key) }}"
            data-test-url="{{ route('mailbuilder.system.test', $mail->key) }}"
            data-csrf="{{ csrf_token() }}"
            data-types="{{ $json($types) }}"
            data-blank="{{ $json($blank) }}"
            data-placeholders="{{ $json($placeholders) }}"
            data-protected="{{ $json($protected) }}"
            data-assets="{{ $json($assets) }}"
            data-tinymce="{{ asset('vendor/tinymce') }}">

            <div class="mb__side">
                @unless ($readonly)
                    <div class="mb__panel">
                        <h2 class="mb__title">Block hinzufügen</h2>
                        <div class="mb__palette" data-mb-palette role="group" aria-label="Block hinzufügen"></div>
                    </div>
                @endunless
                <div class="mb__panel">
                    <h2 class="mb__title">Platzhalter</h2>
                    <p class="mb__hint">Ein Klick fügt den Platzhalter dort ein, wo Sie zuletzt geschrieben haben. Mit * markierte müssen in der Mail stehen bleiben.</p>
                    <div class="mb__palette" data-mb-placeholders role="group" aria-label="Platzhalter einfügen"></div>
                </div>
            </div>

            <div class="mb__side">
                <div class="mb__panel">
                    <h2 class="mb__title">Aufbau der Mail</h2>
                    <ol class="mb__blocks" data-mb-list aria-label="Blöcke der Mail"></ol>
                </div>
                <div class="mb__panel">
                    <h2 class="mb__title" data-mb-inspector-title>Block bearbeiten</h2>
                    <div class="mb__inspector" data-mb-inspector></div>
                </div>
            </div>

            <div class="mb__panel mb__preview">
                <h2 class="mb__title">Vorschau mit Beispielwerten</h2>
                <div class="mb__preview-bar" role="group" aria-label="Ansicht">
                    <x-vdbs.actions.button variant="secondary" size="s" data-mb-device="desktop" aria-pressed="true">Computer</x-vdbs.actions.button>
                    <x-vdbs.actions.button variant="secondary" size="s" data-mb-device="mobile" aria-pressed="false">Handy</x-vdbs.actions.button>
                    <x-vdbs.actions.button variant="secondary" size="s" data-mb-dark aria-pressed="false">Dunkelmodus</x-vdbs.actions.button>
                    <x-vdbs.actions.button variant="secondary" size="s" data-mb-tab="text" aria-pressed="false">Textfassung</x-vdbs.actions.button>
                </div>
                <div role="status" aria-live="polite">
                    <p class="mb__ok" data-mb-ok hidden>Keine Hinweise.</p>
                    <ul class="mb__issues" data-mb-issues></ul>
                </div>
                <iframe class="mb__preview-frame" title="Vorschau der Mail" data-mb-frame sandbox=""></iframe>
                <pre class="mb__text" data-mb-text hidden></pre>
                <p role="status" aria-live="polite" data-mb-message class="mb__hint"></p>
            </div>
        </div>

        <div class="mb__panel" style="margin-top: var(--space-5);">
            @unless ($readonly)
                <div class="mb__preview-bar">
                    <x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button>
                    <x-vdbs.actions.button variant="secondary" data-mb-test>Testmail an mich</x-vdbs.actions.button>
                    <x-vdbs.actions.button variant="secondary" :href="route('mailbuilder.system.index')">Zur Liste</x-vdbs.actions.button>
                </div>
            @else
                <x-vdbs.actions.button variant="secondary" :href="route('mailbuilder.system.index')">Zur Liste</x-vdbs.actions.button>
            @endunless
        </div>
    </form>

    @unless ($readonly)
        @if ($current['customized'])
            <x-vdbs.layout.section title="Standardtext wiederherstellen">
                <form method="POST" action="{{ route('mailbuilder.system.reset', $mail->key) }}">
                    @csrf
                    <p>Verwirft Ihre Änderung. Danach gilt wieder der mitgelieferte Text.</p>
                    <x-vdbs.actions.button type="submit" variant="secondary">Auf Standard zurücksetzen</x-vdbs.actions.button>
                </form>
            </x-vdbs.layout.section>
        @endif

        @if ($versions->isNotEmpty())
            <x-vdbs.layout.section title="Frühere Stände" lead="Jedes Speichern legt den bisherigen Stand ab. Beim Wiederherstellen wird der aktuelle Stand selbst zur Version.">
                <x-vdbs.data.table caption="Frühere Stände" :columns="[['key' => 'when', 'label' => 'Gespeichert'], ['key' => 'who', 'label' => 'Von'], ['key' => 'subject', 'label' => 'Betreff'], ['key' => 'act', 'label' => '']]">
                    @foreach ($versions as $version)
                        @continue(! is_array($version->blocks))
                        <tr>
                            <td>{{ $version->created_at?->format('d.m.Y H:i') }}</td>
                            <td>{{ $version->savedBy?->person?->fullName() ?: $version->savedBy?->email ?: '–' }}</td>
                            <td>{{ $version->subject }}</td>
                            <td>
                                <form method="POST" action="{{ route('mailbuilder.system.restore', [$mail->key, $version]) }}">
                                    @csrf
                                    <x-vdbs.actions.button type="submit" variant="secondary" size="s">Wiederherstellen</x-vdbs.actions.button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </x-vdbs.data.table>
            </x-vdbs.layout.section>
        @endif
    @endunless
</x-layouts.app>
