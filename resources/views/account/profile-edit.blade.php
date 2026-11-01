<x-account.shell title="Profil bearbeiten" current="profile-edit" lead="Diese Angaben können andere sehen, sobald Sie sie unter Sichtbarkeit freigeben. Bis dahin sind sie nur für Sie sichtbar.">
    <x-vdbs.forms.form :action="route('account.profile.update')" method="put" :files="true">
        <x-vdbs.forms.form-section title="Angaben">
            <x-vdbs.forms.input name="display_name" label="Anzeigename" :optional="true" maxlength="80" :value="old('display_name', $profile->display_name)" :hint="'Ohne Eingabe verwenden wir: '.$defaultName" />
            <x-vdbs.forms.input name="headline" label="Funktion / Schwerpunkt" :optional="true" maxlength="120" :value="old('headline', $profile->headline)" hint="z. B. Seminarleitung, Schwerpunkt Schülervertretung" />
            <x-vdbs.forms.textarea name="bio" label="Kurzvorstellung" :optional="true" :rows="8" :rich="true" :value="old('bio', $bio)" hint="Höchstens 1 500 Zeichen." />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Foto">
            <div style="display:flex;gap:var(--space-4);align-items:center">
                <x-vdbs.data.avatar :name="$profile->display_name ?: $defaultName" :src="$user->avatarUrl()" size="l" />
                <p style="margin:0">Das Foto wird mittig quadratisch zugeschnitten und verkleinert. Standort- und Kameradaten werden dabei entfernt.</p>
            </div>
            <x-vdbs.forms.file name="photo" :label="filled($user->avatar_path) ? 'Neues Foto' : 'Foto'" accept=".jpg,.jpeg,.png,.webp" :optional="true" hint="JPEG, PNG oder WebP, mindestens 200 × 200 Pixel, höchstens 4 MB" />
            @if (filled($user->avatar_path))
                <x-vdbs.forms.checkbox name="remove_photo" label="Foto entfernen" />
            @endif
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Angaben als Autor:in">
            <p style="margin:0">Wenn die Geschäftsstelle einen von Ihnen angelegten Artikel mit Autorenangabe veröffentlicht, nimmt das Portal diese Angaben. Sie gelten unabhängig von der Sichtbarkeit Ihres Profils und erscheinen nur dort, wo ein Artikel Sie als Autor:in nennt. Im Artikel selbst können sie einzeln überschrieben werden.</p>
            <x-vdbs.forms.input name="author_name" label="Name als Autor:in" :optional="true" maxlength="120" :value="old('author_name', $profile->author_name)" :hint="'Ohne Eingabe verwenden wir: '.$defaultName" />
            <x-vdbs.forms.textarea name="author_text" label="Text „Über die Autor:in“" :optional="true" :rows="6" :rich="true" :value="old('author_text', $authorText)" hint="Kurze Vorstellung unter dem Artikel" />
            <x-vdbs.forms.checkbox name="author_use_photo" label="Mein Profilfoto im Kasten „Über die Autor:in“ zeigen" :checked="(bool) old('author_use_photo', $profile->author_use_photo)" />
            <x-vdbs.forms.input name="author_photo_credit" label="Bildquelle des Fotos" :optional="true" maxlength="200" :value="old('author_photo_credit', $profile->author_photo_credit)" hint="Ohne Eingabe steht dort „Privat“" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('account.profile')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-account.shell>
