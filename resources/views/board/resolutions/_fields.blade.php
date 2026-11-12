<x-vdbs.forms.input name="title" label="Beschluss (Titel)" :required="true" maxlength="200" :value="old('title', $resolution?->title)" hint="Zum Beispiel „Haushaltsplan 2027 wird beschlossen“." />
<x-vdbs.forms.textarea name="text" label="Wortlaut" :optional="true" maxlength="10000" :rows="5" :value="old('text', $resolution?->text)" />
<x-vdbs.forms.select name="outcome" label="Ergebnis" :required="true" :options="$outcomes" :value="old('outcome', $resolution?->outcome ?? 'accepted')" />
<x-vdbs.forms.input name="votes_for" label="Stimmen dafür" type="number" :optional="true" min="0" max="999" :value="old('votes_for', $resolution?->votes_for)" />
<x-vdbs.forms.input name="votes_against" label="Stimmen dagegen" type="number" :optional="true" min="0" max="999" :value="old('votes_against', $resolution?->votes_against)" />
<x-vdbs.forms.input name="votes_abstain" label="Enthaltungen" type="number" :optional="true" min="0" max="999" :value="old('votes_abstain', $resolution?->votes_abstain)" />
