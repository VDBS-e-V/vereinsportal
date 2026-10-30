<?php

namespace App\Modules\AccessRequests\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Services\RoleAssignments;
use App\Modules\AccessGovernance\Services\UserAdministration;
use App\Modules\AccessRequests\Enums\AccessRequestStatus;
use App\Modules\AccessRequests\Mail\AccessRequestNoticeMail;
use App\Modules\AccessRequests\Mail\AccessRequestReceivedMail;
use App\Modules\AccessRequests\Mail\AccessRequestRejectedMail;
use App\Modules\AccessRequests\Mail\NewAccessRequestMail;
use App\Modules\AccessRequests\Models\AccessRequest;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\People\Models\Person;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Anträge auf Zugang zum Portal: Eingang über das öffentliche Formular, Prüfung und Entscheidung in der Geschäftsstelle. Bei der Annahme entsteht
 * ein Konto (eingeladen, mit der gewählten Rolle). Die Nachweise werden mit der Entscheidung gelöscht, entschiedene Anträge nach der Aufbewahrungsfrist
 * (config portal.access_requests). Gibt es die Adresse schon, wird nichts angelegt; die Adresse erhält eine neutrale Nachricht.
 */
final class AccessRequests
{
    public const DISK = 'local';

    public const TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    public function __construct(
        private readonly UserAdministration $users,
        private readonly RoleAssignments $roles,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validierte Formulardaten
     * @param  list<UploadedFile>  $files
     */
    public function submit(array $data, array $files = []): void
    {
        $email = mb_strtolower(trim((string) $data['email']));

        $known = User::query()->where('email', $email)->exists()
            || AccessRequest::query()->where('email', $email)->whereIn('status', [AccessRequestStatus::New->value, AccessRequestStatus::InReview->value])->exists();

        if ($known) {
            Mail::to($email)->send(new AccessRequestNoticeMail(route('login'), route('password.request'), route('portal.contact')));

            return;
        }

        $request = DB::transaction(function () use ($data, $email, $files): AccessRequest {
            $request = AccessRequest::query()->create([
                'status' => AccessRequestStatus::New,
                'connection' => $data['connection'],
                'organization' => filled($data['organization'] ?? null) ? trim($data['organization']) : null,
                'details' => filled($data['details'] ?? null) ? trim($data['details']) : null,
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $email,
                'birth_date' => $data['birth_date'],
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                'consented_at' => now(),
                'terms_version' => (string) config('portal.access_requests.terms_version'),
            ]);

            foreach ($files as $file) {
                $mime = (string) $file->getMimeType();

                if (! isset(self::TYPES[$mime])) {
                    continue;
                }

                $path = 'access-requests/'.$request->id.'/'.Str::uuid().'.'.self::TYPES[$mime];
                Storage::disk(self::DISK)->putFileAs(dirname($path), $file, basename($path));

                $request->files()->create([
                    'original_name' => Str::limit(preg_replace('/[^\p{L}\p{N}\s._()\-]/u', '_', basename(str_replace('\\', '/', $file->getClientOriginalName()))) ?: 'nachweis', 200, ''),
                    'path' => $path,
                    'mime' => $mime,
                    'size' => (int) $file->getSize(),
                ]);
            }

            return $request;
        });

        Mail::to($email)->send(new AccessRequestReceivedMail($request->fullName(), $request->id));
        $this->notifyStaff($request);
    }

    public function startReview(AccessRequest $request): void
    {
        if ($request->status === AccessRequestStatus::New) {
            $request->forceFill(['status' => AccessRequestStatus::InReview])->save();
        }
    }

    /**
     * Nimmt den Antrag an: Person und Konto entstehen (Einladung per E-Mail), optional mit einer Rolle.
     *
     * @throws DomainException
     */
    public function approve(AccessRequest $request, User $by, ?RoleKey $role = null, ?string $note = null): User
    {
        $this->ensureOpen($request);

        if (User::query()->where('email', $request->email)->exists()) {
            throw new DomainException('Zu dieser E-Mail-Adresse gibt es schon ein Konto.');
        }

        $person = Person::query()->where('email', $request->email)->first();

        if ($person instanceof Person) {
            $user = $this->users->createForPerson($person, $by);

            if ($role !== null && ($roleModel = Role::query()->where('key', $role->value)->first()) instanceof Role) {
                $this->roles->assign($user, $roleModel, $by);
            }
        } else {
            $user = $this->users->create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'birth_date' => $request->birth_date->toDateString(),
                'email' => $request->email,
            ], $by, $role);
        }

        $this->decide($request, AccessRequestStatus::Approved, $by, $note, $user);

        AuditLog::record(AuditEvent::AccessRequestApproved, $by, $user, ['rolle' => $role?->value]);

        return $user;
    }

    /** @throws DomainException */
    public function reject(AccessRequest $request, User $by, ?string $note = null): void
    {
        $this->ensureOpen($request);

        $name = $request->fullName();
        $email = $request->email;

        $this->decide($request, AccessRequestStatus::Rejected, $by, $note, null);

        Mail::to($email)->send(new AccessRequestRejectedMail($name, filled($note) ? trim($note) : null, route('portal.contact')));

        AuditLog::record(AuditEvent::AccessRequestRejected, $by);
    }

    /** Löscht entschiedene Anträge nach der Aufbewahrungsfrist und liegengebliebene offene Anträge. @return array{decided: int, open: int} */
    public function prune(): array
    {
        $decidedDays = (int) config('portal.access_requests.retention_days');
        $openDays = (int) config('portal.access_requests.open_retention_days');
        $count = ['decided' => 0, 'open' => 0];

        if ($decidedDays > 0) {
            AccessRequest::query()->whereNotNull('decided_at')->where('decided_at', '<', now()->subDays($decidedDays))->get()->each(function (AccessRequest $request) use (&$count): void {
                $this->delete($request);
                $count['decided']++;
            });
        }

        if ($openDays > 0) {
            AccessRequest::query()->whereNull('decided_at')->where('created_at', '<', now()->subDays($openDays))->get()->each(function (AccessRequest $request) use (&$count): void {
                $this->delete($request);
                $count['open']++;
            });
        }

        return $count;
    }

    /** Löscht den Antrag mit allen Nachweisen. */
    public function delete(AccessRequest $request): void
    {
        $this->deleteFiles($request);
        $request->delete();
    }

    private function ensureOpen(AccessRequest $request): void
    {
        if (! $request->status->isOpen()) {
            throw new DomainException('Dieser Antrag ist schon entschieden.');
        }
    }

    private function decide(AccessRequest $request, AccessRequestStatus $status, User $by, ?string $note, ?User $user): void
    {
        $request->forceFill([
            'status' => $status,
            'decided_at' => now(),
            'decided_by_user_id' => $by->id,
            'decision_note' => filled($note) ? trim($note) : null,
            'user_id' => $user?->id,
        ])->save();

        $this->deleteFiles($request);
    }

    private function deleteFiles(AccessRequest $request): void
    {
        foreach ($request->files()->get() as $file) {
            Storage::disk(self::DISK)->delete($file->path);
            $file->delete();
        }

        Storage::disk(self::DISK)->deleteDirectory('access-requests/'.$request->id);
    }

    private function notifyStaff(AccessRequest $request): void
    {
        if (! config('portal.access_requests.notify_staff')) {
            return;
        }

        $url = route('access-requests.show', $request);

        User::query()
            ->where('status', UserStatus::Active->value)
            ->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', RoleKey::AdministrationStaff->value)))
            ->get()
            ->each(fn (User $staff) => Mail::to($staff->email)->send(new NewAccessRequestMail($url)));
    }
}
