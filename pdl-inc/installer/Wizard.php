<?php

/**
 * PowerDownload - Fortschritt des Web-Installers
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

/**
 * Fortschritt des Installers in der Session.
 *
 * Gespeichert werden nur die Eingaben, die der letzte Schritt braucht. Das
 * Administrator-Passwort liegt ausschließlich als Hash vor; das
 * Datenbank-Passwort wird nach dem Abschluss aus der Session entfernt.
 *
 * @phpstan-import-type DbInput from FormValidator
 * @phpstan-import-type WebsiteSettings from FormValidator
 * @phpstan-import-type AdminData from DatabaseSetup
 *
 * @phpstan-type DoneInfo array{config_written: bool, config_source: string, lock_file: string, admin_nick: string}
 * @phpstan-type Notice array{type: string, message: string}
 */
final class Wizard
{
    public const string SESSION_KEY = 'pdl_installer';

    public const int STEP_REQUIREMENTS = 1;

    public const int STEP_DATABASE = 2;

    public const int STEP_WEBSITE = 3;

    public const int STEP_ADMIN = 4;

    public const int STEP_FINISH = 5;

    public const array STEPS = [
        self::STEP_REQUIREMENTS => 'Systemprüfung',
        self::STEP_DATABASE => 'Datenbank',
        self::STEP_WEBSITE => 'Website',
        self::STEP_ADMIN => 'Administrator',
        self::STEP_FINISH => 'Abschluss',
    ];

    /**
     * POST-Aktionen (Formularfeld „action“) und der Schritt, zu dem sie gehören.
     */
    public const array ACTIONS = [
        'requirements' => self::STEP_REQUIREMENTS,
        'database' => self::STEP_DATABASE,
        'website' => self::STEP_WEBSITE,
        'admin' => self::STEP_ADMIN,
        'finish' => self::STEP_FINISH,
    ];

    private int $completed = 0;

    /** @var DbInput|null */
    private ?array $database = null;

    private string $serverLabel = '';

    /** @var WebsiteSettings|null */
    private ?array $website = null;

    /** @var AdminData|null */
    private ?array $admin = null;

    /** @var DoneInfo|null */
    private ?array $done = null;

    /** @var Notice|null einmalige Meldung nach einer Weiterleitung */
    private ?array $notice = null;

