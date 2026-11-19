<?php

use App\Modules\MailBuilder\Models\MailAsset;
use App\Modules\MailBuilder\Models\MailLayout;
use App\Modules\MailBuilder\Services\MailAssets;
use App\Modules\MailBuilder\Services\MailComposer;
use App\Modules\MailBuilder\Support\BlockSchema;
use App\Modules\MailBuilder\Support\LayoutSettings;
use App\Modules\MailBuilder\Support\MailCheck;
use App\Modules\MailBuilder\Support\MailRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** @param list<array<string, mixed>> $blocks */
function mbDoc(array $blocks, string $preheader = ''): array
{
    return ['version' => 1, 'preheader' => $preheader, 'blocks' => $blocks];
}

function mbBlock(string $type, array $props, ?array $visibleIf = null): array
{
    return ['id' => 'b'.mt_rand(1000, 9999), 'type' => $type, 'props' => $props, 'visible_if' => $visibleIf];
}

it('turns any input into a valid document and drops unsafe parts', function () {
    $doc = BlockSchema::sanitize([
        'preheader' => str_repeat('x', 500),
        'blocks' => [
            ['type' => 'script', 'props' => []],
            ['type' => 'button', 'props' => ['label' => 'Los', 'url' => 'javascript:alert(1)']],
            ['type' => 'button', 'props' => ['label' => 'Los', 'url' => 'https://example.org/a']],
            ['type' => 'button', 'props' => ['label' => 'Link', 'url' => '{link}']],
            ['type' => 'text', 'props' => ['html' => '<p onclick="x()">Hallo</p><script>alert(1)</script>']],
            'kaputt',
        ],
    ]);

    expect($doc['preheader'])->toHaveLength(200)
        ->and($doc['blocks'])->toHaveCount(4)
        ->and($doc['blocks'][0]['props']['url'])->toBe('')
        ->and($doc['blocks'][1]['props']['url'])->toBe('https://example.org/a')
        ->and($doc['blocks'][2]['props']['url'])->toBe('{link}')
        ->and($doc['blocks'][3]['props']['html'])->not->toContain('script')->not->toContain('onclick')
        ->and(BlockSchema::sanitize('nonsense')['blocks'])->toBe([])
        ->and(BlockSchema::safeUrl('data:text/html,x'))->toBeFalse();
});

it('keeps ids unique and caps the number of blocks', function () {
    $blocks = array_fill(0, 80, ['id' => 'same', 'type' => 'divider', 'props' => []]);
    $doc = BlockSchema::sanitize(['blocks' => $blocks]);

    expect($doc['blocks'])->toHaveCount(BlockSchema::MAX_BLOCKS)->and(collect($doc['blocks'])->pluck('id')->unique())->toHaveCount(BlockSchema::MAX_BLOCKS);
});

it('renders blocks into table based html with text version, preheader and footer', function () {
    $doc = mbDoc([
        mbBlock('heading', ['text' => 'Willkommen {vorname}', 'level' => 2, 'align' => 'left']),
        mbBlock('text', ['html' => '<p>Guten Tag {name}, <a href="https://example.org">Info</a></p>']),
        mbBlock('button', ['label' => 'Öffnen', 'url' => '{link}', 'align' => 'left', 'style' => 'primary']),
    ], 'Kurz gesagt');

    $mail = MailRenderer::render($doc, LayoutSettings::DEFAULTS, ['vorname' => 'Anna', 'name' => 'Anna Beispiel', 'link' => 'https://portal.example/start'], ['subject' => 'Hallo {vorname}']);

    expect($mail->subject)->toBe('Hallo Anna')
        ->and($mail->html)->toContain('<!DOCTYPE html>')->toContain('lang="de"')->toContain('<h2')->toContain('Willkommen Anna')->toContain('href="https://portal.example/start"')->toContain('Kurz gesagt')->toContain('role="presentation"')->toContain('Verband für Demokratiebildung')
        ->and($mail->text)->toContain('WILLKOMMEN ANNA')->toContain('Guten Tag Anna Beispiel')->toContain('Öffnen: https://portal.example/start')->toContain('Info (https://example.org)');
});

it('escapes placeholder values and never links an unsafe address', function () {
    $doc = mbDoc([
        mbBlock('text', ['html' => '<p>Hallo {name}</p>']),
        mbBlock('button', ['label' => 'Klick', 'url' => '{link}', 'align' => 'left', 'style' => 'primary']),
    ]);

    $mail = MailRenderer::render($doc, LayoutSettings::DEFAULTS, ['name' => '<script>alert(1)</script>', 'link' => 'javascript:alert(1)']);

    expect($mail->html)->toContain('&lt;script&gt;')->not->toContain('<script>alert')->not->toContain('javascript:');

    $unresolved = MailRenderer::render($doc, LayoutSettings::DEFAULTS, ['name' => 'A']);

    expect($unresolved->html)->not->toContain('Klick');
});

it('lets trusted raw values through only when named', function () {
    $doc = mbDoc([mbBlock('text', ['html' => '<p>Vorab</p>{eigener_text}'])]);

    $plain = MailRenderer::render($doc, LayoutSettings::DEFAULTS, ['eigener_text' => '<p>Fett</p>']);
    $raw = MailRenderer::render($doc, LayoutSettings::DEFAULTS, ['eigener_text' => '<p>Fett</p>'], ['raw' => ['eigener_text']]);

    expect($plain->html)->toContain('&lt;p&gt;Fett')->and($raw->html)->toContain('Fett</p>')->not->toContain('&lt;p&gt;Fett');
});

