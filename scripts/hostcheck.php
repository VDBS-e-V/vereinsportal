<?php

/*
 * Hosting-Prüfung (eigenständig, ohne Laravel). Zweck: vor dem Hochladen des Portals prüfen, ob das Webhosting (z. B. Strato) alles mitbringt.
 *
 * Benutzung:
 *   1. Unten bei $KEY ein eigenes langes Passwort eintragen (mindestens 12 Zeichen).
 *   2. Datei per FTP in ein beliebiges Webverzeichnis laden, z. B. /hostcheck.php
 *   3. Im Browser öffnen: https://IHRE-DOMAIN/hostcheck.php?key=IHR-PASSWORT
 *   4. Ergebnis ansehen (oder per Druckfunktion als PDF speichern) und die Datei danach SOFORT LÖSCHEN.
 *
 * Die Seite zeigt nur technische Angaben. Sie speichert nichts. Der optionale Datenbanktest sendet die Zugangsdaten nur an diese Seite
 * (nur über HTTPS benutzen) und gibt sie nicht aus.
 */

$KEY = 'HIER-EIN-EIGENES-PASSWORT-EINTRAGEN';

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

if ($KEY === 'HIER-EIN-EIGENES-PASSWORT-EINTRAGEN' || strlen($KEY) < 12) {
    http_response_code(403);
    exit('Bitte zuerst in der Datei bei $KEY ein eigenes Passwort mit mindestens 12 Zeichen eintragen.');
}

if (! isset($_GET['key']) || ! hash_equals($KEY, (string) $_GET['key'])) {
    http_response_code(403);
    exit('Zugriff verweigert.');
}

$rows = [];
$add = function (string $group, string $label, string $status, string $detail) use (&$rows): void {
    $rows[] = [$group, $label, $status, $detail];
};

// PHP
$php = PHP_VERSION;
$add('PHP', 'Version', version_compare($php, '8.4.1', '>=') ? 'ok' : 'fehler', $php.' (nötig: 8.4.1 oder höher; Strato: Version im Kundenmenü unter PHP-Einstellungen wählbar)');
$add('PHP', 'Schnittstelle', 'info', PHP_SAPI);
$add('PHP', 'Betriebssystem', 'info', PHP_OS_FAMILY);

// Erweiterungen
$required = ['mbstring', 'openssl', 'pdo_mysql', 'fileinfo', 'ctype', 'json', 'tokenizer', 'xml', 'dom', 'curl'];
$wanted = ['zip' => 'ZIP-Mappe der Seminare', 'gd' => 'Bilder, Profilfotos, PDF mit Bildern', 'intl' => 'Sortierung und Formate', 'bcmath' => 'Rechnen (optional)', 'exif' => 'Bildausrichtung (optional)', 'sodium' => 'Verschlüsselung (optional)'];

foreach ($required as $ext) {
    $add('Erweiterungen (Pflicht)', $ext, extension_loaded($ext) ? 'ok' : 'fehler', extension_loaded($ext) ? 'vorhanden' : 'FEHLT');
}

foreach ($wanted as $ext => $why) {
    $add('Erweiterungen (gewünscht)', $ext, extension_loaded($ext) ? 'ok' : 'warnung', extension_loaded($ext) ? 'vorhanden' : 'fehlt: '.$why);
}

