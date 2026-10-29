@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Willkommenspaket']];
@endphp

<x-layouts.app title="Willkommenspaket" width="wide">
    <x-vdbs.layout.page-header title="Willkommenspaket für neue Mitglieder" lead="Beim Aufnehmen eines Online-Antrags geht eine Willkommensmail mit Satzung und Willkommensbrief an die Person." :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    <x-vdbs.feedback.alert variant="info">
        @if ($autoSend)
            Das Willkommenspaket geht automatisch beim Aufnehmen eines Online-Antrags. Für vom Vorstand erfasste Mitglieder lässt es sich auf der Seite der Mitgliedschaft senden. Fehlt ein Dokument, geht die Mail ohne diesen Anhang.
        @else
            Das automatische Senden beim Aufnehmen ist abgeschaltet. Auf der Seite einer Mitgliedschaft lässt sich das Willkommenspaket weiter von Hand senden.
        @endif
    </x-vdbs.feedback.alert>

    <div style="margin-top:var(--space-6)">
        @include('onboarding._documents', ['kinds' => $kinds, 'documents' => $documents, 'canManage' => $canManage, 'uploadRoute' => route('onboarding.member.upload')])
    </div>
</x-layouts.app>
