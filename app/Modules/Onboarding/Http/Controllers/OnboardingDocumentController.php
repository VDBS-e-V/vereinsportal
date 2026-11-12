<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Onboarding\Enums\OnboardingDocumentKind;
use App\Modules\Onboarding\Models\OnboardingDocument;
use App\Modules\Onboarding\Services\OnboardingDocuments;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Download eines Onboarding-Dokuments. Satzung und Willkommensbrief sieht der Vorstand, die Erklärungen der Teamenden die Verwaltung der
 * Teamenden sowie die Teamenden selbst (nur die jeweils geltende Fassung).
 */
class OnboardingDocumentController extends Controller
{
    public function __construct(private readonly OnboardingDocuments $documents) {}

    public function download(OnboardingDocument $document): StreamedResponse
    {
        $allowed = in_array($document->kind, OnboardingDocumentKind::forMembers(), true)
            ? Gate::allows('memberships.view')
            : (Gate::allows('team.view') || (Gate::allows('team.onboarding') && $this->documents->current($document->kind)?->id === $document->id));

        abort_unless($allowed, 403);
        abort_unless(Storage::disk(OnboardingDocuments::DISK)->exists($document->path), 404);

        return Storage::disk(OnboardingDocuments::DISK)->download($document->path, $document->kind->label().'.pdf', ['Content-Type' => 'application/pdf']);
    }
}
