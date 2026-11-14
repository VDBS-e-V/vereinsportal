@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Kontakt', 'href' => route('portal.contact')], ['label' => $topic->title]];
@endphp

<x-layouts.app :title="$topic->title" width="medium">
    <x-vdbs.layout.page-header :title="$topic->title" :lead="$topic->description" :breadcrumb="$crumbs" />

    @if ($topic->faqs->isEmpty())
        <p>Zu diesem Thema gibt es noch keine Fragen und Antworten.</p>
    @else
        <x-vdbs.disclosure.accordion>
            @foreach ($topic->faqs as $faq)
                <x-vdbs.disclosure.accordion-item :title="$faq->question" :level="2">
                    {{ \App\Modules\ContactForms\Support\FaqText::html($faq->answer) }}
                </x-vdbs.disclosure.accordion-item>
            @endforeach
        </x-vdbs.disclosure.accordion>
    @endif

    <div style="margin-top:var(--space-6)">
        @if ($follow)
            <p><strong>Ihre Frage ist nicht dabei?</strong></p>
            <x-vdbs.actions.button :href="route('portal.contact.form', ['form' => $follow->slug, 'thema' => $topic->id])" icon="mail">Uns schreiben</x-vdbs.actions.button>
        @endif
        <x-vdbs.actions.button :href="route('portal.contact')" variant="text" icon="arrow-left">Zurück zur Themenwahl</x-vdbs.actions.button>
    </div>
</x-layouts.app>
