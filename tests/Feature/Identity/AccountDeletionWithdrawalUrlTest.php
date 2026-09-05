<?php

use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Support\AccountDeletionWithdrawalUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

beforeEach(function () {
    Route::get(
        '/identity/account-deletion/withdraw/{publicId}',
        fn () => 'ok',
    )->name('identity.account-deletion.withdraw');

    Route::getRoutes()->refreshNameLookups();
});

it('creates a signed withdrawal url bound exactly to revoke_until', function () {
    $revokeUntil = now()->addDays(5);

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now(),
        'confirmed_at' => now(),
        'revoke_until' => $revokeUntil,
    ]);

    $url = app(
        AccountDeletionWithdrawalUrl::class
    )->create($deletionRequest);

    $request = Request::create($url, 'GET');

    expect(URL::hasValidSignature($request))
        ->toBeTrue();

    parse_str(
        (string) parse_url($url, PHP_URL_QUERY),
        $query,
    );

    expect((int) $query['expires'])
        ->toBe($deletionRequest->revoke_until->timestamp);
});

it('keeps the signed withdrawal url valid at the exact revoke_until boundary', function () {
    $revokeUntil = now()->addDays(5)->startOfSecond();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now(),
        'confirmed_at' => now(),
        'revoke_until' => $revokeUntil,
    ]);

    $url = app(
        AccountDeletionWithdrawalUrl::class
    )->create($deletionRequest);

    $this->travelTo($revokeUntil);

    $request = Request::create($url, 'GET');

    expect(URL::hasValidSignature($request))
        ->toBeTrue();
});

it('expires the signed withdrawal url after revoke_until', function () {
    $revokeUntil = now()->addDays(5)->startOfSecond();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now(),
        'confirmed_at' => now(),
        'revoke_until' => $revokeUntil,
    ]);

    $url = app(
        AccountDeletionWithdrawalUrl::class
    )->create($deletionRequest);

    $this->travelTo($revokeUntil->copy()->addSecond());

    $request = Request::create($url, 'GET');

    expect(URL::hasValidSignature($request))
        ->toBeFalse();
});
