<?php

use Illuminate\Support\Facades\File;

it('creates a nested design page and its route', function () {
    $viewPath = resource_path(
        'views/design/pages/testing/generated-page.blade.php'
    );

    $routePath = base_path(
        'routes/design/pages/testing/generated-page.php'
    );

    File::delete([
        $viewPath,
        $routePath,
    ]);

    try {
        $this->artisan(
            'make:design-page',
            [
                'name' => 'testing/generated-page',
                '--title' => 'Generator Test',
            ],
        )->assertSuccessful();

        expect(File::exists($viewPath))
            ->toBeTrue()
            ->and(File::exists($routePath))
            ->toBeTrue()
            ->and(File::get($viewPath))
            ->toContain('Generator Test')
            ->and(File::get($routePath))
            ->toContain("'/testing/generated-page'")
            ->toContain("'design.pages.testing.generated-page'")
            ->toContain("'testing.generated-page'")
            ->toContain("'Generator Test'");

        $this->artisan(
            'make:design-page',
            [
                'name' => 'testing/generated-page',
                '--title' => 'Generator Test',
            ],
        )->assertFailed();
    } finally {
        File::delete([
            $viewPath,
            $routePath,
        ]);

        File::deleteDirectory(
            dirname($viewPath)
        );

        File::deleteDirectory(
            dirname($routePath)
        );
    }
});
