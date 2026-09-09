@props([
    'title' => 'Beispiel',
    'code',
    'language' => 'Blade',
    'compact' => false,
])

<div
    {{ $attributes->class([
        'code-example',
        'vdbs-code-example',
        'code-example--compact' => $compact,
        'vdbs-code-example--compact' => $compact,
    ]) }}
    data-vdbs-code-example
>
    <div class="code-example__header vdbs-code-example__header">
        <p class="code-example__title vdbs-code-example__title">
            {{ $title }}
        </p>

        <button
            class="btn btn--secondary btn--sm code-example__copy vdbs-code-example__copy"
            type="button"
            data-vdbs-copy-code
        >
            <x-vdbs.icon name="copy" size="17" />
            <span data-vdbs-copy-label>Code kopieren</span>
        </button>
    </div>

    @if (trim((string) $slot) !== '')
        <div class="code-example__preview vdbs-code-example__preview">
            {{ $slot }}
        </div>
    @endif

    <div class="code-example__source vdbs-code-example__source">
        <span class="code-example__language vdbs-code-example__language">
            {{ $language }}
        </span>

        <pre class="code-example__code vdbs-code-example__code"><code data-vdbs-code-source>{{ $code }}</code></pre>

        <span
            class="code-example__status vdbs-code-example__status"
            data-vdbs-copy-status
            role="status"
            aria-live="polite"
        ></span>
    </div>
</div>
