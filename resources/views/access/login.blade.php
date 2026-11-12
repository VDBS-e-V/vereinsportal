@php
    $image = config('portal.login_image');
@endphp

@if ($image)
    <x-layouts.app title="Anmelden" width="wide">
        <x-vdbs.layout.aside-layout variant="image" size="half" main-width="narrow">
            @include('access._login-form')

            <x-slot:aside>
                <x-vdbs.media.image :src="$image['src']" :alt="$image['alt'] ?? ''" :credit="$image['credit'] ?? null" credit-placement="edge" loading="eager" />
            </x-slot:aside>
        </x-vdbs.layout.aside-layout>
    </x-layouts.app>
@else
    <x-layouts.app title="Anmelden" width="narrow">
        @include('access._login-form')
    </x-layouts.app>
@endif
