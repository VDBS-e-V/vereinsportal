<?php

namespace App\Modules\MailBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\MailBuilder\Models\MailAsset;
use App\Modules\MailBuilder\Services\MailAssets;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Bilder für Mails (Logo, Bildblöcke): hochladen und, wenn sie nirgends benutzt werden, wieder löschen. */
class MailAssetController extends Controller
{
    public function __construct(private readonly MailAssets $assets) {}

    public function index(): View
    {
        return view('mail-builder.assets', ['assets' => MailAsset::query()->orderByDesc('id')->get(), 'maxKb' => MailAssets::MAX_KB]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['file' => ['required', 'file'], 'alt' => ['nullable', 'string', 'max:200']], ['file.required' => 'Bitte wählen Sie ein Bild.']);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        try {
            $asset = $this->assets->store($request->file('file'), $user, $data['alt'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        AuditLog::record(AuditEvent::MailDesignChanged, $user, null, ['aktion' => 'Mailbild hochgeladen', 'datei' => $asset->original_name]);

        return redirect()->route('mailbuilder.assets.index')->with('success', 'Das Bild wurde hochgeladen.');
    }

    public function destroy(Request $request, MailAsset $asset): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $inUse = in_array($asset->id, $this->assets->usedIds(), true);

        if ($inUse) {
            return back()->withErrors(['file' => 'Das Bild wird noch in einer Mail oder als Logo benutzt. Tauschen Sie es zuerst aus.']);
        }

        Storage::disk(MailAsset::DISK)->delete($asset->path);
        $asset->delete();
        AuditLog::record(AuditEvent::MailDesignChanged, $user, null, ['aktion' => 'Mailbild gelöscht', 'datei' => $asset->original_name]);

        return redirect()->route('mailbuilder.assets.index')->with('success', 'Das Bild wurde gelöscht.');
    }
}
