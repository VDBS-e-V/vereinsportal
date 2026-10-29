@props(['text'])

{{--
    Text, der beim Anklicken in die Zwischenablage kopiert wird (Paket "data"), z. B. eine Anschrift.
    Ohne JavaScript steht der Text normal da; mit JavaScript wird er zum Knopf und meldet „Kopiert“.
--}}

<span class="vdbs-no-js">{{ $text }}</span>
<button type="button" class="vdbs-copy vdbs-js-only" data-vdbs-copy="{{ $text }}" title="Zum Kopieren anklicken">{{ $text }}<x-vdbs.icon name="copy" :size="16" /></button>
