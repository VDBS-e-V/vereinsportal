<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingAttachment;
use App\Modules\Mailings\Services\MailAttachments;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Anhänge eines Entwurfs (Vorstand, Geschäftsstelle): hochladen, entfernen, herunterladen. Hochladen und Entfernen nur die Autorin des Entwurfs. */
class MailingAttachmentController extends Controller
{
    public function __construct(private readonly MailAttachments $attachments) {}

    public function store(Request $request, Mailing $mailing): RedirectResponse
    {
        $this->authorDraft($mailing);

        $request->validate(
            ['attachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$this->attachments->maxKb()]],
            ['attachment.required' => 'Bitte wählen Sie eine Datei.', 'attachment.mimes' => 'Erlaubt sind PDF, JPEG und PNG.', 'attachment.max' => 'Die Datei ist zu groß.', 'attachment.file' => 'Die Datei konnte nicht hochgeladen werden.', 'attachment.uploaded' => 'Die Datei konnte nicht hochgeladen werden.']
        );

        try {
            $this->attachments->add($mailing, $request->file('attachment'));
        } catch (DomainException $exception) {
            return $this->back($request, $mailing)->with('error', $exception->getMessage());
        }

        return $this->back($request, $mailing)->with('success', 'Der Anhang wurde hinzugefügt.');
    }

    public function destroy(Request $request, Mailing $mailing, MailingAttachment $attachment): RedirectResponse
    {
        $this->authorDraft($mailing);
        abort_unless($attachment->mailing_id === $mailing->id, 404);

        $attachment->delete();

        return $this->back($request, $mailing)->with('success', 'Der Anhang wurde entfernt.');
    }

    public function download(Mailing $mailing, MailingAttachment $attachment): StreamedResponse
    {
        $user = $this->user();
        abort_unless($attachment->mailing_id === $mailing->id, 404);
        abort_unless($user->id === $mailing->sent_by_user_id || $user->can('mailings.approve') || $mailing->addressesVisibleTo($user), 403);
        abort_unless(Storage::disk(MailingAttachment::DISK)->exists($attachment->path), 404);

        return Storage::disk(MailingAttachment::DISK)->download($attachment->path, $attachment->original_name, ['Content-Type' => $attachment->mime]);
    }

    private function authorDraft(Mailing $mailing): void
    {
        abort_unless($mailing->sent_by_user_id === $this->user()->id && $mailing->status === Mailing::STATUS_DRAFT, 404);
    }

    private function back(Request $request, Mailing $mailing): RedirectResponse
    {
        return redirect()->route(implode('.', array_slice(explode('.', (string) $request->route()?->getName()), 0, 2)).'.create', ['entwurf' => $mailing->id]);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
