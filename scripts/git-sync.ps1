<#
.SYNOPSIS
	Synchronisiert das Projekt mit dem aktuellen Git-Stand.

.DESCRIPTION
	Holt Änderungen per `git pull`, führt Laravel-Datenbankmigrationen aus,
	leert die Laravel-Caches und erstellt die Frontend-Assets neu.
	Mit -RunTests werden zusätzlich die Laravel-Tests ausgeführt.
	Mit -KurzeTests werden nur die kurzen Unit-Tests ausgeführt.

.PARAMETER RunTests
	Führt vor dem Frontend-Build `php artisan test` aus.

.EXAMPLE
	.\scripts\git-sync.ps1
	Synchronisiert das Projekt ohne Tests.

.EXAMPLE
	.\scripts\git-sync.ps1 -RunTests
	Synchronisiert das Projekt und führt anschließend die Tests aus.

.EXAMPLE
	.\scripts\git-sync.ps1 -KurzeTests
	Synchronisiert das Projekt und führt anschließend nur die kurzen Unit-Tests aus.

.NOTES
	Voraussetzungen: Git, PHP/Laravel und npm müssen verfügbar sein.
	Lokale Änderungen sollten vor dem Start gesichert werden, da `git pull`
	den lokalen Arbeitsstand verändern kann.
#>
param(
	[switch]$RunTests,
	[switch]$KurzeTests
)

# Ausgangszustand der Konsole bereinigen.
clear
echo "Starting git sync..."
echo ""
echo ""

# Änderungen vom konfigurierten Git-Remote abrufen und zusammenführen.
echo "Pulling latest changes..."
git pull

# Datenbankstruktur an den aktuellen Anwendungscode anpassen.
echo "Running database migrations..."
php artisan migrate

# Laravel-Caches leeren, damit die Änderungen wirksam werden.
echo "Clearing application cache..."
php artisan optimize:clear
php artisan config:clear

# Tests nur bei Übergabe des optionalen Schalters ausführen.
if ($RunTests) {
	echo "Running tests..."
	php artisan test
}

# Bei Übergabe des optionalen Schalters nur die kurzen Unit-Tests ausführen.
if ($KurzeTests) {
	echo "Running short tests..."
	php artisan test --testsuite=Unit
}

# Frontend-Assets für die Anwendung erstellen.
echo "Building frontend assets..."
npm run build