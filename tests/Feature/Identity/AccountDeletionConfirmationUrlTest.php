<?php

use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Support\AccountDeletionConfirmationUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

beforeEach(function () {
    Route::get(
        '/identity/account-deletion/confirm/{publicId}',
        fn () => 'ok',
    )->name('identity.account-deletion.confirm');

    Route::getRoutes()->refreshNameLookups();
});

it('creates a signed account deletion confirmation url valid for three days', function () {
    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => null,
    ]);

    $service = app(
        AccountDeletionConfirmationUrl::class
    );

    $expiresAt = $service->expiresAt();

    $url = $service->create(
        $deletionRequest,
        $expiresAt,
    );

    $request = Request::create(
        $url,
        'GET',
    );

    expect(
        URL::hasValidSignature($request)
    )->toBeTrue();

    $validForSeconds =
        $expiresAt->timestamp - now()->timestamp;

    expect($validForSeconds)
        ->toBeGreaterThanOrEqual(259199)
        ->toBeLessThanOrEqual(259200);
});

it('expires the account deletion confirmation url after three days', function () {
    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => null,
    ]);

    $service = app(
        AccountDeletionConfirmationUrl::class
    );

    $expiresAt = $service->expiresAt();

    $url = $service->create(
        $deletionRequest,
        $expiresAt,
    );

    $this->travel(3)->days();
    $this->travel(1)->second();

    $request = Request::create(
        $url,
        'GET',
    );

    expect(
        URL::hasValidSignature($request)
    )->toBeFalse();
});