// Grenzwerte
$toBytes = function (string $v): int {
    $v = trim($v);
    $n = (int) $v;

    return match (strtolower(substr($v, -1))) {
        'g' => $n * 1024 ** 3,
        'm' => $n * 1024 ** 2,
        'k' => $n * 1024,
        default => $n,
    };
};
$mem = (string) ini_get('memory_limit');
$add('Grenzwerte', 'memory_limit', $mem === '-1' || $toBytes($mem) >= 256 * 1024 ** 2 ? 'ok' : 'warnung', $mem.' (empfohlen: mindestens 256M)');
$time = (int) ini_get('max_execution_time');
$add('Grenzwerte', 'max_execution_time', $time === 0 || $time >= 30 ? 'ok' : 'warnung', $time.' Sekunden (empfohlen: mindestens 30; der Cron-Aufruf nutzt etwa 45)');
$up = (string) ini_get('upload_max_filesize');
$add('Grenzwerte', 'upload_max_filesize', $toBytes($up) >= 15 * 1024 ** 2 ? 'ok' : 'warnung', $up.' (empfohlen: mindestens 15M für Belege)');
$post = (string) ini_get('post_max_size');
$add('Grenzwerte', 'post_max_size', $toBytes($post) >= 20 * 1024 ** 2 ? 'ok' : 'warnung', $post.' (empfohlen: mindestens 20M)');
$add('Grenzwerte', 'max_input_vars', 'info', (string) ini_get('max_input_vars'));
$disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
$add('Grenzwerte', 'gesperrte Funktionen', 'info', $disabled === [] ? 'keine' : implode(', ', $disabled));

foreach (['proc_open', 'exec', 'shell_exec'] as $fn) {
    $add('Funktionen', $fn, 'info', in_array($fn, $disabled, true) || ! function_exists($fn) ? 'gesperrt (das Portal braucht sie nicht)' : 'verfügbar');
}

$add('Funktionen', 'dns_get_record', function_exists('dns_get_record') ? 'ok' : 'warnung', function_exists('dns_get_record') ? 'vorhanden (Absenderprüfung SPF/DMARC möglich)' : 'fehlt (Absenderprüfung nicht möglich)');
$add('Funktionen', 'ZipArchive', class_exists('ZipArchive') ? 'ok' : 'warnung', class_exists('ZipArchive') ? 'vorhanden' : 'fehlt (Seminarmappe als ZIP nicht möglich)');
$add('Funktionen', 'allow_url_fopen', 'info', ini_get('allow_url_fopen') ? 'an' : 'aus');

if (extension_loaded('gd')) {
    $info = gd_info();
    $add('Bilder', 'GD', 'ok', 'JPEG: '.(! empty($info['JPEG Support']) ? 'ja' : 'nein').', PNG: '.(! empty($info['PNG Support']) ? 'ja' : 'nein').', WebP: '.(! empty($info['WebP Support']) ? 'ja' : 'nein'));
}

// Schreibrechte
$dir = __DIR__;
$probe = $dir.'/.hostcheck-'.bin2hex(random_bytes(4));
$can = @file_put_contents($probe, 'test') !== false;
if ($can) {
    @unlink($probe);
}
$add('Dateien', 'Schreiben im Webverzeichnis', $can ? 'info' : 'info', $can ? 'möglich (das Portal schreibt später nur in storage/ und bootstrap/cache/)' : 'nicht möglich (Rechte der Ordner storage/ und bootstrap/cache/ im Kundenmenü auf Schreiben stellen)');
$free = @disk_free_space($dir);
$add('Dateien', 'freier Speicher', $free !== false && $free > 5 * 1024 ** 3 ? 'ok' : 'info', $free === false ? 'unbekannt' : round($free / 1024 ** 3, 1).' GB');
$tmp = sys_get_temp_dir();
$add('Dateien', 'Temp-Ordner', is_writable($tmp) ? 'ok' : 'warnung', $tmp.(is_writable($tmp) ? ' (beschreibbar)' : ' (nicht beschreibbar)'));

// Verbindung
$https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$add('Verbindung', 'HTTPS', $https ? 'ok' : 'fehler', $https ? 'aktiv' : 'NICHT aktiv: Zertifikat im Kundenmenü aktivieren und Weiterleitung auf HTTPS einstellen');
$add('Verbindung', 'Server', 'info', (string) ($_SERVER['SERVER_SOFTWARE'] ?? 'unbekannt'));
$add('Verbindung', 'mod_rewrite', 'info', function_exists('apache_get_modules') ? (in_array('mod_rewrite', apache_get_modules(), true) ? 'an' : 'AUS') : 'nicht ermittelbar (bei Strato üblicherweise an)');
$add('Zeit', 'Zeitzone', 'info', date_default_timezone_get().' – jetzt: '.date('d.m.Y H:i:s'));

