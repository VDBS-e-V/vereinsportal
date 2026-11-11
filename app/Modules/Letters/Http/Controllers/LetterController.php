<?php

namespace App\Modules\Letters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Letters\Models\LetterTemplate;
use App\Modules\Letters\Services\LetterGenerator;
use App\Modules\Seminars\Models\Seminar;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Schreiben zu einem Seminar erzeugen: Vorlage wählen, Empfänger (Schule oder Teamende), Betreff und Text ergänzen, Word-Datei laden. */
class LetterController extends Controller
{
    public function __construct(private readonly LetterGenerator $generator) {}

    public function generate(Request $request, Seminar $seminar): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'template_id' => ['required', 'integer', Rule::exists('letter_templates', 'id')],
            'to' => ['required', Rule::in(['school', 'team'])],
            'applications' => ['nullable', 'array'], 'applications.*' => ['integer'],
            'subject' => ['nullable', 'string', 'max:200'], 'freitext' => ['nullable', 'string', 'max:6000'],
        ], ['template_id.required' => 'Bitte wählen Sie eine Vorlage.']);

        try {
            $result = $this->generator->generate(LetterTemplate::query()->findOrFail($data['template_id']), $seminar, $data['to'], array_map('intval', $data['applications'] ?? []), $data['subject'] ?? null, $data['freitext'] ?? null, $this->user());
        } catch (DomainException $exception) {
            return redirect()->to(route('seminars.show', $seminar).'#letters-heading')->withInput()->with('error', $exception->getMessage());
        }

        return response()->download($result['path'], $result['name'])->deleteFileAfterSend();
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
