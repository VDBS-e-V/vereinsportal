@php
    $messages = [
        'unknown' => ['Dieser Link gilt nicht', 'Bitte prüfen Sie den Link oder fragen Sie die Person, die ihn Ihnen gegeben hat.'],
        'closed' => ['Die Rückmeldung ist beendet', 'Dieser Link nimmt keine Antworten mehr an. Vielen Dank für Ihr Interesse.'],
        'pending' => ['Die Rückmeldung ist noch nicht offen', 'Bitte versuchen Sie es nach dem Seminar noch einmal mit diesem Link.'],
        'expired' => ['Die Rückmeldung ist abgelaufen', 'Die Frist für diesen Link ist vorbei. Vielen Dank für Ihr Interesse.'],
        'full' => ['Es sind schon genug Rückmeldungen eingegangen', 'Dieser Link nimmt keine weiteren Antworten an. Vielen Dank!'],
    ];
    [$title, $text] = $messages[$reason] ?? $messages['unknown'];
@endphp
<x-layouts.app :title="$title" width="narrow">
    <x-vdbs.layout.page-header :title="$title" :lead="$text" />
</x-layouts.app>
