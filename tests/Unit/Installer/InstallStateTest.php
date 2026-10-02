<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Installer\InstallState;
use PowerDownload\LocalConfig;

final class InstallStateTest extends InstallerTestCase
{
    /**
     * @return iterable<string, array{bool, bool, string, bool|null, string|null}>
     */
    public static function combinations(): iterable
    {
        $env = LocalConfig::SOURCE_ENVIRONMENT;
        $defaults = LocalConfig::SOURCE_DEFAULTS;
        $file = LocalConfig::SOURCE_FILE;

        yield 'Sperrdatei schlägt alles' => [true, true, $env, true, InstallState::REASON_LOCK_FILE];
        yield 'Sperrdatei allein' => [true, false, $defaults, null, InstallState::REASON_LOCK_FILE];
        yield 'lokale Konfiguration' => [false, true, $file, null, InstallState::REASON_LOCAL_CONFIG];
        yield 'Umgebung, Datenbank eingerichtet' => [false, false, $env, true, InstallState::REASON_DATABASE];
        yield 'Umgebung, Datenbank nicht erreichbar' => [false, false, $env, null, InstallState::REASON_UNREACHABLE];
        yield 'Umgebung, leere Datenbank' => [false, false, $env, false, null];
        yield 'Vorgaben, nichts eingerichtet' => [false, false, $defaults, null, null];
        yield 'Vorgaben, Datenbank wird ignoriert' => [false, false, $defaults, true, null];
    }

    #[Test]
    public function mbstringIsARequiredExtension(): void
    {
        $root = $this->makeRoot();
        $checks = \PowerDownload\Installer\Requirements::check($root, [], '8.4.8', static fn (string $name): bool => $name !== 'mbstring', static fn (string $name): string => '8M');
        $byId = array_column($checks, null, 'id');

        self::assertSame(\PowerDownload\Installer\Requirements::KIND_REQUIRED, $byId['mbstring']['kind']);
        self::assertFalse($byId['mbstring']['ok']);
        self::assertStringContainsString('php.ini', $byId['mbstring']['detail']);
    }

    #[Test]
    #[DataProvider('combinations')]
    public function lockReasonCombinations(bool $lockFile, bool $localConfig, string $source, ?bool $database, ?string $expected): void
    {
        self::assertSame($expected, InstallState::lockReason($lockFile, $localConfig, $source, $database));
    }

    #[Test]
    public function detectLockReasonUsesFilesBeforeTheDatabase(): void
    {
        $root = $this->makeRoot();
        $probed = 0;
        $probe = static function () use (&$probed): ?bool {
            ++$probed;

            return true;
        };

        self::assertNull(InstallState::detectLockReason($root, LocalConfig::SOURCE_DEFAULTS, $probe));
        self::assertSame(0, $probed, 'Ohne Umgebungsvariablen wird die Datenbank nicht befragt.');

        self::assertSame(InstallState::REASON_DATABASE, InstallState::detectLockReason($root, LocalConfig::SOURCE_ENVIRONMENT, $probe));
        self::assertSame(1, $probed);

        file_put_contents($root . '/' . LocalConfig::RELATIVE_PATH, '<?php return [];');
        self::assertSame(InstallState::REASON_LOCAL_CONFIG, InstallState::detectLockReason($root, LocalConfig::SOURCE_ENVIRONMENT, $probe));

        file_put_contents($root . '/logs/install.lock', 'x');
        self::assertSame(InstallState::REASON_LOCK_FILE, InstallState::detectLockReason($root, LocalConfig::SOURCE_ENVIRONMENT, $probe));
        self::assertSame('logs/install.lock', InstallState::existingLockFile($root));
        self::assertSame(1, $probed);
    }

