<?php

namespace App\Modules\Communication\Enums;

enum EmailDeliveryType: string
{
    case System = 'system';
    case Manual = 'manual';
    case Test = 'test';
}
