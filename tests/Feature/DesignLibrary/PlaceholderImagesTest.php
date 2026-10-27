<?php

use App\Modules\Access\Models\User;
use App\Modules\DesignLibrary\Support\PlaceholderImages;

function placeholderDir(array $files = []): string
{
    $dir = sys_get_temp_dir().'/vdbs-placeholder-'.bin2hex(random_bytes(4));
    mkdir($dir);

    foreach ($files as $file) {
        touch($dir.'/'.$file);
    }

    return $dir;
}

it('falls back to the svg of the kind without uploads', function () {
    $images = new PlaceholderImages(placeholderDir(), '/p');

    expect($images->url('hero'))->toBe('/p/hero.svg');
});

it('picks numbered files and cycles when the number exceeds the stock', function () {
    $images = new PlaceholderImages(placeholderDir(['hero-01.jpg', 'hero-02.webp', 'teaser-01.png', 'notes.txt', 'hero-x.jpg']), '/p');

    expect($images->url('hero', 1))->toBe('/p/hero-01.jpg')
        ->and($images->url('hero', 2))->toBe('/p/hero-02.webp')
        ->and($images->url('hero', 3))->toBe('/p/hero-01.jpg')
        ->and($images->url('teaser'))->toBe('/p/teaser-01.png')
        ->and($images->files('hero')->all())->toBe(['hero-01.jpg', 'hero-02.webp']);
});

it('rejects unknown kinds', function () {
    (new PlaceholderImages(placeholderDir(), '/p'))->url('banner');
})->throws(InvalidArgumentException::class);

it('offers a global placeholder helper', function () {
    expect(placeholder('hero'))->toStartWith('/images/placeholder/hero');
});

it('lists the kinds as json for authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/design/api/platzhalter')
        ->assertOk()
        ->assertJsonCount(count(PlaceholderImages::KINDS), 'data')
        ->assertJsonPath('data.0.kind', 'hero');
});
