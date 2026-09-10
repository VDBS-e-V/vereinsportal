<div class="form__field">
    <label for="membership-starts-on">Beginn</label>
    <input class="form__control" id="membership-starts-on" name="starts_on" type="date" required value="{{ old('starts_on', $membership?->starts_on?->toDateString()) }}">
    @error('starts_on')
        <p class="form__error">{{ $message }}</p>
    @enderror
</div>

<div class="form__field">
    <label for="membership-ends-on">Ende</label>
    <input class="form__control" id="membership-ends-on" name="ends_on" type="date" value="{{ old('ends_on', $membership?->ends_on?->toDateString()) }}">
    <p class="form__hint">Leer lassen, solange kein Enddatum feststeht.</p>
    @error('ends_on')
        <p class="form__error">{{ $message }}</p>
    @enderror
</div>

<div class="form__actions">
    <button class="btn" type="submit">{{ $submitLabel }}</button>
    <a class="btn btn--quiet" href="{{ $cancelUrl }}">Abbrechen</a>
</div>
