<?php

/**
 * PowerDownload - Systemprüfung des Web-Installers
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

use PowerDownload\LocalConfig;

/**
 * Schritt 1 des Web-Installers: PHP-Version, Erweiterungen (Pflicht: mysqli,
 * mbstring) und Schreibrechte.
 *
 * kind: „required“ (Pflicht, sperrt den Weiter-Knopf), „optional“ (Hinweis),
 * „info“ (zeigt nur einen Wert) oder „deferred“ (folgt in Schritt 2).
 *
 * @phpstan-type Check array{id: string, label: string, ok: bool, kind: string, detail: string}
 */
final class Requirements
{
    /**
     * Muss zu $pdlRequiredPhp in install.php passen.
     */
    public const string MIN_PHP = '8.4.0';

    public const string KIND_REQUIRED = 'required';

    public const string KIND_OPTIONAL = 'optional';

    public const string KIND_INFO = 'info';

    public const string KIND_DEFERRED = 'deferred';

    /**
     * @param array<array-key, mixed> $server $_SERVER
     * @param callable(string): bool $extensionLoaded z. B. extension_loaded(...)
     * @param callable(string): (string|false) $iniGet z. B. ini_get(...)
     *
     * @return list<Check>
     */
    public static function check(string $rootDir, array $server, string $phpVersion, callable $extensionLoaded, callable $iniGet): array
    {
        $phpOk = self::phpVersionOk($phpVersion);
        $mysqliOk = $extensionLoaded('mysqli');
        $mbstringOk = $extensionLoaded('mbstring');
        $schemaOk = is_file($rootDir . '/' . Schema::FILENAME) && is_readable($rootDir . '/' . Schema::FILENAME);
        $logsOk = self::isWritableDir($rootDir . '/logs');
        $pdlincOk = self::canWriteLocalConfig($rootDir);
        $gdOk = $extensionLoaded('gd');
        $ftpOk = $extensionLoaded('ftp');
        $httpsOk = self::isHttps($server);
        $uploadMax = self::iniValue($iniGet, 'upload_max_filesize');
        $postMax = self::iniValue($iniGet, 'post_max_size');

        return [
            self::item('php', 'PHP ' . self::MIN_PHP . ' oder neuer', $phpOk, self::KIND_REQUIRED, 'Gefunden: PHP ' . $phpVersion . '.'),
            self::item(
                'mysqli',
                'PHP-Erweiterung mysqli',
                $mysqliOk,
                self::KIND_REQUIRED,
                $mysqliOk ? 'Vorhanden.' : 'Fehlt. Bitte im Kundenmenü des Hosters bzw. in der php.ini aktivieren. PowerDownload greift ausschließlich über mysqli auf die Datenbank zu.',
            ),
            self::item(
                'mbstring',
                'PHP-Erweiterung mbstring',
                $mbstringOk,
                self::KIND_REQUIRED,
                $mbstringOk ? 'Vorhanden.' : 'Fehlt. Bitte im Kundenmenü des Hosters bzw. in der php.ini aktivieren. PowerDownload braucht sie für Texte mit Umlauten (Längenprüfung, Vorschautexte, Suche, Zensur).',
            ),
            self::item(
                'schema',
                'Schemadatei ' . Schema::FILENAME . ' lesbar',
                $schemaOk,
                self::KIND_REQUIRED,
                $schemaOk ? 'Vorhanden.' : 'Bitte laden Sie ' . Schema::FILENAME . ' vollständig hoch.',
            ),
            self::item(
                'logs',
                'Verzeichnis logs/ beschreibbar',
                $logsOk,
                self::KIND_REQUIRED,
                $logsOk
                    ? 'Beschreibbar. Hier landen Fehlerprotokolle und, falls nötig, die Sperrdatei des Installers.'
                    : 'Bitte passen Sie die Rechte von logs/ an (je nach Hoster 755, 775 oder 777, im FTP-Programm „Schreibrechte“).',
            ),
            self::item(
                'pdlinc',
                'Verzeichnis pdl-inc/ beschreibbar (für pdl_config.local.php und install.lock)',
                $pdlincOk,
                self::KIND_OPTIONAL,
                $pdlincOk
                    ? 'Der Installer legt ' . LocalConfig::RELATIVE_PATH . ' und die Sperrdatei selbst an.'
                    : 'Nicht beschreibbar, kein Problem: Am Ende bietet der Installer ' . LocalConfig::FILENAME . ' zum Herunterladen an, und die Sperrdatei kommt nach logs/.',
            ),
            self::directory($rootDir, 'files', 'pdl-files', 'Datei-Upload im Adminbereich'),
            self::directory($rootDir, 'screens', 'pdl-gfx/screens', 'Screenshots'),
            self::directory($rootDir, 'smilies', 'pdl-gfx/smilies', 'Smilie-Bilder hochladen'),
            self::item(
                'gd',
                'PHP-Erweiterung GD (Verkleinern von Screenshots)',
                $gdOk,
                self::KIND_OPTIONAL,
                $gdOk ? 'Vorhanden.' : 'Fehlt. Screenshots werden dann nicht automatisch verkleinert.',
            ),
            self::item(
                'ftp',
                'PHP-Erweiterung FTP (FTP-Upload und FTP-Browser)',
                $ftpOk,
                self::KIND_OPTIONAL,
                $ftpOk ? 'Vorhanden.' : 'Fehlt. FTP-Upload und FTP-Browser im Adminbereich stehen dann nicht zur Verfügung.',
            ),
            self::item(
                'upload',
                'Größte Upload-Datei',
                true,
                self::KIND_INFO,
                'upload_max_filesize: ' . $uploadMax . ', post_max_size: ' . $postMax . '. Größere Dateien laden Sie per FTP hoch und tragen nur die Adresse ein.',
            ),
            self::item(
                'dbserver',
                'Datenbankserver: ' . ServerVersion::requirement(),
                true,
                self::KIND_DEFERRED,
                'Wird in Schritt 2 geprüft, sobald die Zugangsdaten eingegeben sind.',
            ),
            self::item(
                'https',
                'Verschlüsselte Verbindung (HTTPS)',
                $httpsOk,
                self::KIND_OPTIONAL,
                $httpsOk
                    ? 'Die Verbindung ist verschlüsselt.'
                    : 'Die Seite wurde ohne HTTPS aufgerufen. Zugangsdaten werden dann unverschlüsselt übertragen. Rufen Sie den Installer nach Möglichkeit über https:// auf.',
            ),
        ];
    }

