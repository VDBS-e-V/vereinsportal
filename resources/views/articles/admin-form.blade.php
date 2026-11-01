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
                <x-vdbs.actions.button variant="danger" icon="trash-2" data-vdbs-dialog-open="delete-article">Löschen</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.form :action="$editing ? route('articles.admin.update', $article) : route('articles.admin.store')" :method="$editing ? 'put' : 'post'" :files="true">
        <x-vdbs.forms.form-section title="Artikel">
            <x-vdbs.forms.input name="title" label="Titel" :required="true" maxlength="200" :value="$values['title'] ?? null" />
            <x-vdbs.forms.input name="subtitle" label="Untertitel" :optional="true" maxlength="250" :value="$values['subtitle'] ?? null" hint="Steht unter dem Titel, z. B. Anlass, Ort oder Datum einer Veranstaltung" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="category" label="Kategorie" :required="true" :options="\App\Modules\Articles\Models\Article::categoryOptions($values['category'] ?? null)" :value="$values['category'] ?? 'Themenartikel'" hint="Steht auf der Karte und über dem Titel; führt zur Artikelliste der Kategorie" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.textarea name="teaser" label="Anreißer" :optional="true" maxlength="300" :counter="true" :rows="2" :value="$values['teaser'] ?? null" hint="Kurzer Text für die Karte; ohne Angabe nimmt das Portal den Anfang des Artikels" />
            <x-vdbs.forms.input name="tags" label="Schlagwörter" :optional="true" maxlength="500" :value="$values['tags'] ?? null" hint="Durch Komma getrennt, höchstens zehn, z. B. Demokratie, Berlin, Exkursion" />
            <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="60000" :rows="14" :rich="true" :value="$values['body'] ?? null" />
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

        <x-vdbs.forms.form-section title="Autor:in">
            <x-vdbs.forms.checkbox name="show_author" label="Autor:in oben beim Artikel nennen" :checked="(bool) ($values['show_author'] ?? false)" />
            <x-vdbs.forms.input name="author_name" label="Name der Autorin oder des Autors" :optional="true" maxlength="120" :value="$values['author_name'] ?? null" hint="Ohne Angabe nimmt das Portal den Namen der Person, die den Artikel angelegt hat" />
            <x-vdbs.forms.checkbox name="author_box" label="Kasten „Über die Autor:in“ unter dem Artikel zeigen" :checked="(bool) ($values['author_box'] ?? false)" />
            <x-vdbs.forms.textarea name="author_text" label="Text über die Autorin oder den Autor" :optional="true" maxlength="5000" :rows="5" :rich="true" :value="$values['author_text'] ?? null" />
            @if ($editing && filled($article->author_image_path))
                <p>Aktuelles Foto: <a href="{{ route('articles.public.author-image', $article->slug) }}">ansehen</a></p>
                <x-vdbs.forms.checkbox name="remove_author_image" label="Foto entfernen" />
            @endif
            <x-vdbs.forms.file name="author_image" :label="$editing && filled($article->author_image_path) ? 'Neues Foto' : 'Foto'" accept=".jpg,.jpeg,.png,.webp" :optional="true" hint="Ohne Foto erscheint der Kasten nur mit Text" />
            <x-vdbs.forms.input name="author_image_credit" label="Bildquelle des Fotos" :optional="true" maxlength="200" :value="$values['author_image_credit'] ?? null" hint="Pflicht mit Foto" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Weitere Informationen">
            <x-vdbs.forms.textarea name="more_info" label="Kasten „Weitere Informationen“" :optional="true" maxlength="5000" :rows="5" :rich="true" :value="$values['more_info'] ?? null" hint="Erscheint unter dem Artikel, z. B. Hinweise zu einer Reihe oder weiterführende Links" />
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
            <x-vdbs.actions.button variant="text" :href="route('articles.admin.index')">Zurück zur Liste</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing)
        <x-vdbs.overlays.confirm-dialog id="delete-article" title="Artikel löschen?" variant="danger" :action="route('articles.admin.destroy', $article)" method="delete" confirm-label="Artikel löschen">
            <p>Der Artikel <strong>{{ $article->title }}</strong> wird mit seinem Bild dauerhaft gelöscht.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
