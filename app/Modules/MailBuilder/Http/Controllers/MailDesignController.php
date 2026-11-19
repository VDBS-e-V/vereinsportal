<?php

namespace App\Modules\MailBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\MailBuilder\Models\MailAsset;
use App\Modules\MailBuilder\Models\MailLayout;
use App\Modules\MailBuilder\Services\MailComposer;
use App\Modules\MailBuilder\Support\BlockSchema;
use App\Modules\MailBuilder\Support\LayoutSettings;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Das zentrale Mail-Design: Farben, Schrift, Logo, Kopf und Fußzeile. Gilt für alle Systemmails (und später die Rundschreiben). */
class MailDesignController extends Controller
{
    public function __construct(private readonly MailComposer $composer) {}

    public function edit(): View
    {
        return view('mail-builder.design', [
            'settings' => LayoutSettings::normalize(MailLayout::current()->settings),
            'colors' => LayoutSettings::COLORS,
            'palette' => LayoutSettings::paletteOptions(),
            'fonts' => array_keys(LayoutSettings::FONTS),
            'assets' => MailAsset::query()->orderByDesc('id')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = $this->settings($request);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $layout = MailLayout::current();
        $layout->fill(['settings' => $settings, 'updated_by_user_id' => $user->id])->save();
        AuditLog::record(AuditEvent::MailDesignChanged, $user, null, ['aktion' => 'Design gespeichert']);

        return redirect()->route('mailbuilder.design.edit')->with('success', 'Das Mail-Design wurde gespeichert. Es gilt ab sofort für alle Mails.');
    }

    public function preview(Request $request): JsonResponse
    {
        $layout = new MailLayout(['name' => 'Vorschau', 'is_default' => true, 'settings' => $this->settings($request)]);
        $doc = BlockSchema::sanitize(['blocks' => [
            ['type' => 'heading', 'props' => ['text' => 'So sehen Ihre Mails aus', 'level' => 2]],
            ['type' => 'text', 'props' => ['html' => '<p>{anrede},</p><p>dies ist ein <strong>Beispieltext</strong> mit einem <a href="https://example.org">Link</a>. Überschriften, Absätze, Hinweise und Buttons übernehmen die Farben und die Schrift dieses Designs.</p>']],
            ['type' => 'callout', 'props' => ['tone' => 'info', 'html' => '<p>Das ist eine Hinweisbox.</p>']],
            ['type' => 'button', 'props' => ['label' => 'Zum Portal', 'url' => 'https://portal.example', 'style' => 'primary']],
            ['type' => 'button', 'props' => ['label' => 'Zweite Aktion', 'url' => 'https://portal.example', 'style' => 'secondary']],
        ]]);

        $rendered = $this->composer->render($doc, ['anrede' => 'Guten Tag Anna'], ['subject' => 'Vorschau', 'force_dark' => $request->boolean('dark')], $layout);

        return response()->json(['html' => $rendered->html, 'issues' => $rendered->issues]);
    }

    /** @return array<string, mixed> */
    private function settings(Request $request): array
    {
        $input = $request->validate([
            'width' => ['nullable', 'integer'], 'font' => ['nullable', 'string'], 'radius' => ['nullable', 'integer'],
            'background' => ['nullable', 'string', 'max:7'], 'card' => ['nullable', 'string', 'max:7'], 'text' => ['nullable', 'string', 'max:7'], 'muted' => ['nullable', 'string', 'max:7'],
            'link' => ['nullable', 'string', 'max:7'], 'button' => ['nullable', 'string', 'max:7'], 'button_text' => ['nullable', 'string', 'max:7'], 'accent' => ['nullable', 'string', 'max:7'],
            'logo_asset_id' => ['nullable', 'integer', 'exists:mail_assets,id'], 'logo_width' => ['nullable', 'integer'], 'logo_alt' => ['nullable', 'string', 'max:200'],
            'header_text' => ['nullable', 'string', 'max:200'], 'footer_html' => ['nullable', 'string', 'max:5000'],
        ]);
        $input['footer_html'] = RichText::normalize($input['footer_html'] ?? '');

        return LayoutSettings::normalize($input);
    }
}
