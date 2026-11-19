<?php

namespace App\Modules\MailBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\MailBuilder\Mail\BuilderTestMail;
use App\Modules\MailBuilder\Models\MailAsset;
use App\Modules\MailBuilder\Models\MailLayout;
use App\Modules\MailBuilder\Models\MailTemplate;
use App\Modules\MailBuilder\Models\MailTemplateVersion;
use App\Modules\MailBuilder\Services\MailComposer;
use App\Modules\MailBuilder\Services\SystemMailTemplates;
use App\Modules\MailBuilder\Support\BlockSchema;
use App\Modules\MailBuilder\Support\RenderedMail;
use App\Modules\MailBuilder\Support\SystemMail;
use App\Modules\MailBuilder\Support\SystemMails;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** Systemmails im Mail-Builder: Liste, Bearbeiten, Vorschau, Testmail, Zurücksetzen und frühere Stände. */
class SystemMailController extends Controller
{
    public function __construct(private readonly SystemMailTemplates $templates, private readonly MailComposer $composer) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);
        $stored = MailTemplate::query()->whereNotNull('system_key')->pluck('updated_at', 'system_key');

        return view('mail-builder.system-index', [
            'groups' => SystemMails::grouped(),
            'stored' => $stored,
            'canEdit' => fn (SystemMail $mail): bool => $this->templates->canEdit($mail, $user),
        ]);
    }

    public function edit(Request $request, string $key): View
    {
        $mail = $this->mail($key);
        $user = $this->user($request);
        $current = $this->templates->current($mail);
        $template = $this->templates->stored($key);

        return view('mail-builder.editor', [
            'mail' => $mail,
            'current' => $current,
            'readonly' => ! $this->templates->canEdit($mail, $user),
            'versions' => $template?->versions()->with('savedBy.person')->get() ?? collect(),
            'assets' => MailAsset::query()->orderByDesc('id')->get()->map(fn (MailAsset $asset): array => ['id' => $asset->id, 'name' => $asset->original_name, 'url' => $asset->url(), 'alt' => $asset->alt_default])->all(),
            'types' => BlockSchema::TYPES,
            'placeholders' => collect($mail->placeholders)->map(fn (array $p): string => $p['label'])->all() + collect(SystemMails::COMMON)->map(fn (array $p): string => $p['label'])->all(),
            'protected' => $mail->protected(),
            'blank' => collect(array_keys(BlockSchema::TYPES))->mapWithKeys(fn (string $type): array => [$type => BlockSchema::make($type)])->all(),
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $mail = $this->mail($key);
        $data = $request->validate(['doc' => ['required', 'string', 'max:300000'], 'subject' => ['required', 'string', 'max:200'], 'preheader' => ['nullable', 'string', 'max:200']], ['subject.required' => 'Bitte einen Betreff angeben.']);
        $doc = json_decode($data['doc'], true);

        try {
            $this->templates->save($mail, is_array($doc) ? $doc : [], $data['subject'], $data['preheader'] ?? null, $this->user($request));
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['doc' => $e->getMessage()]);
        }

        return redirect()->route('mailbuilder.system.edit', $key)->with('success', 'Die Mail „'.$mail->name.'“ wurde gespeichert. Ab sofort gilt der neue Text.');
    }

    public function reset(Request $request, string $key): RedirectResponse
    {
        $mail = $this->mail($key);

        try {
            $this->templates->reset($mail, $this->user($request));
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()->route('mailbuilder.system.edit', $key)->with('success', 'Die Mail „'.$mail->name.'“ ist auf den Standardtext zurückgesetzt.');
    }

    public function restore(Request $request, string $key, MailTemplateVersion $version): RedirectResponse
    {
        $mail = $this->mail($key);

        try {
            $this->templates->restore($mail, $version, $this->user($request));
        } catch (DomainException $e) {
            return back()->withErrors(['doc' => $e->getMessage()]);
        }

        return redirect()->route('mailbuilder.system.edit', $key)->with('success', 'Der frühere Stand wurde wiederhergestellt.');
    }

    /** Vorschau des gerade bearbeiteten Stands mit Beispielwerten (JSON für den Builder). */
    public function preview(Request $request, string $key): JsonResponse
    {
        $mail = $this->mail($key);
        $rendered = $this->render($request, $mail);

        return response()->json(['html' => $rendered->html, 'text' => $rendered->text, 'subject' => $rendered->subject, 'issues' => $this->templates->check($mail, $this->doc($request), (string) $request->input('subject', $mail->subject))]);
    }

    public function test(Request $request, string $key): JsonResponse
    {
        $mail = $this->mail($key);
        $user = $this->user($request);
        $rendered = $this->render($request, $mail);

        Mail::to((string) $user->email)->send(new BuilderTestMail($rendered->subject, $rendered->html, $rendered->text));

        return response()->json(['message' => 'Die Testmail geht an '.$user->email.'.']);
    }

    private function render(Request $request, SystemMail $mail): RenderedMail
    {
        $layout = MailLayout::current();
        $doc = $this->doc($request);

        return $this->composer->render($doc, $mail->samples(), ['subject' => (string) $request->input('subject', $mail->subject), 'raw' => $mail->raw(), 'allowed' => $mail->allowed(), 'protected' => $mail->protected(), 'force_dark' => $request->boolean('dark')], $layout);
    }

    /** @return array<string, mixed> */
    private function doc(Request $request): array
    {
        $doc = $request->input('doc');
        $doc = is_string($doc) ? json_decode($doc, true) : $doc;
        $doc = is_array($doc) ? $doc : [];
        $doc['preheader'] = (string) $request->input('preheader', $doc['preheader'] ?? '');

        return $doc;
    }

    private function mail(string $key): SystemMail
    {
        return SystemMails::find($key) ?? abort(404);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
