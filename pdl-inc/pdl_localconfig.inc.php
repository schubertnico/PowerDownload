<?php

/**
 * PowerDownload - Lokale Konfiguration (pdl-inc/pdl_config.local.php)
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload;

/**
 * Liest die Zugangsdaten zur Datenbank.
 *
 * Rangfolge je Schlüssel (höchste zuerst):
 *   1. Umgebungsvariablen PDL_DB_HOST, PDL_DB_PORT, PDL_DB_USER, PDL_DB_PASS, PDL_DB_NAME
 *   2. pdl-inc/pdl_config.local.php (vom Web-Installer geschrieben oder von Hand angelegt)
 *   3. Vorgaben aus DEFAULT_DB
 *
 * Anders als bei PowerNews haben die Umgebungsvariablen Vorrang vor der Datei:
 * Entwicklungs- und Aufnahme-Stack mounten das Repository live. Eine dort
 * versehentlich angelegte pdl_config.local.php darf sie nicht auf eine andere
 * Datenbank umbiegen. Wer beim Hoster SetEnv PDL_DB_* nutzt, überstimmt damit
 * die Datei.
 *
 * Die Vorgaben enthalten bewusst keine Zugangsdaten. Ohne Umgebungsvariablen
 * und ohne pdl_config.local.php gilt PowerDownload als nicht eingerichtet.
 *
 * @phpstan-type DbConfig array{host: string, port: int, user: string, password: string, database: string, persistent: bool}
 * @phpstan-type Settings array{db: DbConfig, source: string}
 */
final class LocalConfig
{
    /**
     * Dateiname innerhalb von pdl-inc/.
     */
    public const string FILENAME = 'pdl_config.local.php';

    /**
     * Pfad relativ zum PowerDownload-Verzeichnis (für Meldungen und die Sperre).
     */
    public const string RELATIVE_PATH = 'pdl-inc/pdl_config.local.php';

    public const array DEFAULT_DB = [
        'host' => 'localhost',
        'port' => 3306,
        'user' => '',
        'password' => '',
        'database' => 'pdl3',
        'persistent' => false,
    ];

    /**
     * Umgebungsvariable je Schlüssel.
     */
    public const array ENVIRONMENT = [
        'host' => 'PDL_DB_HOST',
        'port' => 'PDL_DB_PORT',
        'user' => 'PDL_DB_USER',
        'password' => 'PDL_DB_PASS',
        'database' => 'PDL_DB_NAME',
    ];

    public const string SOURCE_ENVIRONMENT = 'environment';

    public const string SOURCE_FILE = 'file';

    public const string SOURCE_DEFAULTS = 'defaults';

    /**
     * Wirksame Zugangsdaten und ihre Quelle.
     *
     * Quelle „environment“: mindestens eine Umgebungsvariable wirkt;
     * „file“: pdl_config.local.php ist vorhanden; sonst „defaults“.
     *
     * @param callable(string): (string|false) $getenv z. B. static fn (string $name): string|false => getenv($name)
     *
     * @return Settings
     */
    public static function load(callable $getenv, string $localFile): array
    {
        $db = self::DEFAULT_DB;
        $source = self::SOURCE_DEFAULTS;

        if (is_file($localFile)) {
            $db = self::apply($db, self::readFile($localFile));
            $source = self::SOURCE_FILE;
        }

        $environment = self::fromEnvironment($getenv);

        if ($environment !== []) {
            $db = array_replace($db, $environment);
            $source = self::SOURCE_ENVIRONMENT;
        }

        return ['db' => $db, 'source' => $source];
    }

    /**
     * Die gesetzten Umgebungsvariablen PDL_DB_*. Gesetzt heißt: getenv()
     * liefert eine Zeichenkette, bei Server, Benutzer und Datenbank nicht leer,
     * beim Port eine Zahl von 1 bis 65535. Das Passwort gilt so, wie es gesetzt ist.
     *
     * @param callable(string): (string|false) $getenv
     *
     * @return array{host?: string, port?: int, user?: string, password?: string, database?: string}
     */
    public static function fromEnvironment(callable $getenv): array
    {
        $text = static function (string $key) use ($getenv): ?string {
            $raw = $getenv(self::ENVIRONMENT[$key]);

            return is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
        };
        $rawPassword = $getenv(self::ENVIRONMENT['password']);
        $rawPort = $getenv(self::ENVIRONMENT['port']);
        $port = is_string($rawPort) ? filter_var(trim($rawPort), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) : false;

        return array_filter([
            'host' => $text('host'),
            'port' => is_int($port) ? $port : null,
            'user' => $text('user'),
            'password' => is_string($rawPassword) ? $rawPassword : null,
            'database' => $text('database'),
        ], static fn (string|int|null $value): bool => $value !== null);
    }

