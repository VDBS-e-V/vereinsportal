<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\ProfileVisibility;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\ProfilePhotos;
use App\Modules\Access\Services\Profiles;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Support\Html\RichText;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Konto › Profil: ansehen (Vorschau), bearbeiten (Angaben und Foto) und die Sichtbarkeit einstellen. Das Protokoll hält fest, dass etwas geändert wurde, nicht was.
 */
class AccountProfileController extends Controller
{
    public function __construct(private readonly Profiles $profiles, private readonly ProfilePhotos $photos) {}

    public function show(Request $request): View
    {
        $user = $this->user($request);

        return view('account.profile', [
            'user' => $user,
            'profile' => $this->profiles->forUser($user),
            'asPortal' => $this->profiles->preview($user, true),
            'asPublic' => $this->profiles->preview($user, false),
            'mine' => $this->profiles->shownTo($user, $user),
            'completeness' => $this->profiles->completeness($user),
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $this->user($request);
        $profile = $this->profiles->forUser($user);

        return view('account.profile-edit', [
            'user' => $user,
            'profile' => $profile,
            'defaultName' => $this->profiles->defaultName($user),
            'bio' => RichText::forEditor($profile->bio),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $request->merge(['bio' => RichText::normalize($request->input('bio'))]);

        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:80'],
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:20000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (mb_strlen(RichText::toPlain((string) $value)) > Profiles::BIO_LIMIT) {
                    $fail('Die Kurzvorstellung darf höchstens '.Profiles::BIO_LIMIT.' Zeichen lang sein.');
                }
            }],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'photo.mimes' => 'Bitte laden Sie ein JPEG-, PNG- oder WebP-Bild hoch.',
            'photo.max' => 'Das Foto darf höchstens 4 MB groß sein.',
        ]);

        $photoChanged = false;

        if ($request->hasFile('photo')) {
            try {
                $this->photos->store($user, $request->file('photo'));
                $photoChanged = true;
            } catch (DomainException $exception) {
                throw ValidationException::withMessages(['photo' => $exception->getMessage()]);
            }
        } elseif ($request->boolean('remove_photo') && filled($user->avatar_path)) {
            $this->photos->remove($user);
            $photoChanged = true;
        }

        $this->profiles->save($user, $data);

        AuditLog::record(AuditEvent::ProfileEdited, $user, $user);

        if ($photoChanged) {
            AuditLog::record(AuditEvent::ProfilePhotoChanged, $user, $user);
        }

        return redirect()->route('account.profile')->with('success', 'Ihr Profil wurde gespeichert.');
    }

    public function visibility(Request $request): View
    {
        $user = $this->user($request);

        return view('account.profile-visibility', [
            'user' => $user,
            'profile' => $this->profiles->forUser($user),
            'fields' => Profiles::FIELDS,
        ]);
    }

    public function updateVisibility(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $rules = ['consent' => ['nullable', 'boolean']];

        foreach (array_keys(Profiles::FIELDS) as $field) {
            $rules['visibility.'.$field] = ['required', 'in:'.implode(',', ProfileVisibility::values())];
        }

        $data = $request->validate($rules, ['visibility.*.required' => 'Bitte wählen Sie für jede Angabe eine Sichtbarkeit.', 'visibility.*.in' => 'Bitte wählen Sie eine gültige Sichtbarkeit.']);

        $current = $this->profiles->forUser($user);
        $widens = collect(array_keys(Profiles::FIELDS))->contains(fn (string $field): bool => $data['visibility'][$field] !== ProfileVisibility::Private->value
            && $current->visibilityOf($field)->value !== $data['visibility'][$field]);

        if ($widens && ! $request->boolean('consent')) {
            throw ValidationException::withMessages(['consent' => 'Bitte bestätigen Sie, dass die gewählten Angaben für andere sichtbar sein dürfen.']);
        }

        $this->profiles->setVisibility($user, $data['visibility'], $request->boolean('consent'));

        AuditLog::record(AuditEvent::ProfileVisibilityChanged, $user, $user);

        return redirect()->route('account.profile.visibility')->with('success', 'Die Sichtbarkeit wurde gespeichert.');
    }

    /** Das Foto eines Profils, nur im Rahmen der gewählten Sichtbarkeit (auch für Gäste, wenn „Öffentlich“ gewählt ist). */
    public function photo(User $user): StreamedResponse
    {
        $viewer = Auth::user();

        abort_unless(filled($user->avatar_path) && $this->profiles->mayViewPhoto($user, $viewer instanceof User ? $viewer : null), 404);
        abort_unless(Storage::disk(ProfilePhotos::DISK)->exists($user->avatar_path), 404);

        $visibility = $this->profiles->forUser($user)->visibilityOf('photo');

        return Storage::disk(ProfilePhotos::DISK)->response($user->avatar_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => ($visibility === ProfileVisibility::Public ? 'public' : 'private').', max-age=3600',
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless($this->profiles->allowedFor($user), 403);

        return $user;
    }
}
