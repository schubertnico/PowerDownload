<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Installer\Html;
use PowerDownload\Installer\Requirements;
use PowerDownload\Installer\ServerVersion;
use PowerDownload\Installer\Wizard;

final class WizardTest extends InstallerTestCase
{
    #[Test]
    public function stepsUnlockOneAfterAnother(): void
    {
        $wizard = Wizard::fromSession(null);

        self::assertSame(0, $wizard->completed());
        self::assertSame(1, $wizard->allowedStep(5));

        $wizard->completeRequirements();
        self::assertSame(2, $wizard->allowedStep(5));

        $wizard->storeDatabase(['host' => 'h', 'port' => 3306, 'user' => 'u', 'password' => 'p', 'database' => 'd'], 'MySQL 8.0.46');
        $wizard->storeWebsite(['name' => 'n', 'url' => 'https://example.org', 'email' => 'a@example.org', 'description' => '']);
        $wizard->storeAdmin('Sabine', 'admin@example.org', 'hash');

        self::assertSame(4, $wizard->completed());
        self::assertSame(5, $wizard->allowedStep(9));
        self::assertTrue($wizard->canEnter(3));
    }

    #[Test]
    public function sessionRoundTripAndManipulatedData(): void
    {
        $wizard = Wizard::fromSession(null);
        $wizard->completeRequirements();
        $wizard->storeDatabase(['host' => 'h', 'port' => 3306, 'user' => 'u', 'password' => 'p', 'database' => 'd'], 'MySQL 8.0.46');

        $restored = Wizard::fromSession($wizard->toSession());
        self::assertSame($wizard->toSession(), $restored->toSession());

        $broken = $wizard->toSession();
        $broken['database']['port'] = '3306';
        $broken['completed'] = 99;
        self::assertSame(1, Wizard::fromSession($broken)->completed(), 'Ungültige Daten führen zu einem früheren Schritt.');
    }

    #[Test]
    public function finishForgetsCredentials(): void
    {
        $wizard = Wizard::fromSession(null);
        $wizard->completeRequirements();
        $wizard->storeDatabase(['host' => 'h', 'port' => 3306, 'user' => 'u', 'password' => 'geheim', 'database' => 'd'], 'MySQL 8.0.46');
        $wizard->storeWebsite(['name' => 'n', 'url' => 'https://example.org', 'email' => 'a@example.org', 'description' => '']);
        $wizard->storeAdmin('Sabine', 'admin@example.org', 'hash');
        $wizard->finish(true, '<?php geheim', 'pdl-inc/install.lock');

        $session = $wizard->toSession();
        self::assertNull($session['database']);
        self::assertNull($session['admin']);
        self::assertSame(['config_written' => true, 'config_source' => '', 'lock_file' => 'pdl-inc/install.lock', 'admin_nick' => 'Sabine'], $session['done']);
        self::assertStringNotContainsString('geheim', serialize($session));
    }

    #[Test]
    public function suggestionsFromTheRequest(): void
    {
        self::assertSame('https://www.example.org/downloads', Wizard::suggestSiteUrl(['HTTP_HOST' => 'www.example.org', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/downloads/install.php']));
        self::assertSame('http://localhost:8249', Wizard::suggestSiteUrl(['HTTP_HOST' => 'localhost:8249', 'SCRIPT_NAME' => '/install.php']));
        self::assertSame('', Wizard::suggestSiteUrl(['HTTP_HOST' => 'evil.example"><script>', 'SCRIPT_NAME' => '/install.php']));
        self::assertSame('noreply@example.org', Wizard::suggestSender('https://www.example.org/downloads'));
        self::assertSame('', Wizard::suggestSender('http://localhost:8249'));
    }

    #[Test]
    public function serverVersions(): void
    {
        self::assertTrue(ServerVersion::parse('8.0.46')?->isSupported());
        self::assertFalse(ServerVersion::parse('5.7.44-log')?->isSupported());
        self::assertTrue(ServerVersion::parse('10.6.18-MariaDB')?->isSupported());
        self::assertFalse(ServerVersion::parse('5.5.5-10.5.23-MariaDB-log')?->isSupported());
        self::assertSame('MariaDB 10.11.15', ServerVersion::parse('10.11.15-MariaDB-ubu2204')?->label());
        self::assertNull(ServerVersion::parse('unbekannt'));
    }

    #[Test]
    public function requirementsBlockOnlyOnRequiredItems(): void
    {
        $root = $this->makeRoot();
        $checks = Requirements::check($root, [], '8.4.8', static fn (string $name): bool => $name === 'mysqli', static fn (string $name): string => '8M');
        $byId = array_column($checks, null, 'id');

        self::assertSame(['php', 'mysqli', 'mbstring', 'schema', 'logs', 'pdlinc', 'files', 'screens', 'smilies', 'gd', 'ftp', 'upload', 'dbserver', 'https'], array_keys($byId));
        self::assertFalse($byId['schema']['ok'], 'Im Testverzeichnis fehlt die Schemadatei.');
        self::assertFalse(Requirements::allRequiredMet($checks));
        self::assertSame(Requirements::KIND_OPTIONAL, $byId['gd']['kind']);
        self::assertStringContainsString('upload_max_filesize: 8M', $byId['upload']['detail']);

        mkdir($root . '/pdl-inc/x', 0o777, true);
        file_put_contents($root . '/pdl-inc/pdl3_schema.sql', 'SET NAMES utf8mb4;');
        $checks = Requirements::check($root, ['HTTPS' => 'on'], '8.4.0', static fn (string $name): bool => true, static fn (string $name): string|false => false);
        self::assertTrue(Requirements::allRequiredMet($checks));
        self::assertFalse(Requirements::check($root, [], '8.3.9', static fn (string $name): bool => true, static fn (string $name): string|false => false)[0]['ok']);
    }

    #[Test]
    public function htmlFieldsCarryThePrefixAndAreEscaped(): void
    {
        $html = Html::input('db_host', 'Datenbankserver', '"><script>', ['db_host' => 'Fehler <b>'], ['required' => true], 'Hilfe');

        self::assertStringContainsString('id="pdl_install_db_host" name="db_host"', $html);
        self::assertStringContainsString('id="pdl_install_db_host_error"', $html);
        self::assertStringContainsString('id="pdl_install_db_host_help"', $html);
        self::assertStringContainsString('aria-invalid="true"', $html);
        self::assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
        self::assertStringContainsString('Fehler &lt;b&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertSame('', Html::alert('', 'danger', 'x'));
    }
}
