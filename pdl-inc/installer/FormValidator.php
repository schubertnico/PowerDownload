<?php

/**
 * PowerDownload - Prüfung der Installer-Formulare
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

/**
 * Prüft die Formulare der Installer-Schritte 2 bis 4.
 *
 * Jede Methode liefert die bereinigten Werte und Fehlermeldungen. Der
 * Schlüssel einer Fehlermeldung ist der Feldname im Formular. Zeichen werden
 * ohne mbstring gezählt (PowerDownload setzt die Erweiterung nicht voraus).
 *
 * @phpstan-type DbInput array{host: string, port: int, user: string, password: string, database: string}
 * @phpstan-type WebsiteSettings array{name: string, url: string, email: string, description: string}
 * @phpstan-type AdminInput array{nick: string, email: string, password: string}
 */
final class FormValidator
{
    public const int DEFAULT_DB_PORT = 3306;

    public const int HOST_MAX = 255;

    public const int DB_NAME_MAX = 64;

    public const int DB_USER_MAX = 80;

    public const int DB_PASSWORD_MAX = 255;

    public const int SITE_NAME_MAX = 100;

    public const int URL_MAX = 255;

    /**
     * Spaltenbreite von pdl3_user.email.
     */
    public const int EMAIL_MAX = 128;

    public const int DESCRIPTION_MAX = 300;

    public const int NICK_MIN = 3;

    public const int NICK_MAX = 30;

    public const string NICK_PATTERN = '/^[A-Za-z\x{00C4}\x{00D6}\x{00DC}\x{00E4}\x{00F6}\x{00FC}\x{00DF}0-9_.\-]{3,30}$/u';

    /**
     * Wie bei der Registrierung mindestens 8 Zeichen mit Buchstabe und Ziffer.
     * bcrypt berücksichtigt höchstens 72 Byte; längere Passwörter würden
     * unbemerkt abgeschnitten und werden deshalb abgelehnt.
     */
    public const int PASSWORD_MIN = 8;

    public const int PASSWORD_MAX_BYTES = 72;

    /**
     * Erfüllen die Längen- und Ziffernregel, sind aber allgemein bekannt.
     */
    public const array FORBIDDEN_PASSWORDS = ['admin123', 'passwort1', 'password1'];

