<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    <p>{{ $by }} hat Ihnen im Vereinsportal {{ $tasks->count() === 1 ? 'eine Aufgabe' : $tasks->count().' Aufgaben' }} zugewiesen:</p>
    <ul>
        @foreach ($tasks as $task)
            <li><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a> (Projekt „{{ $task->project->name }}“{{ $task->due_on ? ', fällig am '.$task->due_on->format('d.m.Y') : '' }})</li>
        @endforeach
    </ul>
    <p><a href="{{ $url }}">Meine Aufgaben ansehen</a></p>
    <p style="font-size: 13px; color: #475569;">Sie erhalten diese Nachricht, weil Sie sie in Ihrem Konto unter „Benachrichtigungen“ eingeschaltet haben. Dort können Sie sie jederzeit wieder ausschalten.</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
