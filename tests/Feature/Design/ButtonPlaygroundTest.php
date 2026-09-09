<?php

use App\Support\ButtonExampleBuilder;
use Illuminate\Support\Facades\Route;

it('builds button snippets from controlled variants', function () {
    $example = app(
        ButtonExampleBuilder::class,
    )->build(
        label: 'Speichern',
        variant: 'danger',
        size: 'sm',
        element: 'button',
        icon: 'check',
    );

    expect($example['class'])
        ->toBe('btn btn--danger btn--sm')
        ->and($example['code'])
        ->toContain('<button')
        ->toContain('name="check"')
        ->toContain('Speichern');
});

it('falls back to safe button defaults', function () {
    $example = app(
        ButtonExampleBuilder::class,
    )->build(
        label: '',
        variant: 'unknown',
        size: 'huge',
        element: 'script',
        icon: null,
    );

    expect($example['label'])
        ->toBe('Speichern')
        ->and($example['variant'])
        ->toBe('primary')
        ->and($example['size'])
        ->toBe('md')
        ->and($example['element'])
        ->toBe('button')
        ->and($example['class'])
        ->toBe('btn');
});

it('registers the button playground', function () {
    expect(Route::has('design.bibliothek.buttons'))
        ->toBeTrue();
});
