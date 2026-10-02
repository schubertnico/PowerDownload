<?php

/**
 * PowerDownload - Ablaufsteuerung des Web-Installers
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

use PowerDownload\LocalConfig;

/**
 * Ablaufsteuerung des Web-Installers.
 *
 * Schritte: 1 Systemprüfung, 2 Datenbank, 3 Website, 4 Administrator,
 * 5 Abschluss. Grundsätze:
 *  - Schreibende Aktionen nur per POST mit CSRF-Token, danach Weiterleitung
 *    (Post/Redirect/Get). GET-Parameter wählen nur den angezeigten Schritt.
 *  - Zugangsdaten erscheinen weder in Protokollen noch in Fehlermeldungen.
 *  - Ist PowerDownload bereits eingerichtet, gibt es nur die Sperrseite (HTTP 403).
 *
 * @phpstan-import-type DbInput from FormValidator
 * @phpstan-import-type DoneInfo from Wizard
 *
 * @phpstan-type Result array{errors: array<string, string>, message: string, tables: list<string>}
 */
final class Controller
{
    public const string ENTRY = 'install.php';

    /**
     * Altdateien früherer Versionen, die nach einem Update per FTP liegen
     * bleiben können. Die Abschlussseite nennt jede gefundene Datei.
     */
    public const array LEGACY_FILES = ['setup.php', 'install_303.php', 'install_querys.inc', 'update_224to303.php', 'update_301to303.php'];

    /**
     * Vorgabe für die Kurzbeschreibung, falls die Schemadatei keine enthält.
     */
    public const string DEFAULT_DESCRIPTION = 'PowerDownload – Datei- und Release-Verwaltung mit Statistik, Suche und Benutzerbereich.';

    /** @var list<string> Protokolleinträge ohne Zugangsdaten */
    private array $events = [];

    /**
     * @param string $configSource Quelle der Zugangsdaten (LocalConfig::SOURCE_*)
     * @param \Closure(): ?bool $probe prüft die per Umgebung eingerichtete Datenbank für die Sperre
     * @param \Closure(DbInput): \mysqli $connect baut eine Datenbankverbindung auf
     */
    public function __construct(
        private readonly string $rootDir,
        private readonly Wizard $wizard,
        #[\SensitiveParameter]
        private readonly string $csrfToken,
        private readonly string $configSource,
        private readonly \Closure $probe,
        private readonly \Closure $connect,
    ) {
    }