    /**
     * Schritt 2: Datenbankzugang. Das Passwort wird nicht getrimmt.
     *
     * @param array<array-key, mixed> $input
     *
     * @return array{values: DbInput, errors: array<string, string>}
     */
    public static function database(#[\SensitiveParameter] array $input): array
    {
        $host = self::text($input, 'db_host');
        $port = self::port(self::text($input, 'db_port'));
        $name = self::text($input, 'db_name');
        $user = self::text($input, 'db_user');
        $password = is_string($input['db_password'] ?? null) ? $input['db_password'] : '';

        $errors = array_filter([
            'db_host' => self::fieldError(
                $host,
                self::isHostname($host),
                'Bitte geben Sie den Datenbankserver an (oft „localhost“).',
                'Der Servername enthält ungültige Zeichen. Erlaubt sind ein Rechnername wie „sql.example.org“ oder eine IP-Adresse. Den Port tragen Sie bitte ins eigene Feld ein.',
            ),
            'db_port' => $port === null ? 'Der Port muss eine Zahl zwischen 1 und 65535 sein.' : null,
            'db_name' => self::fieldError(
                $name,
                preg_match('/^[A-Za-z0-9_$-]{1,' . self::DB_NAME_MAX . '}$/', $name) === 1,
                'Bitte geben Sie den Namen der Datenbank an.',
                'Der Datenbankname darf nur Buchstaben, Ziffern sowie _ - $ enthalten (höchstens 64 Zeichen).',
            ),
            'db_user' => self::fieldError(
                $user,
                self::isPlainText($user, self::DB_USER_MAX),
                'Bitte geben Sie den Benutzernamen für die Datenbank an.',
                'Der Benutzername ist zu lang oder enthält ungültige Zeichen.',
            ),
            'db_password' => strlen($password) > self::DB_PASSWORD_MAX || str_contains($password, "\0")
                ? 'Das Passwort ist zu lang oder enthält ungültige Zeichen.'
                : null,
        ]);

        return [
            'values' => [
                'host' => $host,
                'port' => $port ?? self::DEFAULT_DB_PORT,
                'user' => $user,
                'password' => $password,
                'database' => $name,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Schritt 3: Name, Adresse, Absenderadresse und Kurzbeschreibung der
     * Download-Seite.
     *
     * @param array<array-key, mixed> $input
     *
     * @return array{values: WebsiteSettings, errors: array<string, string>}
     */
    public static function website(array $input): array
    {
        $name = self::text($input, 'site_name');
        $url = rtrim(self::text($input, 'site_url'), '/');
        $email = self::text($input, 'site_email');
        $description = self::text($input, 'site_description');

        $errors = array_filter([
            'site_name' => self::fieldError(
                $name,
                self::isPlainText($name, self::SITE_NAME_MAX),
                'Bitte geben Sie einen Namen für Ihre Download-Seite an.',
                'Der Name darf höchstens ' . self::SITE_NAME_MAX . ' Zeichen lang sein und keine Zeilenumbrüche enthalten.',
            ),
            'site_url' => self::fieldError(
                $url,
                self::isWebUrl($url) && strlen($url) <= self::URL_MAX,
                'Bitte geben Sie die Adresse Ihrer Download-Seite an.',
                'Bitte geben Sie eine vollständige Adresse mit http:// oder https:// an (höchstens ' . self::URL_MAX . ' Zeichen).',
            ),
            'site_email' => self::fieldError(
                $email,
                self::isEmail($email),
                'Bitte geben Sie die Absenderadresse für E-Mails an.',
                'Bitte geben Sie eine gültige E-Mail-Adresse an (höchstens ' . self::EMAIL_MAX . ' Zeichen).',
            ),
            'site_description' => $description !== '' && !self::isPlainText($description, self::DESCRIPTION_MAX)
                ? 'Die Kurzbeschreibung darf höchstens ' . self::DESCRIPTION_MAX . ' Zeichen lang sein und keine Zeilenumbrüche enthalten.'
                : null,
        ]);

        return [
            'values' => ['name' => $name, 'url' => $url, 'email' => $email, 'description' => $description],
            'errors' => $errors,
        ];
    }

    /**
     * Schritt 4: Administrator. Angemeldet wird später mit Benutzername und
     * Passwort; das Passwort wird wie im Login-Formular nicht getrimmt.
     *
     * @param array<array-key, mixed> $input
     *
     * @return array{values: AdminInput, errors: array<string, string>}
     */
    public static function admin(#[\SensitiveParameter] array $input): array
    {
        $nick = self::text($input, 'admin_nick');
        $email = self::text($input, 'admin_email');
        $password = is_string($input['admin_password'] ?? null) ? $input['admin_password'] : '';
        $confirm = is_string($input['admin_password_confirm'] ?? null) ? $input['admin_password_confirm'] : '';

        $errors = array_filter([
            'admin_nick' => self::fieldError(
                $nick,
                self::isNickname($nick),
                'Bitte geben Sie einen Benutzernamen an.',
                'Der Benutzername muss 3 bis 30 Zeichen lang sein und darf nur Buchstaben (auch Umlaute), Ziffern sowie . _ - enthalten.',
            ),
            'admin_email' => self::fieldError(
                $email,
                self::isEmail($email),
                'Bitte geben Sie Ihre E-Mail-Adresse an.',
                'Bitte geben Sie eine gültige E-Mail-Adresse an (höchstens ' . self::EMAIL_MAX . ' Zeichen).',
            ),
            'admin_password' => self::passwordError($password, $nick),
        ]);

        if (!isset($errors['admin_password']) && $password !== $confirm) {
            $errors['admin_password_confirm'] = 'Die beiden Passwörter stimmen nicht überein.';
        }

        return [
            'values' => ['nick' => $nick, 'email' => $email, 'password' => $password],
            'errors' => $errors,
        ];
    }

    /**
     * Rechnername, IPv4- oder IPv6-Adresse (ohne Klammern). Doppelpunkte nur
     * bei IPv6, damit „host:3306“ oder „p:host“ nicht als Server durchgehen.
     */
    public static function isHostname(string $host): bool
    {
        if ($host === '' || strlen($host) > self::HOST_MAX) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return true;
        }

        return preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9_.-]*[A-Za-z0-9])?$/', $host) === 1;
    }

    public static function isWebUrl(string $url): bool
    {
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && preg_match('/[\s"\'<>]/', $url) !== 1;
    }

    public static function isEmail(string $email): bool
    {
        return $email !== ''
            && strlen($email) <= self::EMAIL_MAX
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isNickname(string $nick): bool
    {
        return preg_match(self::NICK_PATTERN, $nick) === 1;
    }

    /**
     * Fehlermeldung zum Passwort oder null.
     */
    public static function passwordError(#[\SensitiveParameter] string $password, string $nick = ''): ?string
    {
        if ($password === '') {
            return 'Bitte geben Sie ein Passwort an.';
        }

        $length = self::charCount($password);

        if ($length === null || $length < self::PASSWORD_MIN) {
            return 'Das Passwort muss mindestens ' . self::PASSWORD_MIN . ' Zeichen lang sein.';
        }

        if (strlen($password) > self::PASSWORD_MAX_BYTES) {
            return 'Das Passwort darf höchstens ' . self::PASSWORD_MAX_BYTES . ' Zeichen lang sein (Umlaute und Sonderzeichen zählen doppelt).';
        }

        if (preg_match('/\pL/u', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
            return 'Das Passwort muss mindestens einen Buchstaben und eine Ziffer enthalten.';
        }

        foreach (self::FORBIDDEN_PASSWORDS as $forbidden) {
            if (self::equalsIgnoringCase($password, $forbidden)) {
                return 'Dieses Passwort ist zu bekannt. Bitte wählen Sie ein eigenes.';
            }
        }

        if ($nick !== '' && self::equalsIgnoringCase($password, $nick)) {
            return 'Das Passwort darf nicht gleich dem Benutzernamen sein.';
        }

        return null;
    }

    /**
     * Anzahl der Zeichen einer UTF-8-Zeichenkette, null bei ungültigem UTF-8.
     */
    public static function charCount(string $value): ?int
    {
        $count = preg_match_all('/./su', $value);

        return is_int($count) ? $count : null;
    }

    /**
     * Gleich ohne Rücksicht auf Groß- und Kleinschreibung, auch bei Umlauten.
     */
    private static function equalsIgnoringCase(string $value, string $other): bool
    {
        return preg_match('/^' . preg_quote($other, '/') . '$/iu', $value) === 1;
    }

    /**
     * Leerer Wert ergibt den Standardport 3306, ungültiger Wert null.
     */
    private static function port(string $value): ?int
    {
        if ($value === '') {
            return self::DEFAULT_DB_PORT;
        }

        $port = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);

        return is_int($port) ? $port : null;
    }

    /**
     * Fehlermeldung für ein Pflichtfeld: eigene Meldung für leere Eingaben,
     * null bei gültigem Wert.
     */
    private static function fieldError(string $value, bool $valid, string $emptyMessage, string $invalidMessage): ?string
    {
        if ($value === '') {
            return $emptyMessage;
        }

        return $valid ? null : $invalidMessage;
    }

    /**
     * Nicht leer, gültiges UTF-8, höchstens $max Zeichen, keine Steuerzeichen
     * (auch keine Zeilenumbrüche, die in Mail-Kopfzeilen gefährlich wären).
     */
    private static function isPlainText(string $value, int $max): bool
    {
        $length = self::charCount($value);

        return $value !== ''
            && $length !== null
            && $length <= $max
            && preg_match('/[\x00-\x1F\x7F]/', $value) !== 1;
    }

    /**
     * @param array<array-key, mixed> $input
     */
    private static function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
