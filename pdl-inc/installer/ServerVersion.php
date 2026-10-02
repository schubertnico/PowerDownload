<?php

/**
 * PowerDownload - Version des Datenbankservers
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

/**
 * Erkennt Typ und Version des Datenbankservers.
 *
 * MariaDB und MySQL zählen getrennt: MariaDB 10.11 ist nicht „neuer“ als
 * MySQL 8.0, nur weil die Nummer größer ist.
 */
final readonly class ServerVersion
{
    public const string MARIADB = 'MariaDB';

    public const string MYSQL = 'MySQL';

    public const string MIN_MARIADB = '10.6';

    public const string MIN_MYSQL = '8.0';

    private function __construct(
        public string $type,
        public string $version,
    ) {
    }

    /**
     * Wertet die Versionskennung aus, z. B. „8.0.46“, „10.11.15-MariaDB-ubu2204“
     * oder „5.5.5-10.6.18-MariaDB-log“. Liefert null, wenn keine
     * Versionsnummer erkennbar ist.
     */
    public static function parse(string $versionString): ?self
    {
        $isMariaDb = stripos($versionString, 'mariadb') !== false;

        // Ältere MariaDB-Server stellen für alte Clients „5.5.5-“ voran.
        $cleaned = $isMariaDb ? (string) preg_replace('/^5\.5\.5-/', '', trim($versionString)) : trim($versionString);

        if (preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?/', $cleaned, $match) !== 1) {
            return null;
        }

        $version = $match[1] . '.' . $match[2] . '.' . ($match[3] ?? '0');

        return new self($isMariaDb ? self::MARIADB : self::MYSQL, $version);
    }

    public function minimum(): string
    {
        return $this->type === self::MARIADB ? self::MIN_MARIADB : self::MIN_MYSQL;
    }

    public function isSupported(): bool
    {
        return version_compare($this->version, $this->minimum(), '>=');
    }

    /**
     * z. B. „MySQL 8.0.46“.
     */
    public function label(): string
    {
        return $this->type . ' ' . $this->version;
    }

    /**
     * Anforderung als Text für Systemprüfung und Fehlermeldungen.
     */
    public static function requirement(): string
    {
        return 'MySQL ' . self::MIN_MYSQL . ' oder neuer bzw. MariaDB ' . self::MIN_MARIADB . ' oder neuer';
    }
}
