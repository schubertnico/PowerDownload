<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Installer\Controller;
use PowerDownload\Installer\Response;
use PowerDownload\Installer\View;
use PowerDownload\Installer\Wizard;
use PowerDownload\LocalConfig;

final class ControllerTest extends InstallerTestCase
{
    private const string TOKEN = 'token123';

    private const array SERVER = ['HTTP_HOST' => 'www.example.org', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/downloads/install.php'];

    /**
     * @param (\Closure(array{host: string, port: int, user: string, password: string, database: string}): \mysqli)|null $connect
     */
    private static function controller(string $root, Wizard $wizard, string $source = LocalConfig::SOURCE_DEFAULTS, ?bool $probe = null, ?\Closure $connect = null): Controller
    {
        return new Controller(
            $root,
            $wizard,
            self::TOKEN,
            $source,
            static fn (): ?bool => $probe,
            $connect ?? static function (array $db): \mysqli {
                throw new \mysqli_sql_exception('Access denied for user secret_user', 1045);
            },
        );
    }

    /**
     * Wizard mit erledigten Schritten 1 bis 4.
     */
    private static function completeWizard(): Wizard
    {
        $wizard = Wizard::fromSession(null);
        $wizard->completeRequirements();
        $wizard->storeDatabase(['host' => 'sql.example.org', 'port' => 3306, 'user' => 'downloads_user', 'password' => 'geheim', 'database' => 'downloads_db'], 'MySQL 8.0.46');
        $wizard->storeWebsite(['name' => 'Downloads Fotoclub Lichtblick', 'url' => 'https://www.example.org/downloads', 'email' => 'downloads@example.org', 'description' => '']);
        $wizard->storeAdmin('Sabine', 'admin@example.org', password_hash('Lichtblick2026', PASSWORD_DEFAULT));

        return $wizard;
    }

    #[Test]
    public function lockedInstallerAnswers403BeforeEvaluatingTheForm(): void
    {
        $root = $this->makeRoot();
        file_put_contents($root . '/pdl-inc/install.lock', 'x');
        $wizard = Wizard::fromSession(null);

        $response = self::controller($root, $wizard)->handle('POST', [], ['action' => 'requirements', 'csrf_token' => self::TOKEN], self::SERVER);

        self::assertSame(403, $response->status);
        self::assertSame('locked', $response->template);
        self::assertSame('lockfile', $response->args['reason']);
        self::assertSame(0, $wizard->completed());
    }

    #[Test]
    public function environmentWithInstalledDatabaseLocks(): void
    {
        $root = $this->makeRoot();

        $response = self::controller($root, Wizard::fromSession(null), LocalConfig::SOURCE_ENVIRONMENT, true)->handle('GET', [], [], self::SERVER);
        self::assertSame(403, $response->status);
        self::assertSame('database', $response->args['reason']);

        $response = self::controller($root, Wizard::fromSession(null), LocalConfig::SOURCE_ENVIRONMENT, null)->handle('GET', [], [], self::SERVER);
        self::assertSame('unreachable', $response->args['reason']);
    }

    #[Test]
    public function stepsCannotBeSkipped(): void
    {
        $root = $this->makeRoot();
        $response = self::controller($root, Wizard::fromSession(null))->handle('GET', ['step' => '4'], [], self::SERVER);

        self::assertSame(Response::REDIRECT, $response->kind);
        self::assertSame('install.php?step=1', $response->location);
    }

    #[Test]
    public function requirementsPageListsChecksAndBlocksWithoutSchema(): void
    {
        $root = $this->makeRoot();
        $controller = self::controller($root, Wizard::fromSession(null));

        $page = $controller->handle('GET', [], [], self::SERVER);
        self::assertSame('requirements', $page->template);
        self::assertFalse($page->args['allOk']);

        $post = $controller->handle('POST', ['step' => '1'], ['action' => 'requirements', 'csrf_token' => self::TOKEN], self::SERVER);
        self::assertSame('requirements', $post->template);
        self::assertStringContainsString('rot markierten', $post->args['message']);
    }

    #[Test]
    public function wrongCsrfTokenShowsAMessage(): void
    {
        $root = $this->makeRoot();
        $wizard = Wizard::fromSession(null);
        $wizard->completeRequirements();

        $response = self::controller($root, $wizard)->handle('POST', [], ['action' => 'database', 'csrf_token' => 'falsch'], self::SERVER);

        self::assertSame('database', $response->template);
        self::assertStringContainsString('Sitzung ist abgelaufen', $response->args['message']);
    }

    #[Test]
    public function connectionErrorIsFriendlyAndShownAtThePasswordField(): void
    {
        $root = $this->makeRoot();
        $wizard = Wizard::fromSession(null);
        $wizard->completeRequirements();
        $controller = self::controller($root, $wizard);

        $response = $controller->handle('POST', [], [
            'action' => 'database', 'csrf_token' => self::TOKEN,
            'db_host' => 'sql.example.org', 'db_port' => '3306', 'db_name' => 'downloads_db', 'db_user' => 'secret_user', 'db_password' => 'falsch',
        ], self::SERVER);

        self::assertSame('database', $response->template);
        self::assertSame(['db_password'], array_keys($response->args['errors']));
        self::assertStringNotContainsString('secret_user', serialize($response->args['errors']) . $response->args['message']);
        self::assertSame(['Verbindungstest fehlgeschlagen (Fehlercode 1045).'], $controller->events());
        self::assertArrayNotHasKey('db_password', $response->args['old'], 'Das Passwort wird nie wieder ausgegeben.');
    }

    #[Test]
    public function finishWithoutWritableDirectoriesChangesNothing(): void
    {
        $root = $this->makeRoot();
        rmdir($root . '/logs');
        rmdir($root . '/pdl-inc');
        $connected = false;
        $connect = static function (array $db) use (&$connected): \mysqli {
            $connected = true;

            throw new \mysqli_sql_exception('nie', 0);
        };

        $response = self::controller($root, self::completeWizard(), LocalConfig::SOURCE_DEFAULTS, null, $connect)
            ->handle('POST', [], ['action' => 'finish', 'csrf_token' => self::TOKEN], self::SERVER);

        self::assertSame('finish', $response->template);
        self::assertStringContainsString('Es wurde nichts verändert.', $response->args['message']);
        self::assertFalse($connected, 'Ohne Sperrmöglichkeit wird die Datenbank nicht berührt.');
    }

    #[Test]
    public function finishReportsDatabaseErrorsWithoutDetails(): void
    {
        $root = $this->makeRoot();
        copy(self::rootDir() . '/pdl-inc/pdl3_schema.sql', $root . '/pdl-inc/pdl3_schema.sql');
        $tables = \PowerDownload\Installer\Setup::configuredTables(self::rootDir());
        file_put_contents($root . '/pdl-inc/pdl_config.inc.php', '<?php $sql_table = ' . var_export($tables, true) . ';');

        $response = self::controller($root, self::completeWizard())->handle('POST', [], ['action' => 'finish', 'csrf_token' => self::TOKEN], self::SERVER);

        self::assertSame('finish', $response->template);
        self::assertStringContainsString('Benutzername oder Passwort ist falsch', $response->args['message']);
        self::assertStringNotContainsString('secret_user', $response->args['message']);
        self::assertFileDoesNotExist($root . '/' . LocalConfig::RELATIVE_PATH);
        self::assertFileDoesNotExist($root . '/pdl-inc/install.lock');
    }

    #[Test]
    public function websiteStepIsPrefilledFromTheRequestAndSchema(): void
    {
        $root = $this->makeRoot();
        copy(self::rootDir() . '/pdl-inc/pdl3_schema.sql', $root . '/pdl-inc/pdl3_schema.sql');
        $wizard = Wizard::fromSession(null);
        $wizard->completeRequirements();
        $wizard->storeDatabase(['host' => 'h', 'port' => 3306, 'user' => 'u', 'password' => 'p', 'database' => 'd'], 'MySQL 8.0.46');
        $wizard->setNotice('success', 'Verbindung hergestellt: MySQL 8.0.46.');

        $response = self::controller($root, $wizard)->handle('GET', ['step' => '3'], [], self::SERVER);

        self::assertSame('website', $response->template);
        self::assertSame('https://www.example.org/downloads', $response->args['old']['site_url']);
        self::assertSame('noreply@example.org', $response->args['old']['site_email']);
        self::assertSame(Controller::defaultDescription(self::rootDir()), $response->args['old']['site_description']);
        self::assertNotSame('', $response->args['old']['site_description']);
        self::assertSame('Verbindung hergestellt: MySQL 8.0.46.', $response->args['notice']['message'] ?? null);

        $post = self::controller($root, $wizard)->handle('POST', [], [
            'action' => 'website', 'csrf_token' => self::TOKEN,
            'site_name' => 'Downloads', 'site_url' => 'https://www.example.org/downloads', 'site_email' => 'downloads@example.org', 'site_description' => '',
        ], self::SERVER);
        self::assertSame('install.php?step=4', $post->location);
    }

    #[Test]
    public function adminStepStoresOnlyAHash(): void
    {
        $root = $this->makeRoot();
        $wizard = self::completeWizard();

        $response = self::controller($root, $wizard)->handle('POST', [], [
            'action' => 'admin', 'csrf_token' => self::TOKEN,
            'admin_nick' => 'Sabine', 'admin_email' => 'admin@example.org', 'admin_password' => 'admin123', 'admin_password_confirm' => 'admin123',
        ], self::SERVER);
        self::assertSame(['admin_password'], array_keys($response->args['errors']));

        $response = self::controller($root, $wizard)->handle('POST', [], [
            'action' => 'admin', 'csrf_token' => self::TOKEN,
            'admin_nick' => 'Jürgen', 'admin_email' => 'admin@example.org', 'admin_password' => 'Lichtblick2026', 'admin_password_confirm' => 'Lichtblick2026',
        ], self::SERVER);
        self::assertSame('install.php?step=5', $response->location);
        self::assertStringNotContainsString('Lichtblick2026', serialize($wizard->toSession()));
        self::assertTrue(password_verify('Lichtblick2026', $wizard->admin()['password_hash'] ?? ''));
    }

    #[Test]
    public function donePageOffersTheConfigOnlyWhileItIsMissing(): void
    {
        $root = $this->makeRoot();
        $wizard = self::completeWizard();
        $wizard->finish(false, '<?php return [];', 'logs/install.lock');
        $controller = self::controller($root, $wizard);

        $page = $controller->handle('GET', [], [], self::SERVER);
        self::assertSame('done', $page->template);
        self::assertSame(['install.php'], $page->args['deleteFiles']);

        $download = $controller->handle('POST', [], ['action' => 'download_config', 'csrf_token' => self::TOKEN], self::SERVER);
        self::assertSame(Response::DOWNLOAD, $download->kind);
        self::assertSame(LocalConfig::FILENAME, $download->title);

        file_put_contents($root . '/' . LocalConfig::RELATIVE_PATH, '<?php return [];');
        file_put_contents($root . '/setup.php', '<?php');
        $page = $controller->handle('GET', [], [], self::SERVER);
        self::assertTrue($page->args['configPresent']);
        self::assertSame('', $wizard->done()['config_source'] ?? null);
        self::assertSame(['install.php', 'setup.php'], $page->args['deleteFiles']);
    }

    #[Test]
    public function everyPageRendersWithItsIds(): void
    {
        $wizard = self::completeWizard();
        $common = ['csrf' => self::TOKEN, 'errors' => [], 'message' => ''];
        $pages = [
            ['requirements', 1, $common + ['checks' => [['id' => 'php', 'label' => 'PHP', 'ok' => true, 'kind' => 'required', 'detail' => '']], 'allOk' => true], ['pdl_install_checks', 'pdl_install_check_php', 'pdl_install_btn_requirements']],
            ['database', 2, $common + ['old' => [], 'tables' => ['pdl3_settings']], ['pdl_install_form_database', 'pdl_install_db_host', 'pdl_install_db_port', 'pdl_install_db_name', 'pdl_install_db_user', 'pdl_install_db_password', 'pdl_install_existing_tables', 'pdl_install_btn_database', 'pdl_install_btn_back']],
            ['website', 3, $common + ['old' => [], 'notice' => ['type' => 'success', 'message' => 'ok']], ['pdl_install_form_website', 'pdl_install_site_name', 'pdl_install_site_url', 'pdl_install_site_email', 'pdl_install_site_description', 'pdl_install_btn_website', 'pdl_install_notice']],
            ['admin', 4, $common + ['old' => []], ['pdl_install_form_admin', 'pdl_install_admin_nick', 'pdl_install_admin_email', 'pdl_install_admin_password', 'pdl_install_admin_password_confirm', 'pdl_install_btn_admin']],
            ['finish', 5, $common + ['wizard' => $wizard, 'configWritable' => false, 'lockTarget' => null], ['pdl_install_summary', 'pdl_install_summary_db_host', 'pdl_install_summary_db_name', 'pdl_install_summary_db_user', 'pdl_install_summary_db_server', 'pdl_install_summary_site_name', 'pdl_install_summary_site_url', 'pdl_install_summary_site_email', 'pdl_install_summary_site_description', 'pdl_install_summary_admin_nick', 'pdl_install_summary_admin_email', 'pdl_install_edit_database', 'pdl_install_edit_website', 'pdl_install_edit_admin', 'pdl_install_config_mode_manual', 'pdl_install_lock_mode_missing', 'pdl_install_form_finish', 'pdl_install_btn_install']],
            ['done', 0, ['done' => ['config_written' => false, 'config_source' => '<?php', 'lock_file' => '', 'admin_nick' => 'Sabine'], 'configPresent' => false, 'csrf' => self::TOKEN, 'deleteFiles' => ['install.php']], ['pdl_install_success', 'pdl_install_done_admin_nick', 'pdl_install_config_manual', 'pdl_install_btn_download_config', 'pdl_install_config_source', 'pdl_install_config_recheck', 'pdl_install_lock_not_written', 'pdl_install_delete_files', 'pdl_install_link_admin', 'pdl_install_link_frontend']],
            ['locked', 0, ['reason' => 'lockfile', 'lockFile' => 'pdl-inc/install.lock'], ['pdl_install_locked', 'pdl_install_locked_delete_hint', 'pdl_install_link_frontend', 'pdl_install_link_admin']],
        ];

        foreach ($pages as [$template, $step, $args, $ids]) {
            $html = View::capture($template, 'Titel', 'Installation', $step, $wizard->completed(), $args);
            self::assertStringContainsString('id="pdl_install_brand"', $html);
            self::assertStringContainsString('id="pdl_install_page_title"', $html);

            if ($step > 0) {
                self::assertStringContainsString('id="pdl_install_steps"', $html);
                self::assertStringContainsString('id="pdl_install_step_counter"', $html);
                self::assertStringContainsString('id="pdl_install_step_link_' . $step . '"', $html);
            }

            foreach ($ids as $id) {
                self::assertStringContainsString('id="' . $id . '"', $html, $template . ': ' . $id);
            }
        }
    }
}