    #[Test]
    public function uploadedFilesLockTheInstaller(): void
    {
        $root = $this->makeRoot();
        $never = static fn (): ?bool => null;
        mkdir($root . '/pdl-files', 0o777, true);
        mkdir($root . '/pdl-gfx/screens', 0o777, true);
        foreach (['pdl-files/.htaccess', 'pdl-files/index.html', 'pdl-gfx/screens/.gitkeep', 'pdl-gfx/screens/.htaccess', 'pdl-gfx/screens/index.html'] as $shipped) {
            file_put_contents($root . '/' . $shipped, 'x');
        }

        self::assertFalse(InstallState::hasExistingUploads($root), 'Lieferumfang allein sperrt nicht.');
        self::assertNull(InstallState::detectLockReason($root, LocalConfig::SOURCE_DEFAULTS, $never));

        mkdir($root . '/pdl-files/4');
        self::assertTrue(InstallState::hasExistingUploads($root));
        self::assertSame(InstallState::REASON_EXISTING_FILES, InstallState::detectLockReason($root, LocalConfig::SOURCE_DEFAULTS, $never));
        self::assertSame(InstallState::REASON_EXISTING_FILES, InstallState::detectLockReason($root, LocalConfig::SOURCE_ENVIRONMENT, static fn (): ?bool => false));
        // Spezifischere Gründe gehen vor.
        self::assertSame(InstallState::REASON_DATABASE, InstallState::detectLockReason($root, LocalConfig::SOURCE_ENVIRONMENT, static fn (): ?bool => true));

        rmdir($root . '/pdl-files/4');
        file_put_contents($root . '/pdl-gfx/screens/release1screen1g.jpg', 'x');
        self::assertSame(InstallState::REASON_EXISTING_FILES, InstallState::detectLockReason($root, LocalConfig::SOURCE_DEFAULTS, $never));
        self::assertSame(InstallState::REASON_EXISTING_FILES, InstallState::lockReason(false, false, LocalConfig::SOURCE_DEFAULTS, null, true));
    }

    #[Test]
    public function lockedPageExplainsExistingFiles(): void
    {
        $render = require self::rootDir() . '/pdl-inc/installer/templates/locked.php';
        ob_start();
        $render(InstallState::REASON_EXISTING_FILES, InstallState::LOCK_FILES[0]);
        $html = (string) ob_get_clean();

        self::assertStringContainsString('data-reason="existing_files"', $html);
        self::assertStringContainsString('Hier scheint also schon eine Installation von PowerDownload zu bestehen.', $html);
        self::assertStringContainsString('update.php', $html);
        self::assertStringContainsString('README', $html);
    }

    #[Test]
    public function setupPageOffersInstallerOnlyWhenItIsNotLocked(): void
    {
        require_once self::rootDir() . '/pdl-inc/pdl_setup_required.inc.php';
        $root = $this->makeRoot();
        // Der Autoloader des Installers wird aus dem Testverzeichnis geladen.
        mkdir($root . '/pdl-inc/installer');
        file_put_contents($root . '/pdl-inc/installer/autoload.php', '<?php require_once ' . var_export(self::rootDir() . '/pdl-inc/installer/autoload.php', true) . ';');

        self::assertNull(pdl_setup_installer_lock_reason('defaults', LocalConfig::SOURCE_DEFAULTS, $root));
        self::assertNull(pdl_setup_installer_lock_reason('tables', LocalConfig::SOURCE_ENVIRONMENT, $root), 'Leere Datenbank: Installer offen.');
        self::assertSame(InstallState::REASON_UNREACHABLE, pdl_setup_installer_lock_reason('connection', LocalConfig::SOURCE_ENVIRONMENT, $root));

        mkdir($root . '/pdl-files/7', 0o777, true);
        self::assertSame(InstallState::REASON_EXISTING_FILES, pdl_setup_installer_lock_reason('defaults', LocalConfig::SOURCE_DEFAULTS, $root));

        file_put_contents($root . '/logs/install.lock', 'x');
        self::assertSame(InstallState::REASON_LOCK_FILE, pdl_setup_installer_lock_reason('defaults', LocalConfig::SOURCE_DEFAULTS, $root));
    }

    #[Test]
    public function lockFileGoesToPdlIncFirstAndFallsBackToLogs(): void
    {
        $root = $this->makeRoot();

        self::assertSame('pdl-inc/install.lock', InstallState::lockTarget($root));
        self::assertSame('pdl-inc/install.lock', InstallState::writeLockFile($root, '2026-10-02 12:00:00'));
        self::assertStringContainsString('PowerDownload installiert am 2026-10-02 12:00:00', (string) file_get_contents($root . '/pdl-inc/install.lock'));

        $other = $this->makeRoot();
        rmdir($other . '/pdl-inc');
        self::assertSame('logs/install.lock', InstallState::lockTarget($other));
        self::assertSame('logs/install.lock', InstallState::writeLockFile($other, '2026-10-02'));

        $none = $this->makeRoot();
        rmdir($none . '/pdl-inc');
        rmdir($none . '/logs');
        self::assertNull(InstallState::lockTarget($none));
        self::assertNull(InstallState::writeLockFile($none, '2026-10-02'));
    }
}
