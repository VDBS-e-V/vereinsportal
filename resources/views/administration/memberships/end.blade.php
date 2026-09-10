@extends('layouts.administration')

@section('title', 'Mitgliedschaft beenden')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title"><p class="page-title__kicker">Mitgliedschaften</p><h1 class="page-title__title">Mitgliedschaft von {{ $displayName }} beenden</h1><p class="page-title__lead">Das Enddatum bleibt als Teil des historischen Mitgliedschaftszeitraums erhalten.</p></header>
        @if ($membership->ends_on !== null)<x-vdbs.notice type="warning" role="alert">Für diese Mitgliedschaft ist bereits ein Enddatum hinterlegt. Ändern Sie den Zeitraum über „Bearbeiten“.</x-vdbs.notice>@endif
        @if ($errors->any())<x-vdbs.validation-summary role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</x-vdbs.validation-summary>@endif
        <form class="form" method="POST" action="{{ route('administration.memberships.end.store', $membership) }}">@csrf<div class="form__field"><label for="membership-end-date">Enddatum</label><input class="form__control" id="membership-end-date" name="ends_on" type="date" required value="{{ old('ends_on', now()->toDateString()) }}">@error('ends_on')<p class="form__error">{{ $message }}</p>@enderror</div><div class="form__field"><label for="membership-end-reason">Begründung</label><textarea class="form__control" id="membership-end-reason" name="reason" rows="4" required maxlength="1000">{{ old('reason') }}</textarea><p class="form__hint">Die Begründung wird im Audit-Ereignis gespeichert.</p>@error('reason')<p class="form__error">{{ $message }}</p>@enderror</div><div class="form__actions"><button class="btn" type="submit" @disabled($membership->ends_on !== null)>Enddatum speichern</button><a class="btn btn--quiet" href="{{ route('administration.memberships.show', $membership) }}">Abbrechen</a></div></form>
    </div>
@endsection