    /**
     * Überlagert die Zugangsdaten mit dem Abschnitt „db“ der
     * pdl_config.local.php. Unbekannte Schlüssel und Werte mit falschem Typ
     * werden ignoriert.
     *
     * @param DbConfig $db
     *
     * @return DbConfig
     */
    public static function apply(#[\SensitiveParameter] array $db, #[\SensitiveParameter] mixed $local): array
    {
        $override = is_array($local) && is_array($local['db'] ?? null) ? $local['db'] : [];
        $port = $override['port'] ?? null;
        $persistent = $override['persistent'] ?? null;

        return [
            'host' => self::stringOr($override, 'host', $db['host']),
            'port' => is_int($port) && $port >= 1 && $port <= 65535 ? $port : $db['port'],
            'user' => self::stringOr($override, 'user', $db['user']),
            'password' => self::stringOr($override, 'password', $db['password']),
            'database' => self::stringOr($override, 'database', $db['database']),
            'persistent' => is_bool($persistent) ? $persistent : $db['persistent'],
        ];
    }

    /**
     * Ist PowerDownload per Umgebung oder Datei eingerichtet?
     *
     * @param Settings $settings
     */
    public static function isConfigured(array $settings): bool
    {
        return $settings['source'] !== self::SOURCE_DEFAULTS;
    }

    /**
     * Erzeugt den PHP-Quelltext der pdl_config.local.php.
     *
     * Alle Werte gehen durch var_export(). Sonderzeichen im Passwort
     * (' " \ $ ?> Zeilenumbruch) bleiben so unverändert und können den Code
     * nicht verändern.
     *
     * @param array{host: string, port: int, user: string, password: string, database: string, persistent?: bool} $db
     */
    public static function render(#[\SensitiveParameter] array $db, string $generatedAt): string
    {
        $generatedAt = (string) preg_replace('/[^0-9: .-]/', '', $generatedAt);

        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '/*',
            ' * PowerDownload: lokale Konfiguration',
            ' *',
            ' * Erzeugt vom Web-Installer am ' . $generatedAt . '.',
            ' * Enthält Zugangsdaten: nicht weitergeben, nicht ins Repository.',
            ' * Umgebungsvariablen PDL_DB_* haben Vorrang vor diesen Werten.',
            ' * Bei einem Update bleibt die Datei erhalten.',
            ' */',
            '',
            'return [',
            "    'db' => [",
            "        'host' => " . var_export($db['host'], true) . ',',
            "        'port' => " . var_export($db['port'], true) . ',',
            "        'user' => " . var_export($db['user'], true) . ',',
            "        'password' => " . var_export($db['password'], true) . ',',
            "        'database' => " . var_export($db['database'], true) . ',',
            "        'persistent' => " . var_export($db['persistent'] ?? false, true) . ',',
            '    ],',
            '];',
        ];

        return implode("\n", $lines) . "\n";
    }

    /**
     * Legt die Datei exklusiv an (niemals überschreiben) und setzt die Rechte
     * auf 0640, falls der Server das zulässt. Liefert false, wenn das Anlegen
     * nicht möglich war.
     */
    public static function writeFile(string $path, #[\SensitiveParameter] string $content): bool
    {
        $directory = dirname($path);

        if (file_exists($path) || !is_dir($directory) || !is_writable($directory)) {
            return false;
        }

        return self::quietly(static function () use ($path, $content): bool {
            $handle = fopen($path, 'x');

            if ($handle === false) {
                return false;
            }

            $written = fwrite($handle, $content);
            fclose($handle);

            if ($written !== strlen($content)) {
                unlink($path);

                return false;
            }

            chmod($path, 0o640);

            return true;
        });
    }

    /**
     * Liest pdl_config.local.php. Eine Datei, die kein Array liefert, zählt wie
     * eine leere Datei.
     */
    private static function readFile(string $localFile): mixed
    {
        return (static function (string $file): mixed {
            return require $file;
        })($localFile);
    }

    /**
     * Führt eine Dateioperation aus, ohne dass PHP-Warnungen ausgegeben werden
     * (z. B. bei fehlenden Rechten); das Ergebnis zählt.
     *
     * @param callable(): bool $operation
     */
    private static function quietly(callable $operation): bool
    {
        set_error_handler(static fn (): bool => true);

        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @param array<array-key, mixed> $source
     */
    private static function stringOr(array $source, string $key, string $default): string
    {
        $value = $source[$key] ?? null;

        return is_string($value) ? $value : $default;
    }
}
