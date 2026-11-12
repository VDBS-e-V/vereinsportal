<?php

namespace App\Modules\Contracts\Services;

use App\Modules\Access\Models\User;
use App\Modules\Contracts\Models\ContractorProfile;
use App\Modules\Forms\Services\Iban;
use DomainException;

/** Vertragsdaten einer Person mit Teamkonto: Steuernummer und IBAN liegen verschlüsselt, die IBAN wird geprüft. */
final class ContractorProfiles
{
    public function for(User $user): ?ContractorProfile
    {
        return ContractorProfile::query()->where('user_id', $user->id)->first();
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws DomainException
     */
    public function save(User $user, array $input): ContractorProfile
    {
        $iban = trim((string) ($input['iban'] ?? ''));

        if ($iban !== '' && ! Iban::valid($iban)) {
            throw new DomainException('Die IBAN ist nicht gültig.');
        }

        $tax = trim((string) ($input['tax_number'] ?? ''));

        if ($tax !== '' && preg_match('/^[0-9A-Za-z\/ -]{6,20}$/', $tax) !== 1) {
            throw new DomainException('Die Steuernummer hat ein ungewöhnliches Format. Erlaubt sind Ziffern, Buchstaben, Schrägstrich und Bindestrich (6 bis 20 Zeichen).');
        }

        $vat = $input['vat_mode'] ?? null;
        $clean = fn (string $key): ?string => filled($input[$key] ?? null) ? trim((string) $input[$key]) : null;

        return ContractorProfile::query()->updateOrCreate(['user_id' => $user->id], [
            'tax_number' => $tax !== '' ? $tax : null,
            'vat_mode' => in_array($vat, ['19', '7', 'none'], true) ? $vat : null,
            'bank_name' => $clean('bank_name'), 'bic' => $clean('bic'),
            'iban' => $iban !== '' ? Iban::pretty($iban) : null,
            'account_holder' => $clean('account_holder'),
        ]);
    }

    public function delete(User $user): void
    {
        ContractorProfile::query()->where('user_id', $user->id)->delete();
    }
}
