@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen', 'href' => route('school.home')], ['label' => 'Seminare', 'href' => route('school.seminars.index')], ['label' => $seminar->number]];
@endphp

<x-layouts.app :title="$seminar->title" width="wide">
    <x-vdbs.layout.page-header :title="$seminar->title" :lead="$seminar->number" :breadcrumb="$crumbs" />

    <p><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></p>

    <x-vdbs.data.detail-list :columns="2">
        <x-vdbs.data.detail-item label="Format">{{ $seminar->format }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Teilnehmende">{{ $seminar->participants }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Gewünschter Zeitraum">{{ $seminar->desired_period }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Ansprechperson">{{ $seminar->contact_name }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="E-Mail-Adresse">{{ $seminar->contact_email }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Anmerkungen">{{ $seminar->request_note }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    @if ($seminar->sessions->isNotEmpty())
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Termine</h2>
        <ul>
            @foreach ($seminar->sessions as $session)
                <li>{{ $session->starts_at->format('d.m.Y, H:i') }} Uhr@if (filled($session->location)), {{ $session->location }}@endif</li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