    /**
     * Stellt den Zustand aus der Session wieder her. Unvollständige oder
     * manipulierte Daten führen zu einem früheren Schritt, nie zu einem Fehler.
     */
    public static function fromSession(#[\SensitiveParameter] mixed $data): self
    {
        $wizard = new self();

        if (!is_array($data)) {
            return $wizard;
        }

        $completed = $data['completed'] ?? 0;
        $wizard->completed = is_int($completed) ? max(0, min($completed, self::STEP_FINISH)) : 0;
        $wizard->database = SessionData::database($data['database'] ?? null);
        $serverLabel = $data['server'] ?? '';
        $wizard->serverLabel = is_string($serverLabel) ? $serverLabel : '';
        $wizard->website = SessionData::website($data['website'] ?? null);
        $wizard->admin = SessionData::admin($data['admin'] ?? null);
        $wizard->done = SessionData::done($data['done'] ?? null);
        $wizard->notice = SessionData::notice($data['notice'] ?? null);

        return $wizard;
    }

    /**
     * @return array{completed: int, database: DbInput|null, server: string, website: WebsiteSettings|null, admin: AdminData|null, done: DoneInfo|null, notice: Notice|null}
     */
    public function toSession(): array
    {
        return [
            'completed' => $this->completed,
            'database' => $this->database,
            'server' => $this->serverLabel,
            'website' => $this->website,
            'admin' => $this->admin,
            'done' => $this->done,
            'notice' => $this->notice,
        ];
    }

    /**
     * Höchster Schritt, dessen Daten vollständig vorliegen.
     */
    public function completed(): int
    {
        $completed = $this->completed;

        if ($this->database === null) {
            $completed = min($completed, self::STEP_REQUIREMENTS);
        }

        if ($this->website === null) {
            $completed = min($completed, self::STEP_DATABASE);
        }

        if ($this->admin === null) {
            $completed = min($completed, self::STEP_WEBSITE);
        }

        return $completed;
    }

    /**
     * Begrenzt einen angefragten Schritt auf die bereits erreichbaren.
     */
    public function allowedStep(int $requested): int
    {
        $maxStep = min($this->completed() + 1, self::STEP_FINISH);

        return max(self::STEP_REQUIREMENTS, min($requested, $maxStep));
    }

    public function canEnter(int $step): bool
    {
        return $this->allowedStep($step) === $step;
    }

    public function completeRequirements(): void
    {
        $this->completed = max($this->completed, self::STEP_REQUIREMENTS);
    }

    /**
     * @param DbInput $database
     */
    public function storeDatabase(#[\SensitiveParameter] array $database, string $serverLabel): void
    {
        $this->database = $database;
        $this->serverLabel = $serverLabel;
        $this->completed = max($this->completed, self::STEP_DATABASE);
    }

    /**
     * @param WebsiteSettings $website
     */
    public function storeWebsite(array $website): void
    {
        $this->website = $website;
        $this->completed = max($this->completed, self::STEP_WEBSITE);
    }

    public function storeAdmin(string $nick, string $email, #[\SensitiveParameter] string $passwordHash): void
    {
        $this->admin = ['nick' => $nick, 'email' => $email, 'password_hash' => $passwordHash];
        $this->completed = max($this->completed, self::STEP_ADMIN);
    }

    /**
     * Schließt die Installation ab und verwirft alle Zugangsdaten. Nur wenn
     * pdl_config.local.php von Hand angelegt werden muss, bleibt ihr Inhalt
     * bis dahin in der Session.
     *
     * @param string $lockFile relativer Pfad der Sperrdatei, leer wenn sie fehlt
     */
    public function finish(bool $configWritten, #[\SensitiveParameter] string $configSource, string $lockFile): void
    {
        $this->done = [
            'config_written' => $configWritten,
            'config_source' => $configWritten ? '' : $configSource,
            'lock_file' => $lockFile,
            'admin_nick' => $this->admin['nick'] ?? '',
        ];
        $this->completed = self::STEP_FINISH;
        $this->database = null;
        $this->website = null;
        $this->admin = null;
        $this->notice = null;
    }

    /**
     * Die von Hand angelegte pdl_config.local.php liegt inzwischen vor; ihr
     * Inhalt wird in der Session nicht mehr gebraucht.
     */
    public function forgetConfigSource(): void
    {
        if ($this->done !== null) {
            $this->done['config_source'] = '';
        }
    }

    /**
     * Meldung für die nächste Seite (nach der Weiterleitung).
     */
    public function setNotice(string $type, string $message): void
    {
        $this->notice = ['type' => in_array($type, SessionData::NOTICE_TYPES, true) ? $type : 'info', 'message' => $message];
    }

    /**
     * Liefert die Meldung einmal und vergisst sie dann.
     *
     * @return Notice|null
     */
    public function takeNotice(): ?array
    {
        $notice = $this->notice;
        $this->notice = null;

        return $notice;
    }

    /**
     * @return DbInput|null
     */
    public function database(): ?array
    {
        return $this->database;
    }

    /**
     * Erkannter Datenbankserver, z. B. „MySQL 8.0.46“.
     */
    public function serverLabel(): string
    {
        return $this->serverLabel;
    }

    /**
     * @return WebsiteSettings|null
     */
    public function website(): ?array
    {
        return $this->website;
    }

    /**
     * @return AdminData|null
     */
    public function admin(): ?array
    {
        return $this->admin;
    }

    /**
     * @return DoneInfo|null
     */
    public function done(): ?array
    {
        return $this->done;
    }

    /**
     * Vorschlag für die Adresse der Download-Seite aus der aktuellen Anfrage,
     * z. B. https://www.example.org/downloads für /downloads/install.php.
     *
     * @param array<array-key, mixed> $server $_SERVER
     */
    public static function suggestSiteUrl(array $server): string
    {
        $host = is_string($server['HTTP_HOST'] ?? null) ? $server['HTTP_HOST'] : '';

        if (preg_match('/^[A-Za-z0-9.-]+(?::\d{1,5})?$|^\[[0-9A-Fa-f:.]+\](?::\d{1,5})?$/', $host) !== 1) {
            return '';
        }

        $scheme = Requirements::isHttps($server) ? 'https' : 'http';
        $script = is_string($server['SCRIPT_NAME'] ?? null) ? $server['SCRIPT_NAME'] : '/install.php';
        $path = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if (preg_match('#^[A-Za-z0-9._~/%-]*$#', $path) !== 1) {
            $path = '';
        }

        return $scheme . '://' . $host . $path;
    }

    /**
     * Vorschlag für die Absenderadresse: noreply@<Domain der Download-Seite>.
     */
    public static function suggestSender(string $siteUrl): string
    {
        $host = parse_url($siteUrl, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return '';
        }

        $candidate = 'noreply@' . (str_starts_with(strtolower($host), 'www.') ? substr($host, 4) : $host);

        return filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false ? $candidate : '';
    }
}
