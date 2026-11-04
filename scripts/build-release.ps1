<#
 Baut das Upload-Paket für das Webhosting (Windows, XAMPP).
 Aufruf (PowerShell im Projektordner):   .\scripts\build-release.ps1
 Ergebnis: build\portal-<Version>.zip  (mit vendor\ und public\build, ohne .env, tests, docs, node_modules)
 Voraussetzungen: git, composer, npm und php im Pfad.
#>
param([string]$Version = (Get-Date -Format 'yyyyMMdd-HHmm'))

$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$build = Join-Path $root 'build'
$target = Join-Path $build 'release'

Set-Location $root

if ((git status --porcelain) -ne $null) {
    Write-Warning 'Es gibt nicht committete Änderungen. Das Paket enthält den Stand der Arbeitsdateien.'
}

if (Test-Path $target) { Remove-Item $target -Recurse -Force }
New-Item -ItemType Directory -Path $target | Out-Null

Write-Host '1/5 Frontend bauen ...'
npm ci
npm run build

Write-Host '2/5 Dateien kopieren ...'
robocopy $root $target /E /NFL /NDL /NJH /NJS /NP `
    /XD '.git' '.github' 'node_modules' 'tests' 'docs' 'build' '.idea' '.vscode' `
    /XF '.env' '.env.*' '*.log' 'phpunit.xml' 'database.sqlite' | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy ist fehlgeschlagen ($LASTEXITCODE)" }

# Leere Laufzeitordner anlegen (storage), ohne Inhalte
foreach ($dir in 'storage\logs','storage\framework\cache\data','storage\framework\sessions','storage\framework\views','storage\app\private','storage\app\public','bootstrap\cache') {
    New-Item -ItemType Directory -Path (Join-Path $target $dir) -Force | Out-Null
}
Get-ChildItem (Join-Path $target 'storage') -Recurse -File | Where-Object { $_.Name -ne '.gitignore' } | Remove-Item -Force
Get-ChildItem (Join-Path $target 'bootstrap\cache') -File | Where-Object { $_.Name -ne '.gitignore' } | Remove-Item -Force

Write-Host '3/5 Abhängigkeiten ohne Entwicklungspakete installieren ...'
composer install --no-dev --optimize-autoloader --no-interaction --working-dir=$target

Write-Host '4/5 Prüfsummen schreiben ...'
php (Join-Path $root 'scripts\release-manifest.php') $target $Version

Write-Host '5/5 ZIP-Datei erstellen ...'
$zip = Join-Path $build "portal-$Version.zip"
if (Test-Path $zip) { Remove-Item $zip -Force }
Compress-Archive -Path (Join-Path $target '*') -DestinationPath $zip

Write-Host ''
Write-Host "Fertig: $zip"
Write-Host 'Weiter: docs\68_WEBHOSTING.md, Abschnitt "Erstes Hochladen" bzw. "Aktualisieren".'
