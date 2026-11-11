<?php

/*
 * Schreibt die Prüfsummen eines Release-Ordners als JSON (release-manifest.json). Aufruf: php scripts/release-manifest.php <Ordner> [Version]
 * Das Portal prüft damit nach dem FTP-Upload, ob alle Dateien vollständig angekommen sind (Systemsteuerung, Aufgabe „Paket prüfen“).
 */

$dir = rtrim((string) ($argv[1] ?? ''), "/\\");
$version = (string) ($argv[2] ?? date('Ymd-Hi'));

if ($dir === '' || ! is_dir($dir)) {
    fwrite(STDERR, "Ordner fehlt oder existiert nicht.\n");
    exit(1);
}

$skip = ['release-manifest.json', '.env', 'storage/logs', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views'];
$files = [];
$base = realpath($dir);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));

    foreach ($skip as $prefix) {
        if ($relative === $prefix || str_starts_with($relative, $prefix.'/')) {
            continue 2;
        }
    }

    $files[$relative] = ['size' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getPathname())];
}

ksort($files);
file_put_contents($base.'/release-manifest.json', json_encode(['version' => $version, 'created' => date('c'), 'count' => count($files), 'files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents($base.'/RELEASE', $version."\n".date('Y-m-d H:i')."\n");
echo count($files)." Dateien, Version $version\n";