// Datenbanktest (optional)
$dbResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dbhost'])) {
    try {
        $pdo = new PDO('mysql:host='.$_POST['dbhost'].';dbname='.$_POST['dbname'].';charset=utf8mb4', (string) $_POST['dbuser'], (string) $_POST['dbpass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $sql = 'SELECT @@sql_mode AS m, @@character_set_server AS c';
        $info = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        $dbResult = ['ok', 'Verbunden. Version: '.$version.' · Zeichensatz: '.$info['c'].' · Modus: '.substr((string) $info['m'], 0, 80)];
    } catch (Throwable $e) {
        $dbResult = ['fehler', 'Verbindung fehlgeschlagen: '.preg_replace('/(password|passwort)[^\s]*/i', '***', $e->getMessage())];
    }
}

$colors = ['ok' => '#0a6b32', 'warnung' => '#8a5a00', 'fehler' => '#b00020', 'info' => '#444'];
$labels = ['ok' => 'in Ordnung', 'warnung' => 'Hinweis', 'fehler' => 'PROBLEM', 'info' => 'Information'];
$problems = count(array_filter($rows, fn ($r) => $r[2] === 'fehler'));
$warnings = count(array_filter($rows, fn ($r) => $r[2] === 'warnung'));
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hosting-Prüfung</title>
<style>
body { font-family: system-ui, sans-serif; max-width: 52rem; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
table { border-collapse: collapse; width: 100%; margin-bottom: 1.5rem; }
th, td { text-align: left; padding: .35rem .5rem; border-bottom: 1px solid #ccc; vertical-align: top; }
th { width: 28%; }
.s { font-weight: 600; white-space: nowrap; }
.box { border: 2px solid #444; padding: .75rem 1rem; margin: 1rem 0; }
input { padding: .35rem; margin: .15rem 0; width: 100%; max-width: 22rem; }
</style>
</head>
<body>
<h1>Hosting-Prüfung für das VDBS Vereinsportal</h1>
<div class="box"><strong>Ergebnis:</strong> <?= $problems ?> Problem(e), <?= $warnings ?> Hinweis(e). <?= $problems === 0 ? 'Das Hosting erfüllt die Pflichtanforderungen.' : 'Bitte die Probleme beheben, bevor das Portal hochgeladen wird.' ?><br><strong>Danach diese Datei sofort löschen.</strong></div>
<?php
$group = null;
foreach ($rows as [$g, $label, $status, $detail]) {
    if ($g !== $group) {
        if ($group !== null) {
            echo "</table>\n";
        }
        echo '<h2>'.h($g)."</h2>\n<table>\n";
        $group = $g;
    }
    echo '<tr><th>'.h($label).'</th><td class="s" style="color:'.$colors[$status].'">'.h($labels[$status]).'</td><td>'.h($detail)."</td></tr>\n";
}
echo "</table>\n";
?>
<h2>Datenbank prüfen (optional)</h2>
<p>Nur über HTTPS benutzen. Die Eingaben werden nicht gespeichert und nicht angezeigt.</p>
<?php if ($dbResult) { ?>
<div class="box" style="border-color:<?= $colors[$dbResult[0]] ?>"><strong style="color:<?= $colors[$dbResult[0]] ?>"><?= h($labels[$dbResult[0]]) ?>:</strong> <?= h($dbResult[1]) ?></div>
<?php } ?>
<form method="post" action="?key=<?= h(rawurlencode((string) $_GET['key'])) ?>" autocomplete="off">
<label>Server (Host)<br><input name="dbhost" required></label><br>
<label>Datenbankname<br><input name="dbname" required></label><br>
<label>Benutzer<br><input name="dbuser" required></label><br>
<label>Passwort<br><input name="dbpass" type="password" autocomplete="new-password"></label><br>
<button type="submit">Verbindung testen</button>
</form>
<p style="color:#555">Erstellt für Strato Webhosting. Weitere Prüfungen (Schreibrechte von storage/, PDF-Erzeugung, Cron) macht das Portal selbst in der Systemsteuerung.</p>
</body>
</html>