    /**
     * @param array<array-key, mixed> $query $_GET
     * @param array<array-key, mixed> $post $_POST
     * @param array<array-key, mixed> $server $_SERVER
     */
    public function handle(string $method, array $query, #[\SensitiveParameter] array $post, array $server): Response
    {
        $isPost = $method === 'POST';
        $action = $isPost && is_string($post['action'] ?? null) ? $post['action'] : '';

        // Diese Sitzung hat die Installation abgeschlossen: Abschlussseite.
        $done = $this->wizard->done();

        if ($done !== null) {
            return $this->donePage($done, $action, $post);
        }

        // Sperre: eine bestehende Installation wird niemals angefasst.
        $lockReason = InstallState::detectLockReason($this->rootDir, $this->configSource, $this->probe);

        if ($lockReason !== null) {
            return Response::page('locked', 'Installer gesperrt', 0, [
                'reason' => $lockReason,
                'lockFile' => InstallState::existingLockFile($this->rootDir) ?? InstallState::LOCK_FILES[0],
            ], 403);
        }

        $requested = self::intParam($query, 'step', $this->wizard->completed() + 1);
        $step = $this->wizard->allowedStep($requested);

        if (!$isPost && $requested !== $step) {
            return Response::redirect(self::stepUrl($step));
        }

        $result = self::failure('');

        if ($isPost) {
            $actionStep = Wizard::ACTIONS[$action] ?? null;

            if ($actionStep === null) {
                $result = self::failure('Unbekannte Aktion. Bitte verwenden Sie die Schaltflächen des Installers.');
            } elseif (!$this->wizard->canEnter($actionStep)) {
                return Response::redirect(self::stepUrl($this->wizard->allowedStep($actionStep)));
            } elseif (!$this->validCsrf($post)) {
                $step = $actionStep;
                $result = self::failure('Ihre Sitzung ist abgelaufen oder Cookies sind blockiert. Bitte senden Sie das Formular erneut ab.');
            } else {
                $step = $actionStep;
                $outcome = $this->dispatch($action, $post, $server);

                if ($outcome instanceof Response) {
                    return $outcome;
                }
                $result = $outcome;
            }
        }

        return $this->stepPage($step, $result, $isPost, $post, $server);
    }

    /**
     * Protokolleinträge dieser Anfrage (ohne Zugangsdaten).
     *
     * @return list<string>
     */
    public function events(): array
    {
        return $this->events;
    }

    public static function stepUrl(int $step): string
    {
        return self::ENTRY . '?step=' . $step;
    }

    /**
     * install.php und gefundene Altdateien, die vom Server gelöscht werden sollten.
     *
     * @return list<string>
     */
    public static function filesToDelete(string $rootDir): array
    {
        $files = [self::ENTRY];

        foreach (self::LEGACY_FILES as $file) {
            if (is_file($rootDir . '/' . $file)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * Vorgabe der Kurzbeschreibung aus der Schemadatei.
     */
    public static function defaultDescription(string $rootDir): string
    {
        try {
            $rows = Schema::rows(Schema::fromFile($rootDir . '/' . Schema::FILENAME));
        } catch (\RuntimeException|\UnexpectedValueException) {
            return self::DEFAULT_DESCRIPTION;
        }

        foreach ($rows['pdl3_settings'] ?? [] as $row) {
            $value = $row['wert'] ?? null;

            if (($row['variablenname'] ?? null) === 'site_description' && is_string($value)) {
                return $value;
            }
        }

        return self::DEFAULT_DESCRIPTION;
    }

    /**
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $server
     *
     * @return Response|Result
     */
    private function dispatch(string $action, #[\SensitiveParameter] array $post, array $server): Response|array
    {
        return match ($action) {
            'requirements' => $this->handleRequirements($server),
            'database' => $this->handleDatabase($post),
            'website' => $this->handleWebsite($post),
            'admin' => $this->handleAdmin($post),
            default => $this->handleFinish(),
        };
    }

    /**
     * @param array<array-key, mixed> $server
     *
     * @return Response|Result
     */
    private function handleRequirements(array $server): Response|array
    {
        if (!Requirements::allRequiredMet($this->checks($server))) {
            return self::failure('Bitte beheben Sie zuerst die rot markierten Punkte und laden Sie die Seite dann neu.');
        }

        $this->wizard->completeRequirements();

        return Response::redirect(self::stepUrl(Wizard::STEP_DATABASE));
    }

    /**
     * @param array<array-key, mixed> $post
     *
     * @return Response|Result
     */
    private function handleDatabase(#[\SensitiveParameter] array $post): Response|array
    {
        $input = FormValidator::database($post);

        if ($input['errors'] !== []) {
            return self::failure('Bitte prüfen Sie die markierten Felder.', $input['errors']);
        }

        $check = DatabaseSetup::inspect($this->connect, $input['values']);

        if (!$check['ok']) {
            $this->events[] = 'Verbindungstest fehlgeschlagen (Fehlercode ' . $check['code'] . ').';
            $field = self::errorField($check['code']);
            $error = DatabaseSetup::friendlyError($check['code']);

            return $field === null
                ? self::failure($error)
                : self::failure('Die Verbindung zur Datenbank ist fehlgeschlagen.', [$field => $error]);
        }

        $server = $check['server'];
        $tables = $check['tables'];

        if ($server === null || !$server->isSupported()) {
            return self::failure(
                ($server === null ? 'Die Version des Datenbankservers ist nicht erkennbar.' : 'Der Datenbankserver meldet ' . $server->label() . '.')
                . ' PowerDownload benötigt ' . ServerVersion::requirement() . '.',
            );
        }

        if ($tables !== []) {
            return [
                'errors' => [],
                'message' => 'Diese Datenbank enthält bereits PowerDownload-Tabellen. Der Installer überschreibt keine bestehenden Daten. Für eine Neuinstallation wählen Sie bitte eine leere Datenbank.',
                'tables' => $tables,
            ];
        }

        $this->wizard->storeDatabase($input['values'], $server->label());
        $this->wizard->setNotice('success', 'Verbindung hergestellt: ' . $server->label() . '.');

        return Response::redirect(self::stepUrl(Wizard::STEP_WEBSITE));
    }

    /**
     * @param array<array-key, mixed> $post
     *
     * @return Response|Result
     */
    private function handleWebsite(array $post): Response|array
    {
        $input = FormValidator::website($post);

        if ($input['errors'] !== []) {
            return self::failure('Bitte prüfen Sie die markierten Felder.', $input['errors']);
        }

        $this->wizard->storeWebsite($input['values']);

        return Response::redirect(self::stepUrl(Wizard::STEP_ADMIN));
    }

    /**
     * @param array<array-key, mixed> $post
     *
     * @return Response|Result
     */
    private function handleAdmin(#[\SensitiveParameter] array $post): Response|array
    {
        $input = FormValidator::admin($post);

        if ($input['errors'] !== []) {
            return self::failure('Bitte prüfen Sie die markierten Felder.', $input['errors']);
        }

        $values = $input['values'];
        // Die Anmeldung prüft mit password_verify() (pdl_header.inc.php).
        $this->wizard->storeAdmin($values['nick'], $values['email'], password_hash($values['password'], PASSWORD_DEFAULT));

        return Response::redirect(self::stepUrl(Wizard::STEP_FINISH));
    }

    /**
     * Spielt Schema, Einstellungen und Administrator ein, schreibt
     * pdl_config.local.php (falls möglich) und setzt die Sperre.
     *
     * @return Response|Result
     */
    private function handleFinish(): Response|array
    {
        $database = $this->wizard->database();
        $website = $this->wizard->website();
        $admin = $this->wizard->admin();

        if ($database === null || $website === null || $admin === null) {
            return Response::redirect(self::stepUrl($this->wizard->allowedStep(Wizard::STEP_FINISH)));
        }

        try {
            $outcome = Setup::run($this->rootDir, $this->connect, $database, $website, $admin, date('Y-m-d H:i:s'));
        } catch (\RuntimeException $e) {
            // Setup::run() liefert nur verständliche Meldungen ohne Zugangsdaten.
            $this->events[] = 'Installation abgebrochen (Fehlercode ' . $e->getCode() . ').';

            return self::failure($e->getMessage());
        }

        $lockFile = $outcome['lock_file'];
        $this->wizard->finish($outcome['config_written'], $outcome['config_source'], $lockFile ?? '');
        $this->events[] = 'Installation abgeschlossen (Administrator-ID ' . $outcome['admin_id'] . ', pdl_config.local.php '
            . ($outcome['config_written'] ? 'geschrieben' : 'zum Herunterladen') . ', Sperrdatei ' . ($lockFile ?? 'FEHLT') . ').';

        return Response::redirectWithNewSession(self::ENTRY);
    }

    /**
     * @param DoneInfo $done
     * @param array<array-key, mixed> $post
     */
    private function donePage(array $done, string $action, #[\SensitiveParameter] array $post): Response
    {
        $configPresent = is_file($this->rootDir . '/' . LocalConfig::RELATIVE_PATH);

        if ($configPresent && $done['config_source'] !== '') {
            $this->wizard->forgetConfigSource();
            $done['config_source'] = '';
        }

        if ($action === 'download_config' && $done['config_source'] !== '' && $this->validCsrf($post)) {
            return Response::download(LocalConfig::FILENAME, $done['config_source']);
        }

        return Response::page('done', 'Installation abgeschlossen', 0, [
            'done' => $done,
            'configPresent' => $configPresent,
            'csrf' => $this->csrfToken,
            'deleteFiles' => self::filesToDelete($this->rootDir),
        ], 200);
    }

    /**
     * @param Result $result
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $server
     */
    private function stepPage(int $step, array $result, bool $isPost, #[\SensitiveParameter] array $post, array $server): Response
    {
        $title = Wizard::STEPS[$step] ?? 'Installation';
        $common = ['csrf' => $this->csrfToken, 'errors' => $result['errors'], 'message' => $result['message']];

        return match ($step) {
            Wizard::STEP_DATABASE => Response::page('database', $title, $step, $common + [
                'old' => $isPost ? self::postValues($post, ['db_host', 'db_port', 'db_name', 'db_user']) : $this->oldDatabase(),
                'tables' => $result['tables'],
            ], 200),
            Wizard::STEP_WEBSITE => Response::page('website', $title, $step, $common + [
                'old' => $isPost ? self::postValues($post, ['site_name', 'site_url', 'site_email', 'site_description']) : $this->oldWebsite($server),
                'notice' => $isPost ? null : $this->wizard->takeNotice(),
            ], 200),
            Wizard::STEP_ADMIN => Response::page('admin', $title, $step, $common + [
                'old' => $isPost ? self::postValues($post, ['admin_nick', 'admin_email']) : $this->oldAdmin(),
            ], 200),
            Wizard::STEP_FINISH => Response::page('finish', $title, $step, $common + [
                'wizard' => $this->wizard,
                'configWritable' => Requirements::canWriteLocalConfig($this->rootDir),
                'lockTarget' => InstallState::lockTarget($this->rootDir),
            ], 200),
            default => $this->requirementsPage($title, $common, $server),
        };
    }

    /**
     * @param array{csrf: string, errors: array<string, string>, message: string} $common
     * @param array<array-key, mixed> $server
     */
    private function requirementsPage(string $title, array $common, array $server): Response
    {
        $checks = $this->checks($server);

        return Response::page('requirements', $title, Wizard::STEP_REQUIREMENTS, $common + [
            'checks' => $checks,
            'allOk' => Requirements::allRequiredMet($checks),
        ], 200);
    }

    /**
     * @param array<array-key, mixed> $server
     *
     * @return list<array{id: string, label: string, ok: bool, kind: string, detail: string}>
     */
    private function checks(array $server): array
    {
        return Requirements::check(
            $this->rootDir,
            $server,
            PHP_VERSION,
            extension_loaded(...),
            static fn (string $name): string|false => ini_get($name),
        );
    }

    /**
     * Formularwerte für Schritt 2 (ohne Passwort, das wird nie ausgegeben).
     *
     * @return array<string, string>
     */
    private function oldDatabase(): array
    {
        $database = $this->wizard->database();

        return [
            'db_host' => $database['host'] ?? 'localhost',
            'db_port' => (string) ($database['port'] ?? FormValidator::DEFAULT_DB_PORT),
            'db_name' => $database['database'] ?? '',
            'db_user' => $database['user'] ?? '',
        ];
    }

    /**
     * Formularwerte für Schritt 3, beim ersten Aufruf aus der Anfrage vorbelegt.
     *
     * @param array<array-key, mixed> $server
     *
     * @return array<string, string>
     */
    private function oldWebsite(array $server): array
    {
        $website = $this->wizard->website();

        if ($website !== null) {
            return [
                'site_name' => $website['name'],
                'site_url' => $website['url'],
                'site_email' => $website['email'],
                'site_description' => $website['description'],
            ];
        }

        $url = Wizard::suggestSiteUrl($server);

        return [
            'site_name' => '',
            'site_url' => $url,
            'site_email' => Wizard::suggestSender($url),
            'site_description' => self::defaultDescription($this->rootDir),
        ];
    }

    /**
     * Formularwerte für Schritt 4 (ohne Passwörter).
     *
     * @return array<string, string>
     */
    private function oldAdmin(): array
    {
        $admin = $this->wizard->admin();

        return ['admin_nick' => $admin['nick'] ?? '', 'admin_email' => $admin['email'] ?? ''];
    }

    /**
     * @param array<array-key, mixed> $post
     */
    private function validCsrf(#[\SensitiveParameter] array $post): bool
    {
        $token = $post['csrf_token'] ?? null;

        return $this->csrfToken !== '' && is_string($token) && hash_equals($this->csrfToken, $token);
    }

    /**
     * Welches Feld zeigt einen Verbindungsfehler an? null = allgemeine Meldung.
     */
    private static function errorField(int $code): ?string
    {
        return match ($code) {
            1045, 1698 => 'db_password',
            1044, 1049 => 'db_name',
            1130, 2002, 2003, 2005 => 'db_host',
            default => null,
        };
    }

    /**
     * @param array<string, string> $errors
     *
     * @return Result
     */
    private static function failure(string $message, array $errors = []): array
    {
        return ['errors' => $errors, 'message' => $message, 'tables' => []];
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private static function intParam(array $query, string $key, int $default): int
    {
        $value = $query[$key] ?? null;

        if (!is_string($value)) {
            return $default;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($number) ? $number : $default;
    }

    /**
     * @param array<array-key, mixed> $post
     * @param list<string> $keys
     *
     * @return array<string, string>
     */
    private static function postValues(#[\SensitiveParameter] array $post, array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $value = $post[$key] ?? '';
            $values[$key] = is_string($value) ? trim($value) : '';
        }

        return $values;
    }
}
