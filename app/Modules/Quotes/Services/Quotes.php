<?php

namespace App\Modules\Quotes\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Mail\SeminarQuoteMail;
use App\Modules\Quotes\Models\QuoteText;
use App\Modules\Quotes\Models\SeminarQuote;
use App\Modules\Quotes\Support\QuoteCalculator;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarWorkflow;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Kostenvoranschlag eines Seminars: Version anlegen, bearbeiten, intern freigeben, senden, Antwort der Schule. */
final class Quotes
{
    public function __construct(private readonly SeminarWorkflow $workflow, private readonly QuotePdf $pdf) {}

    /**
     * Neue Version. Beim ersten Mal mit den Textbausteinen, danach als Kopie der letzten Version. Steht das Seminar auf „KVA gesendet“, geht es dafür zurück in die Prüfung.
     *
     * @throws DomainException
     */
    public function createDraft(Seminar $seminar, User $by): SeminarQuote
    {
        if (! in_array($seminar->status, [SeminarStatus::InReview, SeminarStatus::QuoteSent], true)) {
            throw new DomainException('Einen Kostenvoranschlag gibt es nur, solange das Seminar geprüft wird.');
        }

        if ($seminar->quotes()->whereIn('status', [QuoteStatus::Draft->value, QuoteStatus::Approved->value])->exists()) {
            throw new DomainException('Es gibt schon einen Entwurf. Bearbeiten Sie ihn oder senden Sie ihn.');
        }

        return DB::transaction(function () use ($seminar, $by): SeminarQuote {
            if ($seminar->status === SeminarStatus::QuoteSent) {
                $this->workflow->transition($seminar, SeminarStatus::InReview, $by, 'Neue Version des Kostenvoranschlags');
            }

            $latest = $seminar->quotes()->with('lines')->orderByDesc('version')->first();

            $quote = $seminar->quotes()->create([
                'version' => ($latest?->version ?? 0) + 1,
                'status' => QuoteStatus::Draft,
                'vat_percent' => $latest?->vat_percent ?? 0,
                'intro' => $latest?->intro ?? QuoteText::body('intro'),
                'outro' => $latest?->outro ?? QuoteText::body('outro'),
                'created_by_user_id' => $by->id,
            ]);

            if ($latest !== null) {
                foreach ($latest->lines as $line) {
                    $quote->lines()->create(['position' => $line->position, 'description' => $line->description, 'quantity' => $line->quantity, 'unit' => $line->unit, 'unit_price_cents' => $line->unit_price_cents]);
                }

                $seminar->quotes()->whereIn('status', [QuoteStatus::Sent->value, QuoteStatus::Accepted->value])->update(['status' => QuoteStatus::Superseded->value]);
                $this->recalculate($quote);
            }

            $this->workflow->log($seminar, 'quote_created', $by, null, null, 'Version '.$quote->version);

            return $quote;
        });
    }

    /**
     * Speichert Positionen, Steuersatz und Texte. Ein bereits freigegebener Entwurf muss danach neu freigegeben werden.
     *
     * @param  list<array{description: string, quantity: float, unit: ?string, unit_price_cents: int}>  $lines
     *
     * @throws DomainException
     */
    public function save(SeminarQuote $quote, array $lines, float $vatPercent, ?string $intro, ?string $outro): SeminarQuote
    {
        if (! $quote->status->isEditable()) {
            throw new DomainException('Ein gesendeter Kostenvoranschlag lässt sich nicht mehr ändern. Legen Sie eine neue Version an.');
        }

        DB::transaction(function () use ($quote, $lines, $vatPercent, $intro, $outro): void {
            $quote->lines()->delete();

            foreach (array_values($lines) as $index => $line) {
                $quote->lines()->create(['position' => $index + 1] + $line);
            }

            $quote->fill(['vat_percent' => $vatPercent, 'intro' => $intro, 'outro' => $outro, 'status' => QuoteStatus::Draft, 'approved_by_user_id' => null, 'approved_at' => null])->save();
            $this->recalculate($quote);
        });

        return $quote->refresh();
    }

