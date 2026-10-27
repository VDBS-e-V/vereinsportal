<?php

use App\Modules\Access\Enums\EmailChangeRequestStatus;
use App\Modules\Access\Models\EmailChangeRequest;
use App\Modules\Access\Models\User;

it('stores an email change request with a casted status', function () {
    $user = User::factory()->create(['email' => 'old@example.test']);

    $request = EmailChangeRequest::query()->create([
        'public_id' => (string) Illuminate\Support\Str::ulid(),
        'user_id' => $user->id,
        'old_email' => 'old@example.test',
        'new_email' => 'new@example.test',
        'status' => EmailChangeRequestStatus::Pending,
        'expires_at' => now()->addDay(),
    ]);

    expect($request->status)->toBe(EmailChangeRequestStatus::Pending)
        ->and($request->user->is($user))->toBeTrue()
        ->and($user->emailChangeRequests->pluck('id'))->toContain($request->id);
});

it('tracks confirmation and supersession separately', function () {
    $user = User::factory()->create();

    $request = EmailChangeRequest::query()->create([
        'public_id' => (string) Illuminate\Support\Str::ulid(),
        'user_id' => $user->id,
        'old_email' => 'old@example.test',
        'new_email' => 'new@example.test',
        'status' => EmailChangeRequestStatus::Superseded,
        'expires_at' => now()->addDay(),
        'superseded_at' => now(),
    ]);

    expect($request->status)->toBe(EmailChangeRequestStatus::Superseded)
        ->and($request->confirmed_at)->toBeNull()
        ->and($request->superseded_at)->not->toBeNull();
});
