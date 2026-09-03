<?php

namespace App\Modules\Communication\Jobs;

use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Mail\TemplatedMail;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Services\EmailTemplateRenderer;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Throwable;

final class SendTemplatedEmailJob implements ShouldQueue, ShouldBeEncrypted
{
    use FoundationQueueable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param array<string, string|int|float> $values
     */
    public function __construct(
        public readonly int $emailDeliveryId,
        public readonly array $values,
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [
            180,
            180,
        ];
    }

    public function handle(
        EmailTemplateRenderer $renderer,
    ): void {
        $delivery = EmailDelivery::query()
            ->with([
                'templateVersion.template.placeholders',
            ])
            ->findOrFail($this->emailDeliveryId);

        if ($delivery->status === EmailDeliveryStatus::Sent) {
            return;
        }

        if ($delivery->templateVersion === null) {
            throw new LogicException(
                'Ein Template-Versand benötigt eine veröffentlichte Template-Version.'
            );
        }

        $delivery->attempts++;

        $delivery->save();

        $rendered = $renderer->render(
            version: $delivery->templateVersion,
            values: $this->values,
        );

        $delivery->subject = $rendered['subject'];

        $delivery->save();

        Mail::to($delivery->recipient_email)->send(
            new TemplatedMail(
                subjectLine: $rendered['subject'],
                htmlContent: $rendered['html'],
            )
        );

        $delivery->status = EmailDeliveryStatus::Sent;
        $delivery->sent_at = now();
        $delivery->failed_at = null;
        $delivery->last_error_class = null;

        $delivery->save();
    }

    public function failed(
        ?Throwable $exception,
    ): void {
        $delivery = EmailDelivery::query()
            ->find($this->emailDeliveryId);

        if (
            $delivery === null
            || $delivery->status === EmailDeliveryStatus::Sent
        ) {
            return;
        }

        $delivery->status = EmailDeliveryStatus::Failed;
        $delivery->failed_at = now();
        $delivery->last_error_class = $exception !== null
            ? $exception::class
            : null;

        $delivery->save();
    }
}