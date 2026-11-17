<?php

namespace App\Modules\Contracts\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Contracts\Models\ContractTemplate;
use App\Modules\Contracts\Models\SchoolContract;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Support\Money;
use App\Support\Html\RichText;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Der Vertrag mit der Schule: aus einer versionierten Vorlage mit den Daten des Seminars (Schule, Leistung, Termine, Preis aus dem Kostenvoranschlag).
 * Das Portal erzeugt ihn zum Ausdrucken; unterschrieben wird auf Papier. Die Geschäftsstelle erfasst danach das Datum der Unterschrift.
 */
final class SchoolContracts
{
    public const KEY = 'school_agreement';

    /** @var array<string, string> */
    public const PLACEHOLDERS = [
        'verein_name' => 'Name des Vereins',
        'verein_anschrift' => 'Anschrift des Vereins',
        'verein_vorsitz' => 'Vorsitz',
        'schule_name' => 'Name der Schule',
        'schule_anschrift' => 'Anschrift der Schule',
        'seminar_nummer' => 'Nummer des Seminars',
        'leistung' => 'Leistung und Titel des Seminars',
        'termine' => 'Termine',
        'verguetung' => 'Preis laut Kostenvoranschlag',
        'ort' => 'Ort der Unterschrift',
        'datum' => 'Datum',
    ];

    public function __construct(private readonly ContractSettings $settings) {}

    public static function defaultBody(): string
    {
        return '<p>Zwischen dem {verein_name}, {verein_anschrift}<br>- nachfolgend Verein genannt -</p>'
            .'<p>und der Schule {schule_name}, {schule_anschrift}<br>- nachfolgend Schule genannt -</p>'
            .'<h2>§ 1 Gegenstand</h2><p>Der Verein führt für die Schule das Seminar {seminar_nummer} durch: {leistung}.</p>'
            .'<h2>§ 2 Termine</h2><p>{termine}</p>'
            .'<h2>§ 3 Vergütung</h2><p>Die Schule zahlt dem Verein die Vergütung laut Kostenvoranschlag: {verguetung}.</p>'
            .'<h2>§ 4 Absage</h2><p>Sagt die Schule einen Termin ab, ist der Verein unverzüglich zu informieren. Näheres regeln die Parteien in Textform.</p>'
            .'<p>{ort}, den {datum}</p>';
    }

    public function active(): ContractTemplate
    {
        return ContractTemplate::query()->where('key', self::KEY)->where('active', true)->orderByDesc('version')->first()
            ?? ContractTemplate::query()->create(['key' => self::KEY, 'version' => 1, 'title' => 'Vertrag mit der Schule', 'body' => self::defaultBody(), 'active' => true, 'created_at' => now()]);
    }

    /** @return Collection<int, ContractTemplate> */
    public function versions(): Collection
    {
        $this->active();

        return ContractTemplate::query()->where('key', self::KEY)->orderByDesc('version')->get();
    }

    public function createVersion(string $title, string $body, User $by): ContractTemplate
    {
        $current = $this->active();
        ContractTemplate::query()->where('key', self::KEY)->update(['active' => false]);

        $template = ContractTemplate::query()->create(['key' => self::KEY, 'version' => $current->version + 1, 'title' => trim($title), 'body' => RichText::clean($body), 'active' => true, 'created_by_user_id' => $by->id, 'created_at' => now()]);
        AuditLog::record(AuditEvent::SchoolContractTemplateSaved, $by, null, ['version' => $template->version]);

        return $template;
    }

    /** @return list<string> Platzhalter im Text, die es nicht gibt. */
    public function unknown(string $body): array
    {
        preg_match_all('/\{([a-z_]+)\}/', $body, $matches);

        return array_values(array_diff(array_unique($matches[1]), array_keys(self::PLACEHOLDERS)));
    }

    /** Der gültige Vertrag eines Seminars (nicht storniert), sonst null. */
    public function current(Seminar $seminar): ?SchoolContract
    {
        return SchoolContract::query()->where('seminar_id', $seminar->id)->where('status', '!=', SchoolContract::CANCELLED)->latest('id')->first();
    }

