<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Services\PortalTick;
use Illuminate\Http\Response;

/**
 * Cron-Aufruf für Webhosting: Ein externer Dienst (z. B. cron-job.org) ruft jede Minute /cron/{key} auf. Der Schlüssel steht in CRON_KEY
 * (mindestens 32 Zeichen). Ohne gesetzten Schlüssel oder bei falschem Schlüssel gibt es die Adresse nicht (404). Die Antwort ist leer (204).
 */
class CronController extends Controller
{
    public function __invoke(string $key, PortalTick $tick): Response
    {
        $configured = (string) config('portal.cron.key', '');

        abort_if(strlen($configured) < 32 || ! hash_equals($configured, $key), 404);

        @set_time_limit($tick->seconds() + 30);
        @ignore_user_abort(true);

        $result = $tick->run('web');

        return response('', $result['status'] === 'error' ? 500 : 204);
    }
}
