<?php

/**
 * PowerDownload - Hinweisseite „noch nicht eingerichtet“ und Altdateien
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

if (!function_exists('pdl_setup_required_page')) {
    /**
     * Gibt die Hinweisseite aus (HTTP 503) und beendet das Skript.
     *
     * Gründe: „defaults“ (keine Zugangsdaten eingerichtet), „connection“
     * (Datenbank nicht erreichbar), „tables“ (Tabellen fehlen). Die
     * Originalmeldung des Servers gehört ins Fehlerprotokoll, nicht auf die
     * Seite. Den Knopf „Installation starten“ gibt es nur, solange install.php
     * existiert und der Installer nicht gesperrt wäre (pdl_setup_installer_lock_reason).
     *
     * @param string $incdir relativer Weg zum PowerDownload-Verzeichnis ('' oder '../')
     */
    function pdl_setup_required_page(string $reason, string $incdir): never
    {
        $texts = [
            'defaults' => ['PowerDownload ist noch nicht eingerichtet.', 'Es sind noch keine Zugangsdaten zur Datenbank hinterlegt.'],
            'connection' => ['Keine Verbindung zur Datenbank.', 'Bitte prüfen Sie die Zugangsdaten in pdl-inc/pdl_config.local.php bzw. in den Umgebungsvariablen PDL_DB_*.'],
            'tables' => ['Die Tabellen von PowerDownload fehlen.', 'Die Datenbank ist erreichbar, enthält aber noch keine PowerDownload-Tabellen.'],
        ];
        $reason = isset($texts[$reason]) ? $reason : 'defaults';
        [$headline, $detail] = $texts[$reason];
        $rootDir = dirname(__DIR__);
        $installFile = is_file($rootDir . '/install.php');
        $configSource = is_string($GLOBALS['config_source'] ?? null) ? $GLOBALS['config_source'] : 'defaults';
        $lockReason = $installFile ? pdl_setup_installer_lock_reason($reason, $configSource, $rootDir) : null;
        $installLocked = $lockReason !== null;
        $installAvailable = $installFile && !$installLocked;
        $lockTexts = [
            'lockfile' => 'Die Installation wurde bereits abgeschlossen (Sperrdatei vorhanden).',
            'config' => 'Es gibt bereits pdl-inc/pdl_config.local.php.',
            'database' => 'Laut den Umgebungsvariablen PDL_DB_* ist bereits eine Datenbank eingerichtet.',
            'unreachable' => 'Laut den Umgebungsvariablen PDL_DB_* ist bereits eine Datenbank eingerichtet.',
            'existing_files' => 'In pdl-files/ bzw. pdl-gfx/screens/ liegen bereits hochgeladene Dateien.',
        ];
        $lockText = $lockTexts[(string) $lockReason] ?? 'Hier scheint bereits eine Installation zu bestehen.';
        $installLink = htmlspecialchars($incdir . 'install.php', ENT_QUOTES, 'UTF-8');

        if (!headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            header('Retry-After: 300');
            header('Cache-Control: no-store, max-age=0');
        }

        echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>' . htmlspecialchars(rtrim($headline, '.'), ENT_QUOTES, 'UTF-8') . ' · PowerDownload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light min-vh-100 d-flex align-items-center justify-content-center p-3">
    <main class="container" style="max-width: 560px;">
        <div class="card bg-secondary-subtle text-dark shadow" id="pdl_setup_required" data-reason="' . $reason . '">
            <div class="card-header bg-warning text-dark">
                <h1 class="h4 mb-0">' . htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') . '</h1>
            </div>
            <div class="card-body">
                <p class="mb-3">' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</p>';

        if ($installAvailable) {
            echo '
                <p class="mb-3">Der Web-Installer richtet PowerDownload in fünf Schritten ein: Datenbank, Website und Administrator-Konto.</p>
                <p class="mb-0"><a class="btn btn-primary" href="' . $installLink . '" id="pdl_setup_required_install_link">Installation starten</a></p>';
        } elseif ($installLocked) {
            echo '
                <p class="mb-0 small" id="pdl_setup_required_update_hint" data-lock-reason="' . htmlspecialchars($lockReason, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($lockText, ENT_QUOTES, 'UTF-8') . ' Der Web-Installer ist deshalb gesperrt.
                Nach einem Update legen Sie <code>pdl-inc/pdl_config.local.php</code> mit den bisherigen Zugangsdaten an (falls noch nicht geschehen)
                und rufen als Admin <code>update.php</code> auf; die Anleitung steht in der README unter „Update von 3.5.0 auf 3.6.0“.
                Löschen Sie bitte <code>install.php</code> vom Server.</p>';
        } else {
            echo '
                <p class="mb-0 small">Zum Einrichten laden Sie bitte die Datei <code>install.php</code> aus dem Release-Archiv hoch und rufen sie im Browser auf.</p>';
        }

        echo '
            </div>
        </div>
    </main>
</body>
</html>';
        exit;
    }
}

if (!function_exists('pdl_setup_installer_lock_reason')) {
    /**
     * Wäre der Web-Installer gesperrt, und warum? Gleiche Regeln wie
     * install.php (PowerDownload\Installer\InstallState): Sperrdatei,
     * pdl_config.local.php, Datenbank laut Umgebungsvariablen eingerichtet
     * oder nicht erreichbar, hochgeladene Dateien in pdl-files/ bzw.
     * pdl-gfx/screens/. null = nicht gesperrt.
     *
     * Die Datenbank wurde gerade erst befragt: Grund „connection“ heißt nicht
     * erreichbar, „tables“ heißt erreichbar, aber ohne PowerDownload.
     */
    function pdl_setup_installer_lock_reason(string $reason, string $configSource, string $rootDir): ?string
    {
        $autoload = $rootDir . '/pdl-inc/installer/autoload.php';
        if (!is_file($autoload)) {
            // install.php meldet dann selbst „Installer unvollständig“.
            return null;
        }
        require_once $autoload;

        $probe = static fn (): ?bool => $reason === 'connection' ? null : false;

        return \PowerDownload\Installer\InstallState::detectLockReason($rootDir, $configSource, $probe);
    }
}

if (!function_exists('pdl_legacy_install_files')) {
    /**
     * Installations- und Update-Skripte, die nicht auf einen laufenden Server
     * gehören: install.php (nach der Installation) und Altdateien früherer
     * Versionen, die nach einem Update per FTP liegen bleiben können.
     * update.php gehört nicht dazu, es ist nur für Admins erreichbar.
     *
     * @return list<string>
     */
    function pdl_legacy_install_files(string $rootDir): array
    {
        $files = [];

        foreach (['install.php', 'setup.php', 'install_querys.inc'] as $file) {
            if (is_file($rootDir . '/' . $file)) {
                $files[] = $file;
            }
        }

        foreach (['install_*.php', 'update_*.php'] as $pattern) {
            $found = glob($rootDir . '/' . $pattern);

            foreach (is_array($found) ? $found : [] as $path) {
                $files[] = basename($path);
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }
}
