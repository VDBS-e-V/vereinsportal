<?php

namespace App\Modules\Seminars\Support;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Services\SeminarDocuments;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Gemeinsamer Ablauf für das Hochladen des Schulvertrags durch die Geschäftsstelle oder die Schule. */
final class SchoolContractUpload
{
    public static function handle(Request $request, Seminar $seminar, SeminarDocuments $documents, User $by, RedirectResponse $redirect): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file'],
            'signed_on' => ['required', 'date', 'before_or_equal:today', 'after:2020-01-01'],
        ], ['file.required' => 'Bitte wählen Sie die Datei mit dem unterschriebenen Vertrag.', 'signed_on.required' => 'Bitte geben Sie das Datum der Unterschrift an.', 'signed_on.before_or_equal' => 'Das Datum darf nicht in der Zukunft liegen.']);

        if (! in_array($seminar->status, [SeminarStatus::QuoteSent, SeminarStatus::Released], true)) {
            return back()->withErrors(['contract' => 'Den Schulvertrag können Sie hochladen, sobald der Kostenvoranschlag gesendet ist.']);
        }

        try {
            $documents->add($seminar, $request->file('file'), SeminarDocument::SCHOOL_CONTRACT, 'Schulvertrag', $by);
        } catch (DomainException $exception) {
            return back()->withErrors(['contract' => $exception->getMessage()]);
        }

        $seminar->forceFill(['school_contract_signed_on' => $data['signed_on']])->save();

        return $redirect->with('success', 'Der unterschriebene Schulvertrag ist gespeichert.');
    }
}
