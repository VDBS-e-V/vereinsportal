<?php

return [
    /*
     * Aufbewahrung der Protokolleinträge in Tagen. `php artisan audit:prune` löscht ältere Einträge; ohne den Befehl
     * bleiben alle erhalten. Die Frist gehört zur Datenschutzdokumentation des Vereins.
     */
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 730),

    /** Höchstzahl Zeilen eines CSV-Exports. */
    'export_limit' => 10000,
];
