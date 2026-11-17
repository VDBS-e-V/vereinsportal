<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarNotifications;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Koordination: Ergebnislink festhalten und den Bericht an die Schule senden (nach der Prüfung der abgegebenen Unterlagen). */
class SeminarReportController extends Controller
{
    public function __construct(private readonly SeminarNotifications $notices, private readonly \App\Modules\Seminars\Nextcloud\SeminarNextcloud $nextcloud) {}

    public function send(Request $request, Seminar $seminar): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if (! in_array($seminar->status, [SeminarStatus::Reported, SeminarStatus::Held, SeminarStatus::Completed], true)) {
            return back()->with('warning', 'Den Bericht senden Sie nach der Durchführung.');
        }

        $data = $request->validate([
            'results_share_url' => ['nullable', 'string', 'max:500', 'url:https'],
            'results_expires_at' => ['nullable', 'date'],
            'text' => ['nullable', 'string', 'max:1500'],
        ], ['results_share_url.url' => 'Der Link muss mit https:// beginnen.']);

        // Mit eingerichteter Nextcloud und angelegtem Ordner entsteht der Link dort, wenn keiner eingetragen ist
        if (blank($data['results_share_url'] ?? null) && $seminar->results_share_url === null && $this->nextcloud->enabled() && $seminar->nextcloud_folder_path !== null) {
            try {
                $this->nextcloud->createResultsLink($seminar, $data['results_expires_at'] ?? null, $user);
                $seminar = $seminar->fresh();
            } catch (\Throwable $exception) {
                report($exception);

                return back()->with('warning', 'Der Ergebnislink konnte in der Nextcloud nicht erzeugt werden: '.$exception->getMessage());
            }
        }

        if (filled($data['results_share_url'] ?? null)) {
            $last = $seminar->sessions()->max('ends_at');
            $base = ($last !== null ? Carbon::parse($last) : now())->startOfDay();
            $minimum = $base->copy()->addYear();
            $data['results_expires_at'] = $data['results_expires_at'] ?? $base->copy()->addYears(max(1, (int) config('portal.seminars.retention.results_years', 2)))->toDateString();

            if (Carbon::parse($data['results_expires_at'])->lt($minimum)) {
                return back()->withErrors(['results_expires_at' => 'Der Ergebnislink muss mindestens ein Jahr nach dem Seminar gelten (bis '.$minimum->format('d.m.Y').' oder später).'])->withInput();
            }

            $seminar->forceFill(['results_share_url' => $data['results_share_url'], 'results_expires_at' => $data['results_expires_at'], 'results_expiry_notified_at' => null])->save();
        }

        $this->notices->reportToSchool($seminar->fresh(), $data['text'] ?? null, $user);
        $seminar->forceFill(['report_sent_at' => now()])->save();

        return redirect()->route('seminars.show', $seminar)->with('success', 'Der Bericht ging an die Schule.');
    }

    /** Ergebnislink verlängern (nie verkürzen). */
    public function extend(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['results_expires_at' => ['required', 'date']], ['results_expires_at.required' => 'Bitte geben Sie das neue Ablaufdatum an.']);

        if ($seminar->results_share_url === null || $seminar->results_expires_at === null) {
            return back()->with('warning', 'Zu diesem Seminar gibt es keinen Ergebnislink.');
        }

        if (Carbon::parse($data['results_expires_at'])->lte($seminar->results_expires_at)) {
            return back()->withErrors(['results_expires_at' => 'Das neue Datum muss nach dem bisherigen ('.$seminar->results_expires_at->format('d.m.Y').') liegen.']);
        }

        try {
            $this->nextcloud->extendResultsLink($seminar, $data['results_expires_at']);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('warning', 'Das neue Datum konnte nicht an die Nextcloud übergeben werden: '.$exception->getMessage());
        }

        $seminar->forceFill(['results_expires_at' => $data['results_expires_at'], 'results_expiry_notified_at' => null])->save();

        return redirect()->route('seminars.show', $seminar)->with('success', 'Der Ergebnislink gilt jetzt bis '.Carbon::parse($data['results_expires_at'])->format('d.m.Y').'. Stellen Sie die Verlängerung auch in der Nextcloud ein.');
    }
}
