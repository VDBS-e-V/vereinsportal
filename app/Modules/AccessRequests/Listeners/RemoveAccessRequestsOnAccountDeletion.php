<?php

namespace App\Modules\AccessRequests\Listeners;

use App\Modules\Access\Events\AccountAnonymizing;
use App\Modules\AccessRequests\Models\AccessRequest;
use App\Modules\AccessRequests\Services\AccessRequests;

/** Beim Löschen eines Kontos entfallen die Zugangsanträge dieser Adresse. */
final class RemoveAccessRequestsOnAccountDeletion
{
    public function __construct(private readonly AccessRequests $requests) {}

    public function handle(AccountAnonymizing $event): void
    {
        AccessRequest::query()
            ->where(fn ($query) => $query->where('user_id', $event->user->id)->orWhere('email', mb_strtolower($event->user->email)))
            ->get()
            ->each(fn (AccessRequest $request) => $this->requests->delete($request));
    }
}
