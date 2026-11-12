<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\SeminarApplication;
use Illuminate\Console\Command;

/**
 * Datenschutz bei Bewerbungen über den persönlichen Zugang und den Bewerbungslink:
 * unbestätigte Bewerbungen werden nach 7 Tagen gelöscht, zurückgezogene nach 30 Tagen anonymisiert,
 * alle übrigen sechs Monate nach dem letzten Termin (oder der Absage) anonymisiert. Anonymisiert heißt: Name, Telefon, Geburtsjahr,
 * Kompetenzen, Erfahrung und Notizen werden entfernt, die Bewerbung bleibt als Zahl erhalten. Kontakte, die nur über den Bewerbungslink kamen
 * und keine Bewerbung mehr haben, werden gelöscht.
 */
class PruneApplicants extends Command
{
    protected $signature = 'seminars:prune-applicants {--dry-run : Nur zählen, nichts ändern}';

    protected $description = 'Löscht oder anonymisiert Bewerbungen ohne Konto nach den Aufbewahrungsfristen.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $months = max(1, (int) config('portal.contacts.applicant_retention_months', 6));
        $cut = now()->subMonths($months);

        $unconfirmed = SeminarApplication::query()->whereNotNull('contact_id')->whereNull('confirmed_at')->where('created_at', '<', now()->subDays(7));
        $withdrawn = SeminarApplication::query()->whereNotNull('contact_id')->where('status', ApplicationStatus::Withdrawn->value)->where('updated_at', '<', now()->subDays(30));
        $finished = SeminarApplication::query()->whereNotNull('contact_id')->whereHas('seminar', fn ($seminar) => $seminar->where(fn ($group) => $group
            ->where(fn ($inner) => $inner->whereHas('sessions')->whereDoesntHave('sessions', fn ($sessions) => $sessions->where('ends_at', '>=', $cut)))
            ->orWhere(fn ($inner) => $inner->where('status', SeminarStatus::Cancelled->value)->where('updated_at', '<', $cut))));

        $counts = ['deleted' => (clone $unconfirmed)->count(), 'withdrawn' => (clone $withdrawn)->count(), 'finished' => (clone $finished)->count()];

        if (! $dry) {
            $unconfirmed->delete();

            foreach ([$withdrawn, $finished] as $query) {
                $query->update(['contact_id' => null, 'guest_name' => null, 'guest_phone' => null, 'guest_birth_year' => null, 'guest_competences' => null, 'guest_experience' => null, 'guest_consent_contact' => false, 'note' => null, 'internal_note' => null]);
            }

            TeamContact::query()->where('origin', 'link')->whereDoesntHave('credentials')->whereNotIn('id', SeminarApplication::query()->whereNotNull('contact_id')->select('contact_id'))->delete();
        }

        $this->components->info(($dry ? 'Probelauf: ' : '').$counts['deleted'].' unbestätigte gelöscht, '.$counts['withdrawn'].' zurückgezogene und '.$counts['finished'].' abgeschlossene anonymisiert.');

        return self::SUCCESS;
    }
}
