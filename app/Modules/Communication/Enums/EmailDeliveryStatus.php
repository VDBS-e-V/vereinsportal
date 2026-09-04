<?php

namespace App\Modules\Communication\Enums;

enum EmailDeliveryStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
}