    /**
     * @param list<Check> $checks
     */
    public static function allRequiredMet(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['kind'] === self::KIND_REQUIRED && !$check['ok']) {
                return false;
            }
        }

        return true;
    }

    public static function phpVersionOk(string $version): bool
    {
        return version_compare($version, self::MIN_PHP, '>=');
    }

    /**
     * @param array<array-key, mixed> $server $_SERVER
     */
    public static function isHttps(array $server): bool
    {
        $https = $server['HTTPS'] ?? '';

        if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
            return true;
        }

        $port = $server['SERVER_PORT'] ?? '';

        return (is_string($port) || is_int($port)) && (string) $port === '443';
    }

    /**
     * Kann der Installer pdl-inc/pdl_config.local.php anlegen?
     */
    public static function canWriteLocalConfig(string $rootDir): bool
    {
        return !file_exists($rootDir . '/' . LocalConfig::RELATIVE_PATH) && self::isWritableDir($rootDir . '/pdl-inc');
    }

    public static function isWritableDir(string $directory): bool
    {
        return is_dir($directory) && is_writable($directory);
    }

    /**
     * @return Check
     */
    private static function directory(string $rootDir, string $id, string $path, string $purpose): array
    {
        $exists = is_dir($rootDir . '/' . $path);
        $ok = self::isWritableDir($rootDir . '/' . $path);

        return self::item(
            $id,
            'Verzeichnis ' . $path . '/ beschreibbar (' . $purpose . ')',
            $ok,
            self::KIND_OPTIONAL,
            match (true) {
                $ok => 'Beschreibbar.',
                $exists => 'Nicht beschreibbar. Bitte geben Sie dem Verzeichnis Schreibrechte, wenn Sie diese Funktion nutzen möchten.',
                default => 'Fehlt. Bitte legen Sie das Verzeichnis an und geben Sie ihm Schreibrechte, wenn Sie diese Funktion nutzen möchten.',
            },
        );
    }

    /**
     * @param callable(string): (string|false) $iniGet
     */
    private static function iniValue(callable $iniGet, string $name): string
    {
        $value = $iniGet($name);

        return is_string($value) && $value !== '' ? $value : 'unbekannt';
    }

    /**
     * @return Check
     */
    private static function item(string $id, string $label, bool $ok, string $kind, string $detail): array
    {
        return ['id' => $id, 'label' => $label, 'ok' => $ok, 'kind' => $kind, 'detail' => $detail];
    }
}
