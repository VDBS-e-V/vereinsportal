<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Models\PaymentOrder;
use App\Modules\Seminars\Models\Seminar;
use App\Support\Money;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Verträge der Teamenden: Die Seminarleitung lädt den ausgefüllten Vertrag je zugesagter Person hoch und trägt den Betrag aus dem Vertrag ein.
 * Daraus entsteht automatisch ein Zahlungsauftrag an die Geschäftsstelle (Buchhaltung). Bankdaten speichert das Portal nicht; sie stehen im Vertrag.
 */
final class TeamContracts
{
    public function __construct(private readonly SeminarDocuments $documents, private readonly SeminarWorkflow $workflow, private readonly SeminarNotifications $notifications) {}

    /**
     * @throws DomainException
     */
    public function add(Seminar $seminar, int $memberUserId, UploadedFile $file, string $amountInput, User $by): PaymentOrder
    {
        if (! in_array($seminar->status->value, ['prepared', 'held'], true)) {
            throw new DomainException('Verträge lassen sich nach der Vorbereitung bis zur Abgabe des Berichts hochladen.');
        }

        $member = $seminar->applications()->where('user_id', $memberUserId)->where('outcome', 'confirmed')->with('user.person')->first();

        if ($member === null) {
            throw new DomainException('Der Vertrag muss zu einer Person mit Zusage gehören.');
        }

        $amount = Money::parseEuro($amountInput);

        if ($amount === null || $amount <= 0) {
            throw new DomainException('Bitte geben Sie den Betrag aus dem Vertrag in Euro an, zum Beispiel 250,00.');
        }

        $existing = PaymentOrder::query()->where('seminar_id', $seminar->id)->where('user_id', $memberUserId)->first();

        if ($existing !== null && $existing->status === PaymentOrder::PAID) {
            throw new DomainException('Dieser Vertrag ist schon bezahlt und lässt sich nicht ersetzen.');
        }

        $name = $member->user->person?->fullName() ?: (string) $member->user->email;

        $order = DB::transaction(function () use ($seminar, $memberUserId, $file, $amount, $by, $name, $existing): PaymentOrder {
            $document = $this->documents->add($seminar, $file, 'team_contract', 'Vertrag '.$name, $by);

            return PaymentOrder::query()->updateOrCreate(
                ['seminar_id' => $seminar->id, 'user_id' => $memberUserId],
                ['seminar_document_id' => $document->id, 'amount_cents' => $amount, 'status' => PaymentOrder::OPEN, 'note' => null, 'decided_by_user_id' => null, 'decided_at' => null] + ($existing === null ? ['created_by_user_id' => $by->id] : []),
            );
        });

        $this->workflow->log($seminar, 'payment_order', $by, null, null, 'Zahlungsauftrag für '.$name.': '.Money::format($amount));
        AuditLog::record(AuditEvent::PaymentOrderCreated, $by, null, ['seminar' => $seminar->number, 'betrag' => $amount]);
        $this->notifications->paymentCreated($seminar, $name, Money::format($amount));

        return $order;
    }

    /** Die Buchhaltung entscheidet: bezahlt oder abgelehnt (mit Begründung). @throws DomainException */
    public function decide(PaymentOrder $order, string $status, ?string $note, User $by): PaymentOrder
    {
        if (! in_array($status, [PaymentOrder::PAID, PaymentOrder::REJECTED], true)) {
            throw new DomainException('Unbekannte Entscheidung.');
        }

        if ($order->status === PaymentOrder::PAID) {
            throw new DomainException('Dieser Zahlungsauftrag ist schon bezahlt.');
        }

        if ($status === PaymentOrder::REJECTED && trim((string) $note) === '') {
            throw new DomainException('Bitte begründen Sie die Ablehnung.');
        }

        $order->fill(['status' => $status, 'note' => $note !== null && trim($note) !== '' ? trim($note) : null, 'decided_by_user_id' => $by->id, 'decided_at' => now()])->save();
        AuditLog::record(AuditEvent::PaymentOrderDecided, $by, null, ['seminar' => $order->seminar->number, 'status' => $status]);

        return $order;
    }
}
