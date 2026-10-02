<?php

/**
 * PowerDownload - „Jetzt installieren“
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

use PowerDownload\LocalConfig;

/**
 * Schema einspielen, Administrator anlegen, pdl_config.local.php schreiben
 * und den Installer sperren.
 *
 * @phpstan-import-type DbInput from FormValidator
 * @phpstan-import-type WebsiteSettings from FormValidator
 * @phpstan-import-type AdminData from DatabaseSetup
 *
 * @phpstan-type Outcome array{admin_id: int, config_written: bool, config_source: string, lock_file: string|null}
 */
final class Setup
{
    /**
     * @param \Closure(DbInput): \mysqli $connect baut die Datenbankverbindung auf
     * @param DbInput $database Zugang, der in pdl_config.local.php landet
     * @param WebsiteSettings $website
     * @param AdminData $admin
     *
     * @throws \RuntimeException mit verständlicher Meldung; die Datenbank ist dann unverändert
     *                           bzw. die in diesem Lauf angelegten Tabellen sind wieder entfernt
     *
     * @return Outcome
     */
    public static function run(
        string $rootDir,
        \Closure $connect,
        #[\SensitiveParameter]
        array $database,
        array $website,
        #[\SensitiveParameter]
        array $admin,
        string $timestamp,
    ): array {
        // Ohne Sperrdatei kein Abschluss: geprüft, bevor die Datenbank berührt wird.
        if (InstallState::lockTarget($rootDir) === null) {
            throw new \RuntimeException(
                'Die Sperrdatei kann weder in pdl-inc/ noch in logs/ angelegt werden. Ohne sie ließe sich der Installer später erneut aufrufen. '
                . 'Bitte machen Sie logs/ beschreibbar und versuchen Sie es erneut. Es wurde nichts verändert.',
            );
        }

        try {
            $statements = Schema::fromFile($rootDir . '/' . Schema::FILENAME);
            $requiredTables = self::configuredTables($rootDir);
            Schema::assertInstallable($statements, $requiredTables);
        } catch (\UnexpectedValueException $e) {
            throw new \RuntimeException($e->getMessage() . ' Es wurde nichts verändert.', 0, $e);
        }

        // Die Originalmeldungen des Servers nennen Benutzer und Host, sie werden nie weitergegeben.
        try {
            $mysqli = $connect($database);
            $tables = DatabaseSetup::existingTables($mysqli);
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException(
                'Die Installation ist fehlgeschlagen. ' . DatabaseSetup::friendlyError($e->getCode()) . ' Es wurde nichts verändert.',
                $e->getCode(),
            );
        }

        if ($tables !== []) {
            $mysqli->close();

            throw new \RuntimeException('Die Datenbank enthält inzwischen PowerDownload-Tabellen. Es wurde nichts verändert.');
        }

        try {
            $adminId = DatabaseSetup::install($mysqli, $statements, $requiredTables, $website, $admin, time());
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException(
                'Die Installation ist fehlgeschlagen. ' . DatabaseSetup::friendlyError($e->getCode()) . ' Bereits angelegte Tabellen wurden wieder entfernt.',
                $e->getCode(),
            );
        } catch (\UnexpectedValueException $e) {
            throw new \RuntimeException($e->getMessage() . ' Bereits angelegte Tabellen wurden wieder entfernt.', 0, $e);
        } finally {
            $mysqli->close();
        }

        $source = LocalConfig::render($database + ['persistent' => false], $timestamp);

        return [
            'admin_id' => $adminId,
            'config_written' => LocalConfig::writeFile($rootDir . '/' . LocalConfig::RELATIVE_PATH, $source),
            'config_source' => $source,
            'lock_file' => InstallState::writeLockFile($rootDir, $timestamp),
        ];
    }

    /**
     * Tabellennamen aus $sql_table in pdl-inc/pdl_config.inc.php. Die Datei
     * wird in einem eigenen Gültigkeitsbereich gelesen und setzt keine
     * globalen Variablen.
     *
     * @return list<string>
     */
    public static function configuredTables(string $rootDir): array
    {
        $file = $rootDir . '/pdl-inc/pdl_config.inc.php';

        if (!is_file($file)) {
            throw new \UnexpectedValueException('Die Datei pdl-inc/pdl_config.inc.php fehlt. Bitte laden Sie sie erneut hoch.');
        }

        /** @var mixed $tables */
        $tables = (static function (string $file): mixed {
            $sql_table = null;
            include $file;

            return $sql_table;
        })($file);

        if (!is_array($tables)) {
            throw new \UnexpectedValueException('In pdl-inc/pdl_config.inc.php fehlt die Tabellenliste $sql_table.');
        }

        return array_values(array_filter($tables, 'is_string'));
    }
}
