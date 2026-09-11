<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Models\EmailDelivery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class EmailDeliveryIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:254'],
            'status' => ['nullable', Rule::enum(EmailDeliveryStatus::class)],
            'template' => ['nullable', 'string', 'max:150'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;
        $template = trim((string) ($validated['template'] ?? ''));

        $deliveries = EmailDelivery::query()
            ->with('templateVersion.template')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('recipient_email', 'like', '%'.$search.'%')
                        ->orWhere('subject', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($template !== '', function ($query) use ($template): void {
                $query->whereHas(
                    'templateVersion.template',
                    fn ($query) => $query->where('key', $template),
                );
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('administration.communication.deliveries.index', [
            'deliveries' => $deliveries,
            'search' => $search,
            'status' => $status,
            'template' => $template,
            'statuses' => EmailDeliveryStatus::cases(),
            'breadcrumbs' => [
                ['label' => 'Verwaltung', 'url' => route('administration.home')],
                ['label' => 'Kommunikation', 'url' => route('administration.communication.templates.index')],
                ['label' => 'Versandhistorie', 'url' => null],
            ],
        ]);
    }
}
