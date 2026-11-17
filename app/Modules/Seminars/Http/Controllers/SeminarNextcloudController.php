<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Nextcloud\SeminarNextcloud;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/** Koordination: Nextcloud-Ordner anlegen und abgleichen, Ergebnislink erzeugen oder „manuell erledigt“ vermerken. */
class SeminarNextcloudController extends Controller
{
    public function __construct(private readonly SeminarNextcloud $nextcloud) {}

    public function sync(Request $request, Seminar $seminar): RedirectResponse
    {
        if (! $this->nextcloud->enabled()) {
            return $this->back($seminar, null, 'Die Nextcloud-Anbindung ist nicht eingerichtet. Legen Sie den Ordner manuell an und vermerken Sie das hier.');
        }

        try {
            $this->nextcloud->provision($seminar, $this->actor($request));
        } catch (Throwable $exception) {
            return $this->back($seminar, null, 'Die Ordner konnten nicht angelegt werden: '.$exception->getMessage());
        }

        return $this->back($seminar, 'Die Nextcloud-Ordner sind angelegt, der Zugriff des Teams ist abgeglichen.');
    }

    public function manual(Request $request, Seminar $seminar): RedirectResponse
    {
        $seminar->forceFill(['nextcloud_manual' => true])->save();

        return $this->back($seminar, 'Vermerkt: Der Ordner wird von Hand angelegt.');
    }

    public function link(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['results_expires_at' => ['nullable', 'date']]);

        try {
            $result = $this->nextcloud->createResultsLink($seminar, $data['results_expires_at'] ?? null, $this->actor($request));
        } catch (DomainException $exception) {
            return $this->back($seminar, null, $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->back($seminar, null, 'Der Link konnte nicht erzeugt werden: '.$exception->getMessage());
        }

        return $this->back($seminar, 'Der Ergebnislink ist erzeugt und gilt bis '.\Carbon\Carbon::parse($result['expires'])->format('d.m.Y').'.');
    }

    private function back(Seminar $seminar, ?string $success, ?string $warning = null): RedirectResponse
    {
        return redirect()->route('seminars.show', $seminar)->with($warning !== null ? 'warning' : 'success', $warning ?? $success);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
