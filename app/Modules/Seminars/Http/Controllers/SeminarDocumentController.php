<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Services\SeminarDocuments;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Datei eines Seminars laden: Büro und Planung alle, die Schule ihren Schulvertrag. */
class SeminarDocumentController extends Controller
{
    public function __construct(private readonly SeminarDocuments $documents, private readonly SchoolAccounts $accounts) {}

    public function download(Request $request, SeminarDocument $document): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $partnerId = $user->can('school.area') ? $this->accounts->forUser($user)?->partner_id : null;

        abort_unless($this->documents->canDownload($user, $document, $partnerId), 404);

        return $this->documents->download($document);
    }
}
