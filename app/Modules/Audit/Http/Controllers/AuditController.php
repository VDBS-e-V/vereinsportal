<?php

namespace App\Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Support\CsvCell;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Protokoll: gefilterte Liste, Einzelansicht und CSV-Export. */
class AuditController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): View
    {
        [$query, $filters] = $this->filtered($request);

        $entries = $query->paginate(self::PER_PAGE)->withQueryString();

        $labels = [
            'q' => fn ($value): string => 'Suche: '.$value,
            'category' => fn ($value): string => 'Gruppe: '.AuditEvent::categories()[$value],
            'event' => fn ($value): string => 'Ereignis: '.AuditEvent::from($value)->label(),
            'from' => fn ($value): string => 'Ab: '.Carbon::parse($value)->format('d.m.Y'),
            'to' => fn ($value): string => 'Bis: '.Carbon::parse($value)->format('d.m.Y'),
            'subject' => fn ($value): string => 'Betrifft: '.$this->subjectName($value),
        ];

        $active = [];

        foreach ($labels as $key => $label) {
            if ($filters[$key] !== null) {
                $active[] = ['label' => $label($filters[$key]), 'href' => $request->fullUrlWithoutQuery([$key, 'page'])];
            }
        }

        return view('admin.audit.index', [
            'entries' => $entries,
            'filters' => $filters,
            'active' => $active,
            'categoryOptions' => AuditEvent::categories(),
            'eventOptions' => collect(AuditEvent::cases())->mapWithKeys(fn (AuditEvent $case): array => [$case->value => $case->label()])->all(),
            'exportUrl' => route('admin.audit.export', array_filter($filters, fn ($value): bool => $value !== null)),
            'canViewUsers' => Gate::allows('users.view'),
        ]);
    }

    public function show(AuditEntry $entry): View
    {
        return view('admin.audit.show', [
            'entry' => $entry,
            'event' => $entry->eventType,
            'subjectUrl' => $this->subjectUrl($entry->subject_type, $entry->subject_id),
            'actorUrl' => $entry->actor_user_id !== null && Gate::allows('users.view') ? route('admin.users.show', $entry->actor_user_id) : null,
            'context' => $this->flatten($entry->context ?? []),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$query, $filters] = $this->filtered($request);
        $limit = (int) config('audit.export_limit', 10000);

        AuditLog::record(AuditEvent::AuditExported, $request->user(), null, ['filter' => array_filter($filters, fn ($value): bool => $value !== null)]);

        $name = 'protokoll-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query, $limit): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8-Kennzeichen, damit Excel Umlaute richtig zeigt
            fputcsv($out, ['Zeit', 'Ereignis', 'Gruppe', 'Akteur', 'Betroffen', 'Angaben', 'IP-Adresse'], ';');

            foreach ($query->limit($limit)->cursor() as $entry) {
                /** @var AuditEntry $entry */
                $event = $entry->eventType;

                fputcsv($out, array_map([self::class, 'safeCell'], [
                    $entry->occurred_at->format('d.m.Y H:i:s'),
                    $event?->label() ?? $entry->event,
                    $event !== null ? AuditEvent::categories()[$event->category()] : '',
                    $entry->actor_label ?? '',
                    $entry->subject_label ?? '',
                    collect($this->flatten($entry->context ?? []))->map(fn (string $value, string $key): string => $key.': '.$value)->implode('; '),
                    $entry->ip_address ?? '',
                ]), ';');
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Schützt Tabellenprogramme vor Formeln: Zellen, die mit = + - @ beginnen, bekommen ein Hochkomma. */
    public static function safeCell(mixed $value): string
    {
        return CsvCell::safe($value);
    }

    /**
     * @return array{0: Builder<AuditEntry>, 1: array<string, ?string>}
     */
    private function filtered(Request $request): array
    {
        $search = trim((string) $request->query('q', ''));
        $category = array_key_exists((string) $request->query('category', ''), AuditEvent::categories()) ? (string) $request->query('category') : null;
        $event = AuditEvent::tryFrom((string) $request->query('event', ''));
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));
        $subject = $this->subject((string) $request->query('subject', ''));

        $query = AuditEntry::query()
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $builder->where(fn (Builder $inner) => $inner->where('actor_label', 'like', $like)->orWhere('subject_label', 'like', $like));
            })
            ->when($category !== null, fn (Builder $builder) => $builder->whereIn('event', array_map(fn (AuditEvent $case): string => $case->value, AuditEvent::inCategory((string) $category))))
            ->when($event !== null, fn (Builder $builder) => $builder->where('event', $event?->value))
            ->when($from !== null, fn (Builder $builder) => $builder->where('occurred_at', '>=', Carbon::parse((string) $from)->startOfDay()))
            ->when($to !== null, fn (Builder $builder) => $builder->where('occurred_at', '<=', Carbon::parse((string) $to)->endOfDay()))
            ->when($subject !== null, fn (Builder $builder) => $builder->where('subject_type', $subject[0])->where('subject_id', $subject[1]))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        return [$query, [
            'q' => $search !== '' ? $search : null,
            'category' => $category,
            'event' => $event?->value,
            'from' => $from,
            'to' => $to,
            'subject' => $subject !== null ? $subject[0].':'.$subject[1] : null,
        ]];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)?->format('Y-m-d') === $value ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array{0: string, 1: int}|null */
    private function subject(string $value): ?array
    {
        if (preg_match('/^(user|person|partner|seminar):(\d+)$/', $value, $matches) !== 1) {
            return null;
        }

        return [$matches[1], (int) $matches[2]];
    }

    private function subjectName(string $value): string
    {
        $subject = $this->subject($value);

        if ($subject === null) {
            return $value;
        }

        $name = match ($subject[0]) {
            'user' => ($user = User::query()->with('person')->find($subject[1])) instanceof User ? AuditLog::label($user) : null,
            'partner' => Partner::query()->find($subject[1])?->name,
            'seminar' => \App\Modules\Seminars\Models\Seminar::query()->find($subject[1])?->title,
            default => Person::query()->find($subject[1])?->fullName(),
        };

        return $name ?? ['user' => 'Nutzer', 'partner' => 'Schule', 'seminar' => 'Seminar', 'person' => 'Person'][$subject[0]].' '.$subject[1];
    }

    private function subjectUrl(?string $type, ?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        return match (true) {
            $type === 'user' && Gate::allows('users.view') && User::query()->whereKey($id)->exists() => route('admin.users.show', $id),
            $type === 'person' && Gate::allows('people.view') && Person::query()->whereKey($id)->exists() => route('admin.people.show', $id),
            $type === 'partner' && Gate::allows('partners.view') && Partner::query()->whereKey($id)->exists() => route('partners.show', $id),
            $type === 'seminar' && Gate::allows('seminars.view') && \App\Modules\Seminars\Models\Seminar::query()->whereKey($id)->exists() => route('seminars.show', $id),
            default => null,
        };
    }

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<string, string>
     */
    private function flatten(array $context, string $prefix = ''): array
    {
        $flat = [];

        foreach ($context as $key => $value) {
            $name = $prefix.$key;

            if (is_array($value)) {
                $flat += array_is_list($value) ? [$name => implode(', ', array_map('strval', $value))] : $this->flatten($value, $name.'.');

                continue;
            }

            $flat[$name] = match (true) {
                is_bool($value) => $value ? 'ja' : 'nein',
                $value === null => '–',
                default => (string) $value,
            };
        }

        return $flat;
    }
}
