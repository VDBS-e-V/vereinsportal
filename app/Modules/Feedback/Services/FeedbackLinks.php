<?php

namespace App\Modules\Feedback\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Feedback\Models\FeedbackForm;
use App\Modules\Feedback\Models\FeedbackLink;
use App\Modules\Seminars\Models\Seminar;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/** Feedbacklinks eines Seminars: anlegen, beenden, Zustand bestimmen, über den Link auffinden. Ein Link gilt für viele Antworten. */
final class FeedbackLinks
{
    public const STATES = ['closed' => 'beendet', 'pending' => 'noch nicht offen', 'open' => 'offen', 'expired' => 'abgelaufen', 'full' => 'voll'];

    public function __construct(private readonly FeedbackForms $forms) {}

    /**
     * Vorschlag für Zeitraum und Obergrenze: vom ersten Termin bis 14 Tage nach dem letzten Termin; bei Schülerinnen und Schülern höchstens das 1,5-Fache der erwarteten Teilnehmenden.
     *
     * @return array{opens_at: CarbonInterface, closes_at: CarbonInterface, max_responses: ?int, min_responses: int}
     */
    public function defaults(Seminar $seminar, string $audience): array
    {
        $sessions = $seminar->sessions;
        $first = $sessions->min('starts_at');
        $last = $sessions->max('ends_at');
        $expected = (int) $seminar->expected_participants;

        return [
            'opens_at' => ($first ? Carbon::parse($first) : now())->startOfDay(),
            'closes_at' => ($last ? Carbon::parse($last) : now())->copy()->addDays(14)->endOfDay(),
            'max_responses' => $audience === 'students' && $expected > 0 ? (int) round($expected * 1.5) : null,
            'min_responses' => (int) config('portal.feedback.min_responses', 5),
        ];
    }

    /**
     * @param  array<string, mixed>  $input  opens_at, closes_at, max_responses, min_responses, label
     *
     * @throws DomainException
     */
    public function create(Seminar $seminar, string $audience, array $input, User $by): FeedbackLink
    {
        if (! array_key_exists($audience, FeedbackForm::AUDIENCES)) {
            throw new DomainException('Bitte wählen Sie, für wen der Link gilt.');
        }

        $form = $this->forms->forAudience($audience);

        if ($form === null) {
            throw new DomainException('Für diese Gruppe gibt es kein aktives Formular.');
        }

        $defaults = $this->defaults($seminar, $audience);
        $opens = ! empty($input['opens_at']) ? Carbon::parse((string) $input['opens_at'])->startOfDay() : $defaults['opens_at'];
        $closes = ! empty($input['closes_at']) ? Carbon::parse((string) $input['closes_at'])->endOfDay() : $defaults['closes_at'];

        if ($closes->lessThanOrEqualTo($opens)) {
            throw new DomainException('Das Ende muss nach dem Beginn liegen.');
        }

        $max = isset($input['max_responses']) && $input['max_responses'] !== '' ? max(1, (int) $input['max_responses']) : $defaults['max_responses'];
        $min = isset($input['min_responses']) && $input['min_responses'] !== '' ? max(1, min(50, (int) $input['min_responses'])) : $defaults['min_responses'];

        $token = Str::random(32);

        $link = FeedbackLink::query()->create([
            'seminar_id' => $seminar->id,
            'form_id' => $form->id,
            'form_version' => $form->version,
            'audience' => $audience,
            'token_hash' => hash('sha256', $token),
            'token_enc' => Crypt::encryptString($token),
            'label' => Str::limit(trim((string) ($input['label'] ?? '')), 100, '') ?: null,
            'opens_at' => $opens,
            'closes_at' => $closes,
            'max_responses' => $max,
            'min_responses' => $min,
            'created_by_user_id' => $by->id,
        ]);

        AuditLog::record(AuditEvent::FeedbackLinkCreated, $by, null, ['seminar' => $seminar->number ?? $seminar->id, 'gruppe' => $link->audienceLabel()]);

        return $link;
    }

    public function close(FeedbackLink $link, User $by): void
    {
        if ($link->closed_at === null) {
            $link->forceFill(['closed_at' => now()])->save();
            AuditLog::record(AuditEvent::FeedbackLinkClosed, $by, null, ['seminar' => $link->seminar_id, 'gruppe' => $link->audienceLabel()]);
        }
    }

    public function findByToken(string $token): ?FeedbackLink
    {
        return FeedbackLink::query()->where('token_hash', hash('sha256', $token))->first();
    }

    public function state(FeedbackLink $link): string
    {
        if ($link->closed_at !== null) {
            return 'closed';
        }

        if (now()->lessThan($link->opens_at)) {
            return 'pending';
        }

        if (now()->greaterThan($link->closes_at)) {
            return 'expired';
        }

        if ($link->max_responses !== null && $link->responses()->count() >= $link->max_responses) {
            return 'full';
        }

        return 'open';
    }
}
