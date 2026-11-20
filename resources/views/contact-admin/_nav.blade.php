@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Kontaktformular']];
@endphp
<nav aria-label="Kontaktformular verwalten" style="display:flex;gap:var(--space-4);flex-wrap:wrap;margin-bottom:var(--space-5)">
    <x-vdbs.actions.button :href="route('contact-admin.topics.index')" :variant="($active ?? '') === 'topics' ? 'primary' : 'secondary'" size="s">Themen</x-vdbs.actions.button>
    <x-vdbs.actions.button :href="route('contact-admin.forms.index')" :variant="($active ?? '') === 'forms' ? 'primary' : 'secondary'" size="s">Formulare</x-vdbs.actions.button>
    <x-vdbs.actions.button :href="route('portal.contact')" variant="text" size="s" icon="external-link">Öffentliche Seite ansehen</x-vdbs.actions.button>
</nav>
