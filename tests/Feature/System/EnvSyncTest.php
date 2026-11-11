<?php

function envSyncFiles(string $env, string $example): array
{
    $dir = sys_get_temp_dir().'/envsync-'.bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir.'/.env', $env);
    file_put_contents($dir.'/.env.example', $example);

    return [$dir.'/.env', $dir.'/.env.example', $dir];
}

function envSyncClean(string $dir): void
{
    foreach (glob($dir.'/.env*') ?: [] as $file) {
        unlink($file);
    }

    rmdir($dir);
}

it('adds missing settings with their comment and keeps existing values', function () {
    [$env, $example, $dir] = envSyncFiles("APP_NAME=Portal\nAPP_DEBUG=false\n", "APP_NAME=Laravel\nAPP_DEBUG=true\n\n# Schlüssel für den Cron-Aufruf\nCRON_KEY=\nNEUE_EINSTELLUNG=\"mit Leerzeichen\"\n# AUSKOMMENTIERT=1\n");

    $this->artisan('env:sync', ['--env' => $env, '--example' => $example])->assertSuccessful();

    $content = file_get_contents($env);
    expect($content)->toContain('APP_NAME=Portal')->and($content)->toContain('APP_DEBUG=false')
        ->and($content)->toContain("# Schlüssel für den Cron-Aufruf\nCRON_KEY=")
        ->and($content)->toContain('NEUE_EINSTELLUNG="mit Leerzeichen"')
        ->and($content)->not->toContain('AUSKOMMENTIERT')
        ->and($content)->not->toContain('APP_NAME=Laravel')
        ->and(glob($dir.'/.env.backup-*'))->toHaveCount(1);

    envSyncClean($dir);
});

it('changes nothing in a dry run and when nothing is missing', function () {
    [$env, $example, $dir] = envSyncFiles("A=1\n", "A=2\nB=3\n");

    $this->artisan('env:sync', ['--env' => $env, '--example' => $example, '--dry-run' => true])->assertSuccessful();
    expect(file_get_contents($env))->toBe("A=1\n")->and(glob($dir.'/.env.backup-*'))->toBeEmpty();

    file_put_contents($env, "A=1\nB=9\n");
    $this->artisan('env:sync', ['--env' => $env, '--example' => $example])->assertSuccessful();
    expect(file_get_contents($env))->toBe("A=1\nB=9\n")->and(glob($dir.'/.env.backup-*'))->toBeEmpty();

    envSyncClean($dir);
});

it('is repeatable and reports settings that exist only in the env', function () {
    [$env, $example, $dir] = envSyncFiles("A=1\nNUR_HIER=x\n", "A=2\nB=3\n");

    $this->artisan('env:sync', ['--env' => $env, '--example' => $example])->expectsOutputToContain('NUR_HIER')->assertSuccessful();
    $after = file_get_contents($env);
    $this->artisan('env:sync', ['--env' => $env, '--example' => $example])->assertSuccessful();

    expect(file_get_contents($env))->toBe($after)->and(substr_count($after, "B=3"))->toBe(1);

    envSyncClean($dir);
});

it('refuses to run without an env file', function () {
    $this->artisan('env:sync', ['--env' => sys_get_temp_dir().'/gibt-es-nicht/.env'])->assertFailed();
});
