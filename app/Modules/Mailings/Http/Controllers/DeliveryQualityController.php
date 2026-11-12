<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailSuppression;
use App\Modules\Mailings\Models\MailUnsubscribe;
use App\Modules\Mailings\Services\SenderCheck;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** Zustellqualität (Geschäftsstelle, Vorstand): Fehlerquote, neue Sperren und Abmeldungen je Monat als Tabelle, dazu die Prüfung der Absenderdomain. */
class DeliveryQualityController extends Controller
{
    public function index(): View
    {
        $from = now()->startOfMonth()->subMonths(11);
        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $months[$from->copy()->addMonths($i)->format('Y-m')] = ['sent_letters' => 0, 'recipients' => 0, 'sent' => 0, 'failed' => 0, 'suppressed' => 0, 'unsubscribed' => 0];
        }

        $finished = [Mailing::STATUS_DONE, Mailing::STATUS_FAILED, Mailing::STATUS_CANCELLED];

        foreach (Mailing::query()->whereIn('status', $finished)->where('created_at', '>=', $from)->get(['created_at', 'recipients_count', 'sent_count', 'failed_count']) as $mailing) {
            $key = $mailing->created_at->format('Y-m');
            $months[$key]['sent_letters']++;
            $months[$key]['recipients'] += (int) $mailing->recipients_count;
            $months[$key]['sent'] += (int) $mailing->sent_count;
            $months[$key]['failed'] += (int) $mailing->failed_count;
        }

        foreach (MailSuppression::query()->where('created_at', '>=', $from)->get(['created_at']) as $row) {
            $months[$row->created_at->format('Y-m')]['suppressed']++;
        }

        foreach (MailUnsubscribe::query()->where('created_at', '>=', $from)->get(['created_at']) as $row) {
            $months[$row->created_at->format('Y-m')]['unsubscribed']++;
        }

        return view('mailings.quality', [
            'months' => array_reverse($months, true),
            'threshold' => (int) config('portal.mailings.failure_warning_percent', 5),
            'activeSuppressions' => MailSuppression::query()->active()->count(),
            'webhookConfigured' => filled(config('portal.mailings.webhook_secret')),
            'senderChecks' => session('sender_checks'),
        ]);
    }

    public function check(SenderCheck $check): RedirectResponse
    {
        return redirect()->route('mailings.quality.index')->with('sender_checks', $check->run());
    }
}
