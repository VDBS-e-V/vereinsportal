@php
    $editing = $article !== null;
    $heading = $editing ? 'Artikel bearbeiten' : 'Artikel schreiben';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Artikel', 'href' => route('articles.admin.index')], ['label' => $editing ? $article->title : 'Neu']];
@endphp

<x-layouts.app :title="$heading" width="medium">
    <x-vdbs.layout.page-header :title="$heading" :breadcrumb="$crumbs">
        @if ($editing)
            <x-slot:actions>
                <x-vdbs.actions.button variant="secondary" :href="route('articles.public.show', $article->slug)" icon="eye">Ansehen</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="danger" shape="outline" icon="trash-2" data-vdbs-dialog-open="delete-article">Löschen</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.form :action="$editing ? route('articles.admin.update', $article) : route('articles.admin.store')" :method="$editing ? 'put' : 'post'" :files="true">
        <x-vdbs.forms.form-section title="Artikel">
            <x-vdbs.forms.input name="title" label="Titel" :required="true" maxlength="200" :value="$values['title'] ?? null" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="category" label="Kategorie" :optional="true" maxlength="60" :value="$values['category'] ?? null" hint="Steht auf der Karte, z. B. Themenartikel" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.textarea name="teaser" label="Anreißer" :optional="true" maxlength="300" :counter="true" :rows="2" :value="$values['teaser'] ?? null" hint="Kurzer Text für die Karte; ohne Angabe nimmt das Portal den Anfang des Artikels" />
            <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="20000" :rows="14" :value="$values['body'] ?? null" hint="Einfaches Markdown: Leerzeile = neuer Absatz, ## Überschrift, **fett**, *kursiv*, - Liste, [Text](https://…)" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Bild">
            @if ($editing && filled($article->image_path))
                <p>Aktuelles Bild: <a href="{{ route('articles.public.image', $article->slug) }}">ansehen</a></p>
                <x-vdbs.forms.checkbox name="remove_image" label="Bild entfernen" />
            @endif
            <x-vdbs.forms.file name="image" :label="$editing && filled($article->image_path) ? 'Neues Bild' : 'Bild'" accept=".jpg,.jpeg,.png,.webp" :optional="true" hint="JPEG, PNG oder WebP, höchstens 4 MB" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="image_alt" label="Alternativtext" :optional="true" maxlength="200" :value="$values['image_alt'] ?? null" hint="Beschreibt das Bild für Screenreader (Pflicht mit Bild)" />
                <x-vdbs.forms.input name="image_credit" label="Bildquelle" :optional="true" maxlength="200" :value="$values['image_credit'] ?? null" hint="z. B. Name der Fotografin, Lizenz (Pflicht mit Bild)" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Veröffentlichung">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="status" label="Status" :required="true" :options="['draft' => 'Entwurf', 'published' => 'Veröffentlicht']" :value="$values['status'] ?? 'draft'" />
                <x-vdbs.forms.input name="published_at" label="Veröffentlichen ab" type="datetime-local" :optional="true" :value="$values['published_at'] ?? null" hint="Ein Datum in der Zukunft plant den Artikel" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.checkbox name="featured" label="Auf der Startseite empfehlen" :checked="(bool) ($values['featured'] ?? false)" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('articles.admin.index')">Zurück zur Liste</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing)
        <x-vdbs.overlays.confirm-dialog id="delete-article" title="Artikel löschen?" variant="danger" :action="route('articles.admin.destroy', $article)" method="delete" confirm-label="Artikel löschen">
            <p>Der Artikel <strong>{{ $article->title }}</strong> wird mit seinem Bild dauerhaft gelöscht.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
