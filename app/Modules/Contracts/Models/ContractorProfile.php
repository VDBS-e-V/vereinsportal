<?php

namespace App\Modules\Contracts\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Contracts. Vertragsdaten einer Person mit Teamkonto (Steuernummer, IBAN verschlüsselt). */
class ContractorProfile extends Model
{
    protected $fillable = ['user_id', 'tax_number', 'vat_mode', 'bank_name', 'bic', 'iban', 'account_holder'];

    protected function casts(): array
    {
        return ['tax_number' => 'encrypted', 'iban' => 'encrypted'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