    /** Intern freigeben (Vorstand oder Koordination). @throws DomainException */
    public function approve(SeminarQuote $quote, User $by): SeminarQuote
    {
        if ($quote->status !== QuoteStatus::Draft) {
            throw new DomainException('Nur ein Entwurf lässt sich freigeben.');
        }

        if ($quote->lines()->count() === 0 || $quote->total_cents <= 0) {
            throw new DomainException('Der Kostenvoranschlag braucht mindestens eine Position mit einem Betrag.');
        }

        $quote->fill(['status' => QuoteStatus::Approved, 'approved_by_user_id' => $by->id, 'approved_at' => now()])->save();
        $this->workflow->log($quote->seminar, 'quote_approved', $by, null, null, 'Version '.$quote->version);
        AuditLog::record(AuditEvent::QuoteApproved, $by, null, ['seminar' => $quote->seminar->number, 'version' => $quote->version]);

        return $quote;
    }

    /**
     * Sendet den freigegebenen Kostenvoranschlag an die Schule (Pflicht-Mail mit PDF) und setzt das Seminar auf „KVA gesendet“.
     *
     * @throws DomainException
     */
    public function send(SeminarQuote $quote, User $by): SeminarQuote
    {
        $seminar = $quote->seminar;

        if ($quote->status !== QuoteStatus::Approved) {
            throw new DomainException('Der Kostenvoranschlag muss zuerst intern freigegeben werden.');
        }

        if (blank($seminar->contact_email)) {
            throw new DomainException('Zum Seminar gibt es keine E-Mail-Adresse der Schule.');
        }

        DB::transaction(function () use ($quote, $seminar, $by): void {
            $this->workflow->transition($seminar, SeminarStatus::QuoteSent, $by);
            $quote->fill(['status' => QuoteStatus::Sent, 'sent_at' => now()])->save();
        });

        AuditLog::record(AuditEvent::QuoteSent, $by, null, ['seminar' => $seminar->number, 'version' => $quote->version]);

        try {
            Mail::to((string) $seminar->contact_email)->send(new SeminarQuoteMail($seminar, $quote, $this->pdf->render($quote), route('school.seminars.show', $seminar)));
            $this->workflow->log($seminar, 'mail', $by, null, null, 'Kostenvoranschlag an die Schule gesendet');
        } catch (Throwable $exception) {
            report($exception);
            $this->workflow->log($seminar, 'mail_failed', $by, null, null, 'Der Kostenvoranschlag konnte nicht per E-Mail gesendet werden. Die Schule sieht ihn im Portal.');
        }

        return $quote;
    }

    /** Die Schule nimmt den Kostenvoranschlag an. Danach fehlen noch Vertrag oder Zusage für die Freigabe. @throws DomainException */
    public function accept(SeminarQuote $quote, User $by): void
    {
        $this->assertAnswerable($quote);

        $quote->fill(['status' => QuoteStatus::Accepted, 'decided_at' => now()])->save();
        $this->workflow->log($quote->seminar, 'quote_accepted', $by, null, null, 'Version '.$quote->version);
    }

    /** Die Schule lehnt ab: der Kostenvoranschlag ist abgelehnt, das Seminar auch. @throws DomainException */
    public function decline(SeminarQuote $quote, User $by, ?string $note): void
    {
        $this->assertAnswerable($quote);

        DB::transaction(function () use ($quote, $by, $note): void {
            $quote->fill(['status' => QuoteStatus::Declined, 'decided_at' => now(), 'decision_note' => $note])->save();
            $this->workflow->declinedBySchool($quote->seminar, $by, $note);
        });
    }

    /** Rückfrage oder Verhandlungswunsch der Schule: steht im Verlauf, die Geschäftsstelle legt eine neue Version an. @throws DomainException */
    public function question(SeminarQuote $quote, User $by, string $note): void
    {
        $this->assertAnswerable($quote);

        $this->workflow->log($quote->seminar, 'quote_question', $by, null, null, trim($note));
    }

    /** @throws DomainException */
    private function assertAnswerable(SeminarQuote $quote): void
    {
        if ($quote->status !== QuoteStatus::Sent || $quote->seminar->status !== SeminarStatus::QuoteSent) {
            throw new DomainException('Auf diesen Kostenvoranschlag lässt sich zurzeit nicht antworten.');
        }
    }

    private function recalculate(SeminarQuote $quote): void
    {
        $totals = QuoteCalculator::totals($quote->lines()->get()->map(fn ($line): array => ['quantity' => $line->quantity, 'unit_price_cents' => $line->unit_price_cents])->all(), $quote->vat_percent);

        $quote->fill(['net_cents' => $totals['net'], 'vat_cents' => $totals['vat'], 'total_cents' => $totals['total']])->save();
    }
}
