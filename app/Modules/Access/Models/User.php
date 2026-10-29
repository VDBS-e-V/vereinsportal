<?php

namespace App\Modules\Access\Models;

use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Mail\PasswordResetMail;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\HasApiTokens;

/**
 * Modul: Access.
 *
 * Login-Konto. Traegt bewusst keine Stammdaten (Name, Adresse) selbst -
 * die liegen auf der zugehoerigen Person (Modul People).
 *
 * @property string|null $remember_token
 * @property TwoFactorMethodType|null $preferred_two_factor_method
 * @property string|null $avatar_path
 * @property-read Person|null $person
 */
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'person_id',
        'email',
        'password',
        'status',
        'preferred_two_factor_method',
        'session_version',
        'force_password_change_at',
        'last_login_at',
        'avatar_path',
        'anonymized_at',
        'anonymized_ref',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'preferred_two_factor_method' => TwoFactorMethodType::class,
            'email_verified_at' => 'datetime',
            'force_password_change_at' => 'datetime',
            'last_login_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Versendet den Link zum Zurücksetzen des Passworts als eigene deutsche E-Mail (statt der Standard-Benachrichtigung). */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('password.reset', ['token' => $token, 'email' => $this->email]);

        Mail::to($this->email)->send(new PasswordResetMail($url, (int) config('auth.passwords.users.expire', 60)));
    }

    public function avatarUrl(): ?string
    {
        if (! is_string($this->avatar_path) || $this->avatar_path === '') {
            return null;
        }

        return route('my.account.avatar', [
            'v' => substr(hash('sha256', $this->avatar_path), 0, 12),
        ]);
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return HasMany<EmailChangeRequest, $this> */
    public function emailChangeRequests(): HasMany
    {
        return $this->hasMany(EmailChangeRequest::class);
    }

    /** @return HasMany<TwoFactorMethod, $this> */
    public function twoFactorMethods(): HasMany
    {
        return $this->hasMany(TwoFactorMethod::class);
    }

    /** @return HasMany<TwoFactorEmailChallenge, $this> */
    public function twoFactorEmailChallenges(): HasMany
    {
        return $this->hasMany(TwoFactorEmailChallenge::class);
    }

    /** @return HasMany<TwoFactorRecoveryCode, $this> */
    public function twoFactorRecoveryCodes(): HasMany
    {
        return $this->hasMany(TwoFactorRecoveryCode::class);
    }

    /** @return HasMany<RoleAssignment, $this> */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }
}
