<x-layouts.app :title="$entry['title']" :width="$entry['width']">
    @if ($entry['width'] === 'full')
        <x-vdbs.layout.container>
            @include('design-library._header')
        </x-vdbs.layout.container>
    @else
        @include('design-library._header')
    @endif

    @include($entry['view'])
</x-layouts.app>
