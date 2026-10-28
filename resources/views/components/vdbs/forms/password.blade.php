@props([
    'name' => 'password',
    'label' => 'Passwort',
    'id' => null,
    'hint' => null,
    'error' => null,
    'success' => null,
    'required' => false,
    'optional' => false,
    'size' => 'm',
    'new' => false,
    'rules' => [],
    'labelHidden' => false,
])

{{--
    Passwortfeld mit Anzeigen/Verbergen (Paket "forms").
    new    true bei Registrierung/Passwortwechsel: autocomplete="new-password"; sonst "current-password"
    rules  Liste von Anforderungen, die unter dem Feld stehen und mit dem Feld verknüpft sind
    Der Umschalter braucht JavaScript und ist ohne JS ausgeblendet. Das Passwort wird nie vorbelegt.
--}}

@php
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $state = \App\Support\Forms\FieldState::make($name, $id, $hint, $error, $success, (bool) $required, (bool) $optional, $errors ?? null);
    $rulesId = $state->id.'-rules';
    $describedBy = $state->describedBy(...($rules ? [$rulesId] : []));
@endphp

<x-vdbs.forms.field :state="$state" :label="$label" :label-hidden="$labelHidden" {{ $attributes->only('class') }}>
    <div class="vdbs-password" data-vdbs-password>
        <input id="{{ $state->id }}" name="{{ $name }}" type="password"
            autocomplete="{{ $new ? 'new-password' : 'current-password' }}" autocapitalize="off" spellcheck="false"
            @if ($required) required @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($state->hasError()) aria-invalid="true" @endif
            {{ $attributes->except('class')->class(['vdbs-control', 'vdbs-control--password', "vdbs-control--size-{$size}", 'vdbs-control--error' => $state->hasError(), 'vdbs-control--success' => $state->hasSuccess()]) }}>
        <button type="button" class="vdbs-password__toggle vdbs-js-only" data-vdbs-password-toggle aria-controls="{{ $state->id }}" aria-pressed="false">
            <x-vdbs.icon class="vdbs-password__icon-show" name="eye" :size="20" />
            <x-vdbs.icon class="vdbs-password__icon-hide" name="eye-off" :size="20" />
            <span class="vdbs-sr-only" data-vdbs-password-label>Passwort anzeigen</span>
        </button>
    </div>

    @if ($rules)
        <ul class="vdbs-password__rules" id="{{ $rulesId }}">
            @foreach ($rules as $rule) <li>{{ $rule }}</li> @endforeach
        </ul>
    @endif
</x-vdbs.forms.field>
