<?php

use App\Support\Media\ImageManifest;
use App\Support\Media\ImageSource;
use App\Support\Media\MissingImageSourceException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

function img(string $attributes): string
{
    return Blade::render("<x-vdbs.media.image {$attributes} />");
}

it('shows the source from the manifest by file name', function () {
    $html = img('src="/images/placeholder/hero-01.jpg" alt="" credit-placement="below"');

    expect($html)
        ->toContain('Bildquelle:')
        ->toContain('Berlinerlights, Pexels')
        ->toContain('href="https://www.pexels.com/photo/18719900/"')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('data-placeholder="true"');
});

it('lets an explicit credit override the manifest', function () {
    $html = img('src="/images/placeholder/hero-01.jpg" alt="" credit="Bernd Wannenmacher"');

    expect($html)->toContain('Bernd Wannenmacher')->not->toContain('Berlinerlights');
});

it('refuses images without a source in strict mode', function () {
    config(['images.strict' => true]);

    img('src="/images/unbekannt.jpg" alt=""');
})->throws(MissingImageSourceException::class);

it('refuses images without an alt text in strict mode but accepts the decorative alt', function () {
    config(['images.strict' => true]);

    expect(fn () => img('src="/images/placeholder/hero-01.jpg"'))->toThrow(MissingImageSourceException::class);
    expect(img('src="/images/placeholder/hero-01.jpg" alt=""'))->toContain('alt=""');
});

it('shows a visible notice instead of throwing in production mode', function () {
    config(['images.strict' => false]);
    Log::spy();

    $html = img('src="/images/unbekannt.jpg"');

    expect($html)->toContain('vdbs-image-credit--missing')->toContain('fehlt');
    Log::shouldHaveReceived('error')->atLeast()->once();
});

it('renders the source below or at the edge, or leaves it to the surrounding component', function () {
    expect(img('src="/images/placeholder/hero-01.jpg" alt="" credit-placement="below"'))->toContain('vdbs-image-credit--below')
        ->and(img('src="/images/placeholder/hero-01.jpg" alt="" credit-placement="edge"'))->toContain('vdbs-image-credit--edge')
        ->and(img('src="/images/placeholder/hero-01.jpg" alt="" credit-placement="none"'))->not->toContain('Bildquelle');
});

it('supports ratios and validates the focus point', function () {
    $html = img('src="/images/placeholder/hero-01.jpg" alt="" ratio="21-9" focus="30% 20%"');

    expect($html)->toContain('vdbs-image--ratio-21-9')->toContain('object-position: 30% 20%');
    expect(img('src="/images/placeholder/hero-01.jpg" alt="" ratio="quer" focus="url(x)"'))
        ->toContain('vdbs-image--ratio-original')->not->toContain('object-position');
});

it('puts caption and source into the figure', function () {
    $below = Blade::render('<x-vdbs.media.figure src="/images/placeholder/split-01.jpg" alt="" caption="Eine Unterschrift" />');
    $edge = Blade::render('<x-vdbs.media.figure src="/images/placeholder/split-01.jpg" alt="" caption="Eine Unterschrift" credit-placement="edge" />');

    expect($below)->toContain('<figcaption')->toContain('Eine Unterschrift')->toContain('vdbs-image-credit--below')
        ->and($edge)->toContain('vdbs-image-credit--edge')->not->toContain('vdbs-image-credit--below');
});

it('builds readable labels from the source data', function () {
    expect((new ImageSource('Pexels', 'Max Muster'))->label())->toBe('Max Muster, Pexels')
        ->and((new ImageSource('Eigenes Foto'))->label())->toBe('Eigenes Foto')
        ->and(ImageSource::fromCredit('© Sabine')->label())->toBe('© Sabine')
        ->and((new ImageSource(''))->isComplete())->toBeFalse();
});

it('has a manifest entry with source and license for every placeholder image', function () {
    $manifest = ImageManifest::make();
    $dir = public_path('images/placeholder');

    $images = collect(scandir($dir))->filter(fn (string $f) => preg_match('/\.(jpe?g|png|webp|avif|svg)$/i', $f));

    expect($images)->not->toBeEmpty();

    foreach ($images as $file) {
        $source = $manifest->find($file);

        expect($source)->not->toBeNull()
            ->and($source->isComplete())->toBeTrue()
            ->and($source->license)->not->toBeEmpty()
            ->and($source->placeholder)->toBeTrue();
    }
});

it('uses placeholder images only inside the design library', function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());

        if (str_contains($path, 'views/design-library/')) {
            continue;
        }

        expect(file_get_contents($file->getPathname()))->not->toContain('placeholder(');
    }
});
