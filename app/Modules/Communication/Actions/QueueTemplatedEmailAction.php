<?php

namespace App\Modules\Communication\Actions;

use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Enums\EmailDeliveryType;
use App\Modules\Communication\Exceptions\EmailTemplateUnavailable;
use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Services\EmailTemplateRenderer;
use Illuminate\Support\Facades\Validator;

final class QueueTemplatedEmailAction
{
    public function __construct(
        private readonly EmailTemplateRenderer $renderer,
    ) {
    }

    /**
     * @param array<string, string|int|float> $values
     */
    public function execute(
        string $templateKey,
        string $recipientEmail,
        array $values,
    ): EmailDelivery {
        $recipientEmail = trim($recipientEmail);

        Validator::make(
            [
                'recipient_email' => $recipientEmail,
            ],
            [
                'recipient_email' => [
                    'required',
                    'email:rfc',
                    'max:254',
                ],
            ],
        )->validate();

        $template = EmailTemplate::query()
            ->where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            throw EmailTemplateUnavailable::missingOrInactive(
                $templateKey
            );
        }

        $version = $template
            ->versions()
            ->orderByDesc('version')
            ->first();

        if ($version === null) {
            throw EmailTemplateUnavailable::withoutPublishedVersion(
                $templateKey
            );
        }

        /*
         * Bewusst bereits vor dem Queueing rendern:
         *
         * - Placeholder-Fehler werden sofort erkannt.
         * - Der tatsächlich zu versendende Betreff landet bereits
         *   in der Versandhistorie.
         *
         * Der HTML-Body wird weiterhin nicht in email_deliveries
         * gespeichert.
         */
        $rendered = $this->renderer->render(
            version: $version,
            values: $values,
        );

        $delivery = EmailDelivery::query()->create([
            'template_version_id' => $version->id,
            'sender_user_id' => null,
            'recipient_email' => $recipientEmail,
            'subject' => $rendered['subject'],
            'delivery_type' => EmailDeliveryType::System,
            'status' => EmailDeliveryStatus::Queued,
            'attempts' => 0,
            'queued_at' => now(),
            'sent_at' => null,
            'failed_at' => null,
            'last_error_class' => null,
        ]);

        SendTemplatedEmailJob::dispatch(
            $delivery->id,
            $values,
        )->afterCommit();

        return $delivery;
    }
}