it('fills repeaters from lists, escaped, and hides blocks by condition', function () {
    $doc = mbDoc([
        mbBlock('repeat', ['placeholder' => 'termine', 'template' => 'Termin: {item}', 'ordered' => false]),
        mbBlock('callout', ['tone' => 'info', 'html' => '<p>Mit Link</p>'], ['placeholder' => 'link', 'when' => 'set']),
        mbBlock('callout', ['tone' => 'warning', 'html' => '<p>Ohne Link</p>'], ['placeholder' => 'link', 'when' => 'empty']),
    ]);

    $without = MailRenderer::render($doc, LayoutSettings::DEFAULTS, ['termine' => ['1. März', '2. März <b>']]);
    $with = MailRenderer::render($doc, LayoutSettings::DEFAULTS, ['termine' => [], 'link' => 'https://x.example']);

    expect($without->html)->toContain('Termin: 1. März')->toContain('2. März &lt;b&gt;')->toContain('Ohne Link')->not->toContain('Mit Link')
        ->and($without->text)->toContain('- Termin: 1. März')
        ->and($with->html)->toContain('Mit Link')->not->toContain('Ohne Link')->not->toContain('Termin:');
});

it('renders dark mode styles for supporting clients or forced for the preview', function () {
    $doc = mbDoc([mbBlock('text', ['html' => '<p>Hallo</p>'])]);

    expect(MailRenderer::render($doc, LayoutSettings::DEFAULTS)->html)->toContain('prefers-color-scheme:dark')
        ->and(MailRenderer::render($doc, LayoutSettings::DEFAULTS, [], ['force_dark' => true])->html)->not->toContain('prefers-color-scheme:dark')->toContain(LayoutSettings::DARK['card']);
});

it('keeps only valid design values', function () {
    $settings = LayoutSettings::normalize(['background' => 'red', 'card' => '#F5F7F6', 'link' => '#123456', 'font' => 'comic', 'width' => 5000, 'radius' => -3, 'footer_html' => '<p>Fuß</p><script>x</script>']);

    expect($settings['background'])->toBe(LayoutSettings::DEFAULTS['background'])
        ->and($settings['card'])->toBe('#f5f7f6')->and($settings['link'])->toBe(LayoutSettings::DEFAULTS['link'])
        ->and($settings['font'])->toBe('sans')
        ->and($settings['width'])->toBe(700)
        ->and($settings['radius'])->toBe(0)
        ->and($settings['footer_html'])->toContain('Fuß')->not->toContain('script');
});

it('checks documents for missing alt text, unknown and missing placeholders and weak contrast', function () {
    $doc = BlockSchema::sanitize(mbDoc([
        mbBlock('image', ['asset_id' => 1, 'alt' => '', 'width' => 300]),
        mbBlock('text', ['html' => '<p>Hallo {tippfehler}</p>']),
    ]));

    $issues = MailCheck::run($doc, ['text' => '#cccccc'], [], ['allowed' => ['vorname', 'link'], 'protected' => ['link']]);
    $messages = implode(' | ', array_column($issues, 'message'));

    expect($messages)->toContain('Alternativtext')->toContain('{tippfehler}')->toContain('{link} muss in der Mail stehen')->toContain('Kontrast zu gering')
        ->and(MailCheck::hasErrors($issues))->toBeTrue()
        ->and(MailCheck::hasErrors(MailCheck::run(BlockSchema::sanitize(mbDoc([mbBlock('text', ['html' => '<p>Fein</p>'])])), LayoutSettings::DEFAULTS)))->toBeFalse()
        ->and(MailCheck::hasErrors(MailCheck::run(BlockSchema::empty(), LayoutSettings::DEFAULTS)))->toBeTrue();
});

it('uses stored design and logo through the composer', function () {
    Storage::fake('public');
    $asset = MailAsset::query()->create(['path' => 'mail-assets/logo.png', 'original_name' => 'logo.png', 'mime' => 'image/png', 'size' => 10]);
    MailLayout::query()->create(['name' => 'Standard', 'is_default' => true, 'settings' => ['accent' => '#58275a', 'logo_asset_id' => $asset->id, 'logo_alt' => 'Vereinslogo', 'footer_html' => '<p>Eigener Fuß</p>']]);

    $mail = app(MailComposer::class)->render(mbDoc([mbBlock('text', ['html' => '<p>Hallo</p>']), mbBlock('image', ['asset_id' => $asset->id, 'alt' => 'Beispiel', 'width' => 300, 'align' => 'center'])]));

    expect($mail->html)->toContain('#58275a')->toContain('alt="Vereinslogo"')->toContain('alt="Beispiel"')->toContain('Eigener Fuß')->toContain($asset->url());
});

it('falls back to the default design when none is stored', function () {
    $mail = app(MailComposer::class)->render(mbDoc([mbBlock('text', ['html' => '<p>Hallo</p>'])]));

    expect(MailLayout::query()->count())->toBe(0)->and($mail->html)->toContain(LayoutSettings::DEFAULTS['accent']);
});

it('accepts png, jpg and gif uploads but not svg or oversized pictures', function () {
    Storage::fake('public');
    $assets = app(MailAssets::class);

    $asset = $assets->store(UploadedFile::fake()->image('logo.png', 200, 80), null, ' Logo ');

    expect($asset->mime)->toBe('image/png')->and($asset->width)->toBe(200)->and($asset->alt_default)->toBe('Logo');
    Storage::disk('public')->assertExists($asset->path);

    expect(fn () => $assets->store(UploadedFile::fake()->create('x.svg', 4, 'image/svg+xml'), null))->toThrow(DomainException::class)
        ->and(fn () => $assets->store(UploadedFile::fake()->image('breit.png', 3000, 10), null))->toThrow(DomainException::class);
});
