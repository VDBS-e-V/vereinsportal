<?php

namespace App\Modules\Mailings\Jobs;

use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\System\Support\Redactor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Versendet ein Schreiben in Paketen. Jeder Lauf sendet höchstens portal.mailings.batch_size Zustellungen und startet sich dann mit Wartezeit
 * (Drosselung: portal.mailings.per_minute) neu, bis nichts mehr offen ist. Jede Zustellung wird atomar von „pending“ auf „sending“ gesetzt, bevor
 * gesendet wird: ein wiederholter Lauf sendet nie zweimal an dieselbe Zeile. Fehlgeschlagene Zeilen werden bis max_attempts wiederholt.
 */
class SendMailing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public readonly int $mailingId) {}

    public function handle(Mailings $mailings): void
    {
        $mailing = Mailing::query()->find($this->mailingId);

        if ($mailing === null || ! $mailing->isActive()) {
            return;
        }

        if ($mailing->status === Mailing::STATUS_QUEUED) {
            $mailing->forceFill(['status' => Mailing::STATUS_SENDING, 'started_at' => $mailing->started_at ?? now()])->save();
        }

        // Zeilen, die ein abgestürzter Worker in „sending“ liegen ließ, wieder freigeben
        MailingDelivery::query()->where('mailing_id', $mailing->id)->where('status', MailingDelivery::SENDING)->where('updated_at', '<', now()->subMinutes(10))->update(['status' => MailingDelivery::PENDING]);

        $limit = max(1, (int) config('portal.mailings.batch_size', 25));
        $maxAttempts = max(1, (int) config('portal.mailings.max_attempts', 3));

        $ids = MailingDelivery::query()->where('mailing_id', $mailing->id)->where('status', MailingDelivery::PENDING)->orderBy('id')->limit($limit)->pluck('id');

        foreach ($ids as $deliveryId) {
            if (Mailing::query()->whereKey($mailing->id)->value('status') === Mailing::STATUS_CANCELLED) {
                return;
            }

            $claimed = MailingDelivery::query()->whereKey($deliveryId)->where('status', MailingDelivery::PENDING)->update(['status' => MailingDelivery::SENDING, 'attempts' => DB::raw('attempts + 1')]);

            if ($claimed !== 1) {
                continue;
            }

            $delivery = MailingDelivery::query()->find($deliveryId);
            $block = $mailings->deliveryBlock($mailing, $delivery);

            if ($block !== null) {
                $delivery->forceFill(['status' => MailingDelivery::SKIPPED, 'error' => $block])->save();

                continue;
            }

            try {
                $mailings->sendDelivery($mailing, $delivery);
                $delivery->forceFill(['status' => MailingDelivery::SENT, 'sent_at' => now(), 'error' => null])->save();
            } catch (Throwable $exception) {
                report($exception);
                $delivery->forceFill(['status' => $delivery->attempts >= $maxAttempts ? MailingDelivery::FAILED : MailingDelivery::PENDING, 'error' => Redactor::clean(Str::limit($exception->getMessage(), 240, ''))])->save();
            }
        }

        $mailing = Mailing::query()->find($this->mailingId);

        if ($mailing === null || $mailing->status === Mailing::STATUS_CANCELLED) {
            return;
        }

        $mailings->refreshCounts($mailing);

        $open = MailingDelivery::query()->where('mailing_id', $mailing->id)->whereIn('status', [MailingDelivery::PENDING, MailingDelivery::SENDING])->count();

        if ($open === 0) {
            $mailing->forceFill(['status' => $mailing->sent_count === 0 && $mailing->failed_count > 0 ? Mailing::STATUS_FAILED : Mailing::STATUS_DONE, 'finished_at' => now()])->save();

            return;
        }

        $delay = max(1, (int) ceil($limit / max(1, (int) config('portal.mailings.per_minute', 60)) * 60));

        static::dispatch($mailing->id)->delay(now()->addSeconds($delay));
    }
}
