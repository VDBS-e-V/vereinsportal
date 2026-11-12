<?php

namespace App\Modules\Letters\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Contracts\Services\Contracts;
use App\Modules\Contracts\Services\ContractSettings;
use App\Modules\Letters\Models\LetterTemplate;
use App\Modules\Letters\Support\LetterVariables;
use App\Modules\Partners\Enums\FederalState;
use App\Modules\Partners\Enums\SchoolType;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Support\Money;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipArchive;

/** Füllt Word-Vorlagen mit den Angaben zu Seminar, Schule oder Person. Pro Empfänger entsteht eine Word-Datei, bei mehreren eine ZIP-Datei. Nichts wird gespeichert. */
final class LetterGenerator
{
    public function __construct(private readonly LetterTemplates $templates, private readonly ContractSettings $settings) {}

    /** Bestätigte Bewerbungen eines Seminars mit Namen für die Auswahl. @return list<SeminarApplication> */
    public function recipients(Seminar $seminar): array
    {
        return $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->with(['user.person', 'contact'])->orderBy('id')->get()->all();
    }

    /**
     * Erzeugt die Schreiben. Gibt den Pfad der Datei (docx oder zip) und den Dateinamen zurück; die Datei liegt im temporären Ordner.
     *
     * @param  list<int>  $applicationIds  Bewerbungen (nur bei $to = team)
     * @return array{path: string, name: string, count: int}
     *
     * @throws DomainException
     */
    public function generate(LetterTemplate $template, Seminar $seminar, string $to, array $applicationIds, ?string $subject, ?string $freitext, User $by): array
    {
        $seminar->loadMissing(['partner', 'sessions']);
        $base = $this->baseValues($by, $subject, $freitext) + $this->seminarValues($seminar);
        $jobs = [];

        if ($to === 'school') {
            if ($seminar->partner === null) {
                throw new DomainException('Das Seminar hat keine Schule.');
            }

            $jobs[] = ['label' => $seminar->partner->name, 'values' => ['empfaenger_ist_schule' => '1', 'empfaenger_ist_person' => ''] + $this->schoolValues($seminar->partner) + $base];
        } else {
            $applications = collect($this->recipients($seminar))->when($applicationIds !== [], fn ($list) => $list->filter(fn (SeminarApplication $application): bool => in_array($application->id, $applicationIds, true)));

            if ($applications->isEmpty()) {
                throw new DomainException('Bitte wählen Sie mindestens eine Person aus dem bestätigten Team.');
            }

            // Die Schule des Seminars steht auch in Schreiben an Teamende zur Verfügung (schule_name, schule_anschrift, ansprechperson)
            $school = $seminar->partner ? $this->schoolOnly($seminar->partner) : [];

            foreach ($applications as $application) {
                $person = $this->personValues($application);
                $jobs[] = ['label' => $person['empfaenger_name'], 'values' => ['empfaenger_ist_schule' => '', 'empfaenger_ist_person' => '1'] + $person + $school + $base];
            }
        }

        $files = [];

        foreach ($jobs as $job) {
            $files[] = ['name' => 'Schreiben_'.Str::slug($job['label'] ?: 'Empfaenger').'.docx', 'path' => $this->fill($template, $job['values'])];
        }

        AuditLog::record(AuditEvent::LettersGenerated, $by, $seminar, ['nummer' => (string) $seminar->number, 'anzahl' => count($files)]);

        if (count($files) === 1) {
            return ['path' => $files[0]['path'], 'name' => $files[0]['name'], 'count' => 1];
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'briefe').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $used = [];

        foreach ($files as $file) {
            $name = $file['name'];

            while (isset($used[$name])) {
                $name = Str::beforeLast($file['name'], '.docx').'_'.(count($used) + 1).'.docx';
            }

            $used[$name] = true;
            $zip->addFile($file['path'], $name);
        }

        $zip->close();

        foreach ($files as $file) {
            @unlink($file['path']);
        }

        return ['path' => $zipPath, 'name' => 'Schreiben_'.Str::slug((string) ($seminar->number ?: $seminar->title)).'.zip', 'count' => count($files)];
    }

