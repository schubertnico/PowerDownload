<?php

/**
 * PowerDownload - Installer-Zustand aus der Session lesen
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

/**
 * Liest die Teile des Installer-Zustands aus der Session (Wizard::fromSession()).
 *
 * Jede Methode liefert vollständige, typrichtige Daten oder null.
 * Unvollständige oder manipulierte Sitzungen führen so zu einem früheren
 * Schritt, nie zu einem Fehler.
 *
 * @phpstan-import-type DbInput from FormValidator
 * @phpstan-import-type WebsiteSettings from FormValidator
 * @phpstan-import-type AdminData from DatabaseSetup
 * @phpstan-import-type DoneInfo from Wizard
 * @phpstan-import-type Notice from Wizard
 */
final class SessionData
{
    /**
     * Erlaubte Typen einer Meldung (Farbe des Bootstrap-Alerts).
     */
    public const array NOTICE_TYPES = ['success', 'danger', 'warning', 'info'];

    /**
     * @return DbInput|null
     */
    public static function database(#[\SensitiveParameter] mixed $data): ?array
    {
        if (!is_array($data) || !is_int($data['port'] ?? null)) {
            return null;
        }

        $strings = self::strings($data, ['host', 'user', 'password', 'database']);

        if ($strings === null) {
            return null;
        }

        return [
            'host' => $strings['host'],
            'port' => $data['port'],
            'user' => $strings['user'],
            'password' => $strings['password'],
            'database' => $strings['database'],
        ];
    }

    /**
     * @return WebsiteSettings|null
     */
    public static function website(mixed $data): ?array
    {
        $strings = is_array($data) ? self::strings($data, ['name', 'url', 'email', 'description']) : null;

        if ($strings === null) {
            return null;
        }

        return ['name' => $strings['name'], 'url' => $strings['url'], 'email' => $strings['email'], 'description' => $strings['description']];
    }

    /**
     * @return AdminData|null
     */
    public static function admin(#[\SensitiveParameter] mixed $data): ?array
    {
        $strings = is_array($data) ? self::strings($data, ['nick', 'email', 'password_hash']) : null;

        if ($strings === null) {
            return null;
        }

        return ['nick' => $strings['nick'], 'email' => $strings['email'], 'password_hash' => $strings['password_hash']];
    }

    /**
     * @return DoneInfo|null
     */
    public static function done(#[\SensitiveParameter] mixed $data): ?array
    {
        if (!is_array($data) || !is_bool($data['config_written'] ?? null)) {
            return null;
        }

        $strings = self::strings($data, ['config_source', 'lock_file', 'admin_nick']);

        if ($strings === null) {
            return null;
        }

        return [
            'config_written' => $data['config_written'],
            'config_source' => $strings['config_source'],
            'lock_file' => $strings['lock_file'],
            'admin_nick' => $strings['admin_nick'],
        ];
    }

    /**
     * @return Notice|null
     */
    public static function notice(mixed $data): ?array
    {
        $strings = is_array($data) ? self::strings($data, ['type', 'message']) : null;

        if ($strings === null || !in_array($strings['type'], self::NOTICE_TYPES, true)) {
            return null;
        }

        return ['type' => $strings['type'], 'message' => $strings['message']];
    }

    /**
     * Liefert die angegebenen Schlüssel, wenn alle Zeichenketten sind.
     *
     * @param array<array-key, mixed> $data
     * @param list<string> $keys
     *
     * @return array<string, string>|null
     */
    private static function strings(#[\SensitiveParameter] array $data, array $keys): ?array
    {
        $result = [];

        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (!is_string($value)) {
                return null;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
