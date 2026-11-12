<?php

namespace App\Modules\Partners\Enums;

/**
 * Bekannte Partner-Typen. Neue Typen werden als weiterer Case ergaenzt -
 * das ist ein Code-, kein Schema-Wechsel (die Spalte `partners.type`
 * bleibt ein string). Aktuell nur Schulen; z. B. Sponsoren oder andere
 * Vereine koennen spaeter folgen.
 */
enum PartnerType: string
{
    case School = 'school';
}