    /**
     * Setzt die Werte in die Vorlage ein. Bekannte Platzhalter ohne Wert werden leer; unbekannte bleiben sichtbar stehen.
     *
     * @param  array<string, string>  $values
     */
    public function fill(LetterTemplate $template, array $values): string
    {
        Settings::setOutputEscapingEnabled(false);
        $processor = new TemplateProcessor($this->templates->absolutePath($template));
        $this->applyBlocks($processor, $values);

        foreach ($processor->getVariables() as $variable) {
            if (array_key_exists($variable, $values)) {
                $processor->setValue($variable, $this->xml((string) $values[$variable]));
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'schreiben').'.docx';
        $processor->saveAs($path);

        return $path;
    }

    /**
     * Wenn-Blöcke: ${wenn_NAME} … ${/wenn_NAME} bleibt nur, wenn NAME einen Wert hat; ${ohne_NAME} … ${/ohne_NAME} nur, wenn NAME leer ist.
     * Die Markierungen stehen in Word jeweils allein in einem Absatz. Gleich benannte Blöcke dürfen mehrfach vorkommen. Blöcke zu unbekannten Namen bleiben stehen.
     *
     * @param  array<string, string>  $values
     */
    private function applyBlocks(TemplateProcessor $processor, array $values): void
    {
        foreach ($processor->getVariables() as $variable) {
            if (str_starts_with($variable, '/')) {
                continue;
            }

            foreach (LetterVariables::BLOCK_PREFIXES as $prefix) {
                if (! str_starts_with($variable, $prefix) || ! array_key_exists(substr($variable, strlen($prefix)), $values)) {
                    continue;
                }

                $filled = trim((string) $values[substr($variable, strlen($prefix))]) !== '';
                $show = $prefix === 'wenn_' ? $filled : ! $filled;

                for ($i = 0; $i < 25 && in_array('/'.$variable, $processor->getVariables(), true); $i++) {
                    $show ? $processor->cloneBlock($variable, 1, true, false) : $processor->deleteBlock($variable);
                }
            }
        }
    }

    /** Maskiert für Word-XML; Zeilenumbrüche werden zu Zeilenumbrüchen in Word. */
    private function xml(string $value): string
    {
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
        $value = htmlspecialchars(str_replace(["\r\n", "\r"], "\n", $value), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return str_replace("\n", '</w:t><w:br/><w:t xml:space="preserve">', $value);
    }

    /** @return array<string, string> */
    private function baseValues(User $by, ?string $subject, ?string $freitext): array
    {
        $now = Carbon::now()->locale('de');
        $by->loadMissing('person');
        $club = $this->settings->all();

        return [
            'datum' => $now->format('d.m.Y'), 'datum_lang' => $now->isoFormat('D. MMMM YYYY'), 'jahr' => $now->format('Y'),
            'betreff' => trim((string) $subject), 'freitext' => trim((string) $freitext),
            'absender_name' => (string) ($by->person?->fullName() ?: $by->email), 'absender_email' => (string) $by->email,
            'absender_telefon' => (string) ($by->person?->phone ?? ''),
            'verein_name' => $club['club_name'], 'verein_kurz' => $club['club_short'], 'verein_vorsitz' => $club['chair'], 'verein_anschrift' => $club['address'], 'verein_ort' => $club['place'],
        ];
    }

    /** @return array<string, string> */
    private function seminarValues(Seminar $seminar): array
    {
        $sessions = $seminar->sessions->sortBy('starts_at')->values();
        $first = $sessions->first();

        return [
            'seminar_titel' => (string) $seminar->title, 'seminar_nr' => (string) ($seminar->number ?? ''), 'seminar_art' => (string) $seminar->type->label(),
            'seminar_termine' => $sessions->map(fn ($session): string => $session->period().($session->location ? ' – '.$session->location : ''))->implode("\n"),
            'seminar_erster_termin' => (string) ($first?->period() ?? ''), 'seminar_ort' => (string) ($first?->location ?? ''),
            'seminar_teilnehmende' => (string) ($seminar->expected_participants ?? ''),
            'seminar_beginn' => $first ? $first->starts_at->format('d.m.Y') : '',
            'seminar_ende' => $sessions->isNotEmpty() ? $sessions->last()->ends_at->format('d.m.Y') : '',
            'seminar_tage' => (string) $sessions->map(fn ($session): string => $session->starts_at->format('Y-m-d'))->unique()->count(),
            'seminar_stunden' => str_replace('.', ',', (string) round($sessions->sum(fn ($session): float => max(0, $session->starts_at->diffInMinutes($session->ends_at)) / 60), 1)),
            'seminar_team' => collect($this->recipients($seminar))->map(fn (SeminarApplication $application): string => $application->applicantName())->implode(', '),
            'seminar_koordination' => (string) ($seminar->coordinator?->person?->fullName() ?? ''),
            'seminar_frist' => $seminar->application_deadline?->format('d.m.Y') ?? '',
            'verguetung' => ($amount = app(Contracts::class)->honorarium($seminar)) > 0 ? Money::format($amount) : '',
        ];
    }

    /** @return array<string, string> */
    private function schoolValues(Partner $partner): array
    {
        $contact = $partner->contacts()->orderByDesc('is_primary')->orderBy('id')->first();
        $street = trim((string) $partner->street.' '.(string) $partner->house_number);
        $city = trim((string) $partner->postal_code.' '.(string) $partner->city);
        $address = collect([$street, $city])->filter()->implode("\n");

        return [
            'empfaenger_name' => (string) $partner->name, 'empfaenger_vorname' => '', 'empfaenger_nachname' => '', 'empfaenger_anschrift' => $address,
            'empfaenger_strasse' => $street, 'empfaenger_plz' => (string) $partner->postal_code, 'empfaenger_ort' => (string) $partner->city, 'empfaenger_email' => (string) ($contact?->email ?? $partner->email ?? ''),
            'empfaenger_telefon' => (string) ($contact?->phone ?? $partner->phone ?? ''),
        ] + $this->schoolOnly($partner, $address, $contact);
    }

    /** Angaben zur Schule, die auch in Schreiben an Personen zur Verfügung stehen. @return array<string, string> */
    private function schoolOnly(Partner $partner, ?string $address = null, mixed $contact = null): array
    {
        $contact ??= $partner->contacts()->first();
        $attributes = (array) ($partner->getAttribute('attributes') ?? []);
        $state = FederalState::tryFrom((string) ($attributes['federal_state'] ?? ''));
        $type = SchoolType::tryFrom((string) ($attributes['school_type'] ?? ''));
        $address ??= collect([trim((string) $partner->street.' '.(string) $partner->house_number), trim((string) $partner->postal_code.' '.(string) $partner->city)])->filter()->implode("\n");

        return [
            'schule_name' => (string) $partner->name, 'schule_anschrift' => $address, 'schule_telefon' => (string) ($partner->phone ?? ''), 'schule_email' => (string) ($partner->email ?? ''),
            'schule_nummer' => (string) ($partner->internalId() ?? ''), 'schule_bundesland' => (string) ($state?->label() ?? ''), 'schule_schulart' => (string) ($type?->label() ?? ''),
            'ansprechperson' => (string) ($contact?->name ?? ''), 'ansprechperson_email' => (string) ($contact?->email ?? ''), 'ansprechperson_telefon' => (string) ($contact?->phone ?? ''),
        ];
    }

    /** @return array<string, string> */
    private function personValues(SeminarApplication $application): array
    {
        $person = $application->user?->person;
        $contact = $application->contact;
        $first = (string) ($person?->first_name ?? $contact?->first_name ?? '');
        $last = (string) ($person?->last_name ?? $contact?->last_name ?? '');
        $street = trim((string) ($person?->street ?? $contact?->street ?? '').' '.(string) ($person?->house_number ?? $contact?->house_number ?? ''));
        $zip = (string) ($person?->postal_code ?? $contact?->postal_code ?? '');
        $city = (string) ($person?->city ?? $contact?->city ?? '');

        return [
            'empfaenger_name' => $application->applicantName(), 'empfaenger_vorname' => $first, 'empfaenger_nachname' => $last,
            'empfaenger_anschrift' => collect([$street, trim($zip.' '.$city)])->filter()->implode("\n"),
            'empfaenger_strasse' => $street, 'empfaenger_plz' => $zip, 'empfaenger_ort' => $city, 'empfaenger_email' => (string) $application->applicantEmail(),
            'empfaenger_telefon' => (string) ($person?->phone ?? $contact?->phone ?? ''),
        ];
    }
}
