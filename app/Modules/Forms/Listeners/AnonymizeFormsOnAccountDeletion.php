<?php

namespace App\Modules\Forms\Listeners;

use App\Modules\Access\Events\AccountAnonymizing;
use App\Modules\Forms\Models\FormApplication;

/** Bei der Kontolöschung bleiben Anträge (steuerliche Aufbewahrung), aber Name, E-Mail und Kontodaten werden entfernt. */
final class AnonymizeFormsOnAccountDeletion
{
    public function handle(AccountAnonymizing $event): void
    {
        FormApplication::query()->where('applicant_user_id', $event->user->id)->get()->each(function (FormApplication $application): void {
            $application->forceFill(['applicant_name' => 'Gelöschte Person', 'applicant_email' => null, 'iban_encrypted' => null, 'account_holder' => null])->save();
        });
    }
}