    /** @throws DomainException */
    public function create(Seminar $seminar, User $by): SchoolContract
    {
        if ($this->current($seminar) !== null) {
            throw new DomainException('Für dieses Seminar gibt es schon einen Vertrag. Stornieren Sie ihn, bevor Sie einen neuen erzeugen.');
        }

        $template = $this->active();
        $body = $this->render($template, $seminar);

        $contract = DB::transaction(function () use ($seminar, $by, $template, $body): SchoolContract {
            $contract = SchoolContract::query()->create(['seminar_id' => $seminar->id, 'template_version' => $template->version, 'title' => $template->title, 'body' => $body, 'created_by_user_id' => $by->id]);
            $this->note($seminar, $by, 'Vertrag mit der Schule erzeugt (Fassung '.$template->version.').');

            return $contract;
        });

        AuditLog::record(AuditEvent::SchoolContractCreated, $by, $seminar, ['nummer' => (string) $seminar->number]);

        return $contract;
    }

    /** @throws DomainException */
    public function recordSigned(SchoolContract $contract, string $date, User $by): void
    {
        if ($contract->status === SchoolContract::CANCELLED) {
            throw new DomainException('Ein stornierter Vertrag lässt sich nicht als unterschrieben erfassen.');
        }

        $signed = Carbon::parse($date);

        if ($signed->isFuture()) {
            throw new DomainException('Das Datum der Unterschrift liegt in der Zukunft.');
        }

        DB::transaction(function () use ($contract, $signed, $by): void {
            $contract->forceFill(['status' => SchoolContract::SIGNED, 'signed_on' => $signed->toDateString(), 'signed_recorded_by_user_id' => $by->id])->save();
            $contract->seminar->forceFill(['school_contract_signed_at' => $signed->toDateString(), 'updated_by_user_id' => $by->id])->save();
            $this->note($contract->seminar, $by, 'Vertrag mit der Schule unterschrieben am '.$signed->format('d.m.Y').' (erfasst).');
        });

        AuditLog::record(AuditEvent::SchoolContractSigned, $by, $contract->seminar, ['nummer' => (string) $contract->seminar->number]);
    }

    public function cancel(SchoolContract $contract, User $by): void
    {
        $contract->forceFill(['status' => SchoolContract::CANCELLED])->save();
        $this->note($contract->seminar, $by, 'Vertrag mit der Schule storniert.');
        AuditLog::record(AuditEvent::SchoolContractCancelled, $by, $contract->seminar, ['nummer' => (string) $contract->seminar->number]);
    }

    /** Setzt die Werte in die Vorlage ein; fehlende Werte werden zur Linie. Alles wird maskiert. */
    public function render(ContractTemplate $template, Seminar $seminar): string
    {
        $seminar->loadMissing(['partner', 'sessions', 'quotes']);
        $club = $this->settings->all();
        $blank = '<span class="contract-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
        $text = fn (?string $value): string => filled($value) ? e(trim((string) $value)) : $blank;

        $partner = $seminar->partner;
        $address = collect([trim((string) $partner?->street.' '.(string) $partner?->house_number), trim((string) $partner?->postal_code.' '.(string) $partner?->city)])->map(fn ($part) => trim((string) $part))->filter()->implode(', ');
        $dates = $seminar->sessions->sortBy('starts_at')->map(fn ($session): string => $session->period())->implode('; ');
        $quote = $seminar->quotes->sortByDesc('id')->first();

        $values = [
            'verein_name' => $text($club['club_name'] ?? null),
            'verein_anschrift' => $text($club['address'] ?? null),
            'verein_vorsitz' => $text($club['chair'] ?? null),
            'schule_name' => $text($partner?->name),
            'schule_anschrift' => $text($address),
            'seminar_nummer' => $text($seminar->number),
            'leistung' => $text($seminar->type->label().' „'.$seminar->title.'“'),
            'termine' => $text($dates),
            'verguetung' => $quote !== null && (int) $quote->total_cents > 0 ? e(Money::format((int) $quote->total_cents)) : $blank,
            'ort' => $text($club['place'] ?? null),
            'datum' => $blank,
        ];

        return (string) preg_replace_callback('/\{([a-z_]+)\}/', fn (array $match): string => $values[$match[1]] ?? $match[0], $template->body);
    }

    private function note(Seminar $seminar, User $by, string $comment): void
    {
        $seminar->events()->create(['type' => SeminarEventType::Note, 'occurred_at' => now(), 'actor_user_id' => $by->id, 'actor_label' => AuditLog::label($by), 'comment' => $comment, 'data' => null]);
    }
}
