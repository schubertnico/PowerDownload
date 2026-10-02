<?php

/**
 * PowerDownload - Sperre des Web-Installers
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

use PowerDownload\LocalConfig;

/**
 * Entscheidet, ob der Web-Installer laufen darf.
 *
 * Der Installer ist gesperrt, sobald eines davon zutrifft:
 *   1. eine Sperrdatei existiert (pdl-inc/install.lock oder logs/install.lock),
 *   2. pdl-inc/pdl_config.local.php existiert,
 *   3. per Umgebungsvariablen ist eine Datenbank eingerichtet, und
 *      pdl3_settings enthält Zeilen,
 *   4. per Umgebungsvariablen ist eine Datenbank eingerichtet, die gerade
 *      nicht erreichbar ist. Ein Datenbankausfall darf den Installer nicht
 *      wieder öffnen.
 *   5. pdl-files/ oder pdl-gfx/screens/ enthalten hochgeladene Dateien.
 *      Typischer Fall: Ein Update von 3.5.0 wurde samt install.php
 *      hochgeladen, pdl_config.local.php fehlt noch. Ohne diese Sperre könnte
 *      ein Fremder den Installer mit eigener Datenbank durchlaufen und die
 *      Seite übernehmen.
 *
 * Gesperrt heißt: HTTP 403 mit Hinweisseite, bevor Session-Daten oder
 * Formulare ausgewertet werden. Der Installer verwirft niemals Tabellen
 * einer bestehenden Installation.
 */
final class InstallState
{
    /**
     * Mögliche Orte der Sperrdatei, bevorzugter zuerst. logs/ muss laut
     * Systemprüfung beschreibbar sein, deshalb lässt sich die Sperre immer
     * setzen, auch wenn pdl-inc/ schreibgeschützt ist.
     */
    public const array LOCK_FILES = ['pdl-inc/install.lock', 'logs/install.lock'];

    public const string REASON_LOCK_FILE = 'lockfile';

    public const string REASON_LOCAL_CONFIG = 'config';

    public const string REASON_DATABASE = 'database';

    public const string REASON_UNREACHABLE = 'unreachable';

    public const string REASON_EXISTING_FILES = 'existing_files';

    /**
     * Upload-Verzeichnisse: Liegt hier mehr als der Lieferumfang, gab es
     * schon eine Installation.
     */
    public const array UPLOAD_DIRS = ['pdl-files', 'pdl-gfx/screens'];

    /**
     * Dateien, die zum Lieferumfang der Upload-Verzeichnisse gehören.
     */
    public const array SHIPPED_UPLOAD_FILES = ['.htaccess', 'index.html', '.gitkeep'];

    /**
     * Reine Entscheidungslogik.
     *
     * @param string $configSource Quelle der Zugangsdaten (LocalConfig::SOURCE_*)
     * @param bool|null $databaseInstalled true = pdl3_settings hat Zeilen,
     *                                     false = Datenbank erreichbar, aber ohne PowerDownload,
     *                                     null = nicht erreichbar oder unklar
     * @param bool $existingUploads pdl-files/ oder pdl-gfx/screens/ enthalten hochgeladene Dateien
     */
    public static function lockReason(bool $lockFileExists, bool $localConfigExists, string $configSource, ?bool $databaseInstalled, bool $existingUploads = false): ?string
    {
        if ($lockFileExists) {
            return self::REASON_LOCK_FILE;
        }

        if ($localConfigExists) {
            return self::REASON_LOCAL_CONFIG;
        }

        if ($configSource === LocalConfig::SOURCE_ENVIRONMENT) {
            $reason = match ($databaseInstalled) {
                true => self::REASON_DATABASE,
                null => self::REASON_UNREACHABLE,
                false => null,
            };

            if ($reason !== null) {
                return $reason;
            }
        }

        return $existingUploads ? self::REASON_EXISTING_FILES : null;
    }

    /**
     * Ermittelt die Sperre. Die Datenbank wird nur befragt, wenn keine Datei
     * die Frage schon beantwortet und die Zugangsdaten aus der Umgebung stammen.
     *
     * @param callable(): ?bool $probe prüft die per Umgebung eingerichtete Datenbank
     */
    public static function detectLockReason(string $rootDir, string $configSource, callable $probe): ?string
    {
        if (self::existingLockFile($rootDir) !== null) {
            return self::REASON_LOCK_FILE;
        }

        if (is_file($rootDir . '/' . LocalConfig::RELATIVE_PATH)) {
            return self::REASON_LOCAL_CONFIG;
        }

        $databaseInstalled = $configSource === LocalConfig::SOURCE_ENVIRONMENT ? $probe() : false;

        return self::lockReason(false, false, $configSource, $databaseInstalled, self::hasExistingUploads($rootDir));
    }

    /**
     * Enthält eines der Upload-Verzeichnisse mehr als den Lieferumfang
     * (.htaccess, index.html, .gitkeep)?
     */
    public static function hasExistingUploads(string $rootDir): bool
    {
        foreach (self::UPLOAD_DIRS as $uploadDir) {
            if (!is_dir($rootDir . '/' . $uploadDir)) {
                continue;
            }

            $entries = @scandir($rootDir . '/' . $uploadDir);

            foreach (is_array($entries) ? $entries : [] as $entry) {
                if ($entry !== '.' && $entry !== '..' && !in_array($entry, self::SHIPPED_UPLOAD_FILES, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Relativer Pfad der vorhandenen Sperrdatei oder null.
     */
    public static function existingLockFile(string $rootDir): ?string
    {
        foreach (self::LOCK_FILES as $lockFile) {
            if (is_file($rootDir . '/' . $lockFile)) {
                return $lockFile;
            }
        }

        return null;
    }

    /**
     * Relativer Pfad, an dem die Sperrdatei angelegt würde, oder null, wenn
     * keines der Verzeichnisse beschreibbar ist.
     */
    public static function lockTarget(string $rootDir): ?string
    {
        foreach (self::LOCK_FILES as $lockFile) {
            if (Requirements::isWritableDir(dirname($rootDir . '/' . $lockFile))) {
                return $lockFile;
            }
        }

        return null;
    }

    /**
     * Schreibt die Sperrdatei und liefert ihren relativen Pfad, oder null,
     * wenn das nicht möglich war. Der Aufrufer muss null sichtbar melden.
     */
    public static function writeLockFile(string $rootDir, string $timestamp): ?string
    {
        $content = 'PowerDownload installiert am ' . preg_replace('/[^0-9: .-]/', '', $timestamp) . "\n"
            . "Solange diese Datei existiert, verweigert install.php jeden Aufruf.\n";

        foreach (self::LOCK_FILES as $lockFile) {
            $path = $rootDir . '/' . $lockFile;

            if (!Requirements::isWritableDir(dirname($path))) {
                continue;
            }

            set_error_handler(static fn (): bool => true);

            try {
                $written = file_put_contents($path, $content, LOCK_EX);
            } finally {
                restore_error_handler();
            }

            if ($written !== false && is_file($path)) {
                return $lockFile;
            }
        }

        return null;
    }
}
