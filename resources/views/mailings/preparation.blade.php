@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => $area], ['label' => 'Rundschreiben', 'href' => route($base.'.index')], ['label' => $mailing->statusLabel()]];
    $who = $mailing->sentBy?->person?->fullName() ?? $mailing->sentBy?->email ?? 'unbekannt';
    $draft = $mailing->status === 'draft';
    $pending = $mailing->status === 'pending_approval';
    $scheduled = $mailing->status === 'scheduled';
    $lead = 'Von '.$who.', angelegt am '.$mailing->created_at?->format('d.m.Y, H:i');
    $sendAtText = $mailing->send_at?->format('d.m.Y, H:i').' Uhr';
    $pendingText = 'Dieses Schreiben wartet auf die Freigabe durch den Vorstand. Erst danach wird gesendet'.($mailing->send_at ? ' (geplant für '.$sendAtText.')' : '').'.';
    $approverName = $mailing->approvedBy ? ($mailing->approvedBy->person?->fullName() ?? $mailing->approvedBy->email) : null;
    $scheduledText = 'Der Planer startet den Versand zu dieser Zeit'.($approverName ? ', freigegeben von '.$approverName : '').'. Die Empfänger werden erst dann bestimmt: Abmeldungen bis dahin gelten.';
@endphp

<x-layouts.app title="Schreiben" width="wide">
    <x-vdbs.layout.page-header :title="$mailing->subject" :lead="$lead" :breadcrumb="$crumbs">
        <x-slot:meta>
            <x-vdbs.data.badge :tone="$mailing->statusTone()">{{ $mailing->statusLabel() }}</x-vdbs.data.badge>
        </x-slot:meta>
        <x-slot:actions>
            @if ($draft && $isAuthor)
                <x-vdbs.actions.button :href="route($base.'.create', ['entwurf' => $mailing->id])" icon="pencil">Entwurf bearbeiten</x-vdbs.actions.button>
            @endif
            @if (($pending || $scheduled) && ($isAuthor || auth()->user()->can('mailings.approve')))
                <form method="post" action="{{ route($base.'.withdraw', $mailing) }}">@csrf<x-vdbs.actions.button type="submit" variant="secondary">Zurückziehen</x-vdbs.actions.button></form>
            @endif
            @if ($scheduled)
                <form method="post" action="{{ route($base.'.cancel', $mailing) }}">@csrf<x-vdbs.actions.button type="submit" variant="danger" icon="x">Planung abbrechen</x-vdbs.actions.button></form>
            @endif
            <x-vdbs.actions.button variant="secondary" :href="route($base.'.index')">Zur Liste</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($draft && filled($mailing->rejection_note))
        <x-vdbs.feedback.alert variant="warning"><strong>Zurückgegeben mit der Begründung:</strong> {{ $mailing->rejection_note }}</x-vdbs.feedback.alert>
    @endif

    @if ($notice)
        <x-vdbs.feedback.alert variant="warning"><strong>Einladungsfrist:</strong> {{ $notice }}</x-vdbs.feedback.alert>
    @endif

    @if ($pending)
        <x-vdbs.feedback.alert variant="info">{{ $pendingText }}</x-vdbs.feedback.alert>
    @endif

    @if ($scheduled)
        <x-vdbs.feedback.alert variant="info">Geplant für <strong>{{ $sendAtText }}</strong>. {{ $scheduledText }}</x-vdbs.feedback.alert>
    @endif

    <x-vdbs.layout.grid :columns="3">
        <x-vdbs.data.stat label="Empfänger jetzt" :value="$recipientsNow" icon="mail" hint="Ohne Abgemeldete und Gesperrte" />
        <x-vdbs.data.stat label="Gruppen" :value="$groupLabels ?: '–'" icon="clipboard-list" />
        <x-vdbs.data.stat label="Pflichtmitteilung" :value="$mailing->mandatory ? 'Ja' : 'Nein'" icon="shield" />
    </x-vdbs.layout.grid>

    <section aria-labelledby="preview-title" style="margin-top:var(--space-6)">
        <h2 id="preview-title" style="font-size:var(--font-size-h3)">Vorschau</h2>
        <p style="color:var(--text-muted)">So sieht die Nachricht für Sie aus (Name und Angaben eingesetzt).</p>
        <div class="vdbs-article__box" style="display:block">
            <p><strong>Betreff:</strong> {{ $preview['subject'] }}</p>
            <x-vdbs.content.prose>{!! rich_text($preview['html']) !!}</x-vdbs.content.prose>
        </div>
    </section>

    @if ($canApprove)
        <section aria-labelledby="approve-title" style="margin-top:var(--space-6)">
            <h2 id="approve-title" style="font-size:var(--font-size-h3)">Freigabe</h2>
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-4);align-items:flex-start">
                <form method="post" action="{{ route($base.'.approve', $mailing) }}">@csrf<x-vdbs.actions.button type="submit" icon="check">Freigeben</x-vdbs.actions.button></form>
                <x-vdbs.actions.button variant="secondary" data-vdbs-dialog-open="reject-mailing">Zurückgeben …</x-vdbs.actions.button>
            </div>
        </section>

        <x-vdbs.overlays.confirm-dialog id="reject-mailing" title="Schreiben zurückgeben?" :action="route($base.'.reject', $mailing)" confirm-label="Zurückgeben">
            <p>Das Schreiben geht als Entwurf an {{ $who }} zurück. Bitte nennen Sie den Grund.</p>
            <x-slot:fields>
                <x-vdbs.forms.textarea name="note" label="Begründung" :required="true" maxlength="500" :rows="4" />
            </x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>
    @endif

    @if ($draft && $isAuthor)
        <section aria-labelledby="delete-title" style="margin-top:var(--space-8)">
            <x-vdbs.actions.button variant="danger-text" icon="trash-2" data-vdbs-dialog-open="delete-draft">Entwurf löschen</x-vdbs.actions.button>
        </section>

        <x-vdbs.overlays.confirm-dialog id="delete-draft" title="Entwurf löschen?" variant="danger" :action="route($base.'.destroy', $mailing)" method="delete" confirm-label="Entwurf löschen">
            <p>Der Entwurf wird endgültig gelöscht.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
