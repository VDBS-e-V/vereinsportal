@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Systemmails']];
@endphp

<x-layouts.app title="Systemmails" width="wide">
    <x-vdbs.layout.page-header title="Systemmails" lead="Die Texte aller automatischen E-Mails des Portals. Ohne Änderung gilt der Standardtext; Aussehen, Logo und Fußzeile kommen aus dem Mail-Design." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('mailbuilder.design.edit')">Mail-Design</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('mailbuilder.assets.index')">Bilder</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @foreach ($groups as $group => $mails)
        <x-vdbs.layout.section :title="$group">
            <x-vdbs.data.table :caption="$group" :columns="[['key' => 'name', 'label' => 'Mail'], ['key' => 'about', 'label' => 'Wann sie verschickt wird'], ['key' => 'state', 'label' => 'Stand']]">
                @foreach ($mails as $mail)
                    <tr>
                        <td><a href="{{ route('mailbuilder.system.edit', $mail->key) }}">{{ $mail->name }}</a>@if ($mail->security) <x-vdbs.data.badge tone="warning">Sicherheit</x-vdbs.data.badge>@endif</td>
                        <td>{{ $mail->description }}</td>
                        <td>
                            @if ($stored->has($mail->key))
                                <x-vdbs.data.badge tone="success">Geändert</x-vdbs.data.badge> {{ \Illuminate\Support\Carbon::parse($stored[$mail->key])->format('d.m.Y') }}
                            @else
                                <x-vdbs.data.badge>Standard</x-vdbs.data.badge>
                            @endif
                            @unless ($canEdit($mail)) <span class="mb__hint">nur lesen</span>@endunless
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </x-vdbs.layout.section>
    @endforeach
</x-layouts.app>
