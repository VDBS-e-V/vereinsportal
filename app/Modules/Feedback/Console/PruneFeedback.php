<?php

namespace App\Modules\Feedback\Console;

use App\Modules\Feedback\Services\FeedbackEvaluation;
use Illuminate\Console\Command;

/** Anonymisiert Feedbacklinks nach Ablauf der Aufbewahrung: Zahlen ins Archiv (bei genug Antworten), Einzelantworten, Freitexte und Namen werden gelöscht. Nennt nur Zahlen. */
class PruneFeedback extends Command
{
    protected $signature = 'feedback:prune {--dry-run : Nur zählen, nichts ändern}';

    protected $description = 'Anonymisiert Feedbacklinks nach Ablauf der Aufbewahrungsfrist.';

    public function handle(FeedbackEvaluation $evaluation): int
    {
        $due = $evaluation->due();
        $archived = 0;
        $deleted = 0;

        if (! $this->option('dry-run')) {
            foreach ($due as $link) {
                $evaluation->anonymize($link) === 'archived' ? $archived++ : $deleted++;
            }
        }

        $this->components->info($this->option('dry-run')
            ? 'Probelauf: '.$due->count().' Link(s) würden anonymisiert.'
            : $due->count().' Link(s) anonymisiert ('.$archived.' mit Archiv, '.$deleted.' ohne, weil zu wenige Antworten).');

        return self::SUCCESS;
    }
}
