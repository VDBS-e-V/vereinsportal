<x-account.shell title="Mein Profil" current="profile" lead="So erscheint Ihr Profil für andere. Bearbeiten und Sichtbarkeit finden Sie im Menü Profil.">
    <x-slot:meta><x-vdbs.data.badge tone="neutral">{{ $completeness }} % vollständig</x-vdbs.data.badge></x-slot:meta>

    @php
        $views = [
            ['title' => 'Für Sie selbst', 'hint' => 'Ihre vollständige Ansicht.', 'shown' => $mine],
            ['title' => 'Für angemeldete Portalnutzer', 'hint' => 'So sehen es Personen mit einem Konto im Portal.', 'shown' => $asPortal],
            ['title' => 'Für die Öffentlichkeit', 'hint' => 'So sieht es jeder ohne Anmeldung.', 'shown' => $asPublic],
        ];
    @endphp

    <x-vdbs.layout.grid :columns="3">
        @foreach ($views as $view)
            <section aria-labelledby="view-{{ $loop->index }}-heading" class="vdbs-profile-card">
                <h2 id="view-{{ $loop->index }}-heading" style="font-size:var(--font-size-h4)">{{ $view['title'] }}</h2>
                <p style="color:var(--text-muted)">{{ $view['hint'] }}</p>

                @if ($view['shown'] === [])
                    <p>Hier ist nichts sichtbar.</p>
                @else
                    <div class="vdbs-profile-card__head">
                        @if (isset($view['shown']['photo']))
                            <x-vdbs.data.avatar :name="$view['shown']['name'] ?? 'Profilfoto'" :src="$view['shown']['photo']" size="l" />
                        @elseif (isset($view['shown']['name']))
                            <x-vdbs.data.avatar :name="$view['shown']['name']" size="l" />
                        @endif
                        <div>
                            @if (isset($view['shown']['name']))<p class="vdbs-profile-card__name">{{ $view['shown']['name'] }}</p>@endif
                            @if (isset($view['shown']['headline']))<p>{{ $view['shown']['headline'] }}</p>@endif
                        </div>
                    </div>
                    @if (isset($view['shown']['bio']))<div class="vdbs-prose">{!! rich_text($view['shown']['bio']) !!}</div>@endif
                    @if (isset($view['shown']['email']) || isset($view['shown']['phone']))
                        <p>
                            @if (isset($view['shown']['email']))E-Mail: {{ $view['shown']['email'] }}<br>@endif
                            @if (isset($view['shown']['phone']))Telefon: {{ $view['shown']['phone'] }}@endif
                        </p>
                    @endif
                @endif
            </section>
        @endforeach
    </x-vdbs.layout.grid>

    <x-vdbs.actions.button-group>
        <x-vdbs.actions.button :href="route('account.profile.edit')" icon="pencil">Profil bearbeiten</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="secondary" :href="route('account.profile.visibility')" icon="eye">Sichtbarkeit einstellen</x-vdbs.actions.button>
    </x-vdbs.actions.button-group>
</x-account.shell>
