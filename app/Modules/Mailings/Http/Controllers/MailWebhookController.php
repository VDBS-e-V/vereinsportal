<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mailings\Services\BounceHandler;
use App\Modules\Mailings\Services\GenericBounceAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Eingehende Rückmeldungen des Versanddienstes (Bounces, Beschwerden). Gesichert durch ein gemeinsames Geheimnis: Der Dienst sendet im Kopf X-Signature
 * den HMAC-SHA256 des Inhalts. Ohne eingestelltes Geheimnis (MAILINGS_WEBHOOK_SECRET) gibt es die Adresse nicht.
 */
class MailWebhookController extends Controller
{
    public function __invoke(Request $request, BounceHandler $handler): JsonResponse
    {
        $secret = (string) config('portal.mailings.webhook_secret', '');

        abort_if($secret === '', 404);

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        abort_unless(hash_equals($expected, (string) $request->header('X-Signature')), 401);

        $result = $handler->handle((new GenericBounceAdapter)->parse($request->getContent()));

        return response()->json($result);
    }
}
