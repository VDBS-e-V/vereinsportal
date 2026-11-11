<?php

namespace App\Modules\Feedback\Support;

/**
 * Die mitgelieferten Standardformulare (Entwurf zur Freigabe durch die Koordination). Fragetypen: scale (1 bis 5), yesno, choice, multi, text, name.
 * „name“ ist ein freiwilliges Namensfeld und wird nicht zu den Antworten gezählt.
 */
final class FeedbackDefaults
{
    public const TYPES = ['scale' => 'Skala 1 bis 5', 'yesno' => 'Ja oder Nein', 'choice' => 'Auswahl (eine Antwort)', 'multi' => 'Mehrfachauswahl', 'text' => 'Freitext', 'name' => 'Name (freiwillig)'];

    public const SCALE_LABELS = ['trifft gar nicht zu', 'trifft eher nicht zu', 'teils, teils', 'trifft eher zu', 'trifft voll zu'];

    /** @return array<string, array{key: string, audience: string, title: string, intro: string, questions: list<array<string, mixed>>}> */
    public static function forms(): array
    {
        return [
            'students' => [
                'key' => 'students', 'audience' => 'students', 'title' => 'Rückmeldung zum Seminar',
                'intro' => 'Danke, dass du mitmachst. Du gibst keinen Namen an. Niemand kann sehen, wer was geschrieben hat. Du brauchst etwa 3 Minuten.',
                'questions' => [
                    self::scale('q1', 'Wie hat dir das Seminar insgesamt gefallen?', true, ['gar nicht', 'nicht so', 'geht so', 'gut', 'sehr gut'], true),
                    self::scale('q2', 'Ich habe etwas Neues gelernt.'),
                    self::scale('q3', 'Ich konnte meine Meinung sagen und wurde gehört.'),
                    self::scale('q4', 'Die Teamerinnen und Teamer haben alles gut erklärt.'),
                    self::scale('q5', 'Die Aufgaben und Übungen waren verständlich.'),
                    self::choice('q6', 'Das Tempo war …', ['zu langsam', 'genau richtig', 'zu schnell']),
                    self::text('q7', 'Was hat dir am besten gefallen?', 'Bitte schreibe keine Namen.'),
                    self::text('q8', 'Was sollten wir beim nächsten Mal anders machen?', 'Bitte schreibe keine Namen.'),
                    self::yesno('q9', 'Würdest du anderen Klassen das Seminar empfehlen?'),
                    self::choice('q10', 'Welche Klassenstufe besuchst du?', ['5 bis 6', '7 bis 8', '9 bis 10', '11 bis 13'], false, 'Das hilft uns bei der Auswertung.'),
                ],
            ],
            'teachers' => [
                'key' => 'teachers', 'audience' => 'teachers', 'title' => 'Rückmeldung der Lehrkräfte zum Seminar',
                'intro' => 'Ihre Antworten sind anonym und helfen uns, die Seminare zu verbessern. Dauer etwa 5 Minuten.',
                'questions' => [
                    self::scale('q1', 'Wie zufrieden sind Sie insgesamt mit dem Seminar?', true, ['gar nicht zufrieden', 'eher unzufrieden', 'teils, teils', 'eher zufrieden', 'sehr zufrieden']),
                    self::scale('q2', 'Die Ziele des Seminars wurden erreicht.'),
                    self::scale('q3', 'Der Ablauf war gut abgestimmt und pünktlich.'),
                    self::scale('q4', 'Die Kommunikation vor dem Seminar (Anfrage, Terminabsprache, Kostenvoranschlag) war klar.'),
                    self::scale('q5', 'Die Teamenden waren fachlich und pädagogisch überzeugend.'),
                    self::scale('q6', 'Die Schülerinnen und Schüler haben sich aktiv beteiligt.'),
                    self::scale('q7', 'Der Aufwand für die Schule war angemessen.'),
                    self::choice('q8', 'Wie ist das Verhältnis von Preis und Leistung?', ['zu hoch', 'angemessen', 'günstig']),
                    self::choice('q9', 'Würden Sie ein weiteres Seminar buchen?', ['Ja', 'Vielleicht', 'Nein']),
                    ['id' => 'q10', 'type' => 'multi', 'text' => 'Welche Themen wünschen Sie sich in Zukunft?', 'hint' => 'Mehrere Antworten sind möglich.', 'required' => false, 'options' => ['Klassenrat', 'SV-Arbeit', 'Schülervertretungswahl', 'Konflikte', 'Beteiligung', 'Medien', 'Sonstiges']],
                    self::text('q11', 'Was hat gut funktioniert?', 'Bitte nennen Sie keine Namen von Schülerinnen und Schülern.'),
                    self::text('q12', 'Was sollten wir ändern?', 'Bitte nennen Sie keine Namen von Schülerinnen und Schülern.'),
                ],
            ],
            'team' => [
                'key' => 'team', 'audience' => 'team', 'title' => 'Rückmeldung der Teamenden zum Seminar',
                'intro' => 'Danke für deinen Einsatz. Mit deinen Antworten verbessern wir Vorbereitung und Begleitung. Du kannst anonym bleiben.',
                'questions' => [
                    self::scale('q1', 'Wie gut war die Vorbereitung (Infos, Materialien, Absprachen)?', true, ['gar nicht gut', 'eher schlecht', 'teils, teils', 'eher gut', 'sehr gut']),
                    self::scale('q2', 'Die Rollen im Team waren klar verteilt.'),
                    self::scale('q3', 'Die Zusammenarbeit im Team war gut.'),
                    self::scale('q4', 'Die Gruppe war interessiert und offen.'),
                    self::scale('q5', 'Die Rahmenbedingungen an der Schule waren gut (Raum, Technik, Zeit).'),
                    self::scale('q6', 'Die Begleitung durch die Koordination war hilfreich.'),
                    self::choice('q7', 'Die Zeit hat gereicht.', ['zu knapp', 'passend', 'zu viel']),
                    self::yesno('q8', 'Gab es Vorfälle, über die wir sprechen sollten?', false, 'Bei Vorfällen melde dich bitte zusätzlich direkt bei der Koordination.'),
                    self::text('q9', 'Wenn ja: Was ist passiert?', 'Bitte nenne keine Namen von Schülerinnen und Schülern.'),
                    self::text('q10', 'Was hat gut funktioniert?'),
                    self::text('q11', 'Was braucht das nächste Team von uns?'),
                    ['id' => 'q12', 'type' => 'name', 'text' => 'Dein Name (freiwillig)', 'hint' => 'Nur ausfüllen, wenn du namentlich antworten möchtest.', 'required' => false],
                ],
            ],
        ];
    }

    /** @param  list<string>|null  $labels */
    private static function scale(string $id, string $text, bool $required = true, ?array $labels = null, bool $smileys = false): array
    {
        return ['id' => $id, 'type' => 'scale', 'text' => $text, 'hint' => '', 'required' => $required, 'labels' => $labels ?? self::SCALE_LABELS, 'smileys' => $smileys];
    }

    private static function yesno(string $id, string $text, bool $required = true, string $hint = ''): array
    {
        return ['id' => $id, 'type' => 'yesno', 'text' => $text, 'hint' => $hint, 'required' => $required];
    }

    /** @param  list<string>  $options */
    private static function choice(string $id, string $text, array $options, bool $required = true, string $hint = ''): array
    {
        return ['id' => $id, 'type' => 'choice', 'text' => $text, 'hint' => $hint, 'required' => $required, 'options' => $options];
    }

    private static function text(string $id, string $text, string $hint = ''): array
    {
        return ['id' => $id, 'type' => 'text', 'text' => $text, 'hint' => $hint, 'required' => false];
    }
}
