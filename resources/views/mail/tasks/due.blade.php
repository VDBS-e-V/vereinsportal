<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    @if ($overdue->isNotEmpty())
        <p><strong>Überfällig:</strong></p>
        <ul>@foreach ($overdue as $task)<li><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a> (Projekt „{{ $task->project->name }}“, fällig war der {{ $task->due_on->format('d.m.Y') }})</li>@endforeach</ul>
    @endif
    @if ($soon->isNotEmpty())
        <p><strong>Bald fällig:</strong></p>
        <ul>@foreach ($soon as $task)<li><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a> (Projekt „{{ $task->project->name }}“, fällig am {{ $task->due_on->format('d.m.Y') }})</li>@endforeach</ul>
    @endif
    <p><a href="{{ $url }}">Meine Aufgaben ansehen</a></p>
    <p style="font-size: 13px; color: #475569;">Sie erhalten diese Erinnerung, weil Sie sie in Ihrem Konto unter „Benachrichtigungen“ eingeschaltet haben, und zwar einmal je Aufgabe. Dort können Sie sie jederzeit wieder ausschalten.</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
