@props([
    'name',
    'role' => null,
    'email' => null,
    'phone' => null,
    'address' => null,
])

<article
    {{ $attributes->class([
        'contact-block',
        'vdbs-contact-block',
    ]) }}
>
    <div class="contact-block__heading vdbs-contact-block__heading">
        <h3 class="contact-block__name vdbs-contact-block__name">
            {{ $name }}
        </h3>

        @if ($role !== null)
            <span class="contact-block__role vdbs-contact-block__role">
                {{ $role }}
            </span>
        @endif
    </div>

    <ul class="contact-block__details vdbs-contact-block__details">
        @if ($email !== null)
            <li><a href="mailto:{{ $email }}">{{ $email }}</a></li>
        @endif

        @if ($phone !== null)
            <li><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a></li>
        @endif

        @if ($address !== null)
            <li><address>{{ $address }}</address></li>
        @endif
    </ul>

    @isset($actions)
        <div class="contact-block__actions vdbs-contact-block__actions">
            {{ $actions }}
        </div>
    @endisset
</article>
