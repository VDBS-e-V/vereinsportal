<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Models\EmailDelivery;
use Illuminate\Contracts\View\View;

final class EmailDeliveryShowController extends Controller
{
    public function __invoke(EmailDelivery $emailDelivery): View
    {
        $emailDelivery->load('templateVersion.template', 'sender');

        return view('administration.communication.deliveries.show', [
            'delivery' => $emailDelivery,
            'breadcrumbs' => [
                ['label' => 'Verwaltung', 'url' => route('administration.home')],
                ['label' => 'Kommunikation', 'url' => route('administration.communication.templates.index')],
                ['label' => 'Versandhistorie', 'url' => route('administration.communication.deliveries.index')],
                ['label' => 'Versand #'.$emailDelivery->id, 'url' => null],
            ],
        ]);
    }
}
