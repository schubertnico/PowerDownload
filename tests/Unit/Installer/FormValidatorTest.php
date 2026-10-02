<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Installer\FormValidator;

final class FormValidatorTest extends InstallerTestCase
{
    /**
     * @return array<string, string>
     */
    private static function database(array $override = []): array
    {
        return $override + [
            'db_host' => 'sql.example.org',
            'db_port' => '3306',
            'db_name' => 'downloads_db',
            'db_user' => 'downloads_user',
            'db_password' => 'Lichtblick-DB26',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function admin(array $override = []): array
    {
        return $override + [
            'admin_nick' => 'Sabine',
            'admin_email' => 'admin@example.org',
            'admin_password' => 'Lichtblick2026',
            'admin_password_confirm' => 'Lichtblick2026',
        ];
    }

    #[Test]
    public function validDatabaseInputIsAccepted(): void
    {
        $result = FormValidator::database(self::database(['db_password' => ' mit Leerzeichen ']));

        self::assertSame([], $result['errors']);
        self::assertSame(['host' => 'sql.example.org', 'port' => 3306, 'user' => 'downloads_user', 'password' => ' mit Leerzeichen ', 'database' => 'downloads_db'], $result['values']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidHosts(): iterable
    {
        yield 'mit Port' => ['localhost:3306'];
        yield 'persistent' => ['p:localhost'];
        yield 'Leerzeichen' => ['sql example'];
        yield 'Schrägstrich' => ['sql.example.org/db'];
    }

    #[Test]
    #[DataProvider('invalidHosts')]
    public function hostWithPortOrPrefixIsRejected(string $host): void
    {
        $result = FormValidator::database(self::database(['db_host' => $host]));

        self::assertArrayHasKey('db_host', $result['errors']);
    }

    #[Test]
    public function ipv6AndIpv4HostsAreAccepted(): void
    {
        self::assertTrue(FormValidator::isHostname('::1'));
        self::assertTrue(FormValidator::isHostname('127.0.0.1'));
        self::assertTrue(FormValidator::isHostname('db'));
    }

    #[Test]
    public function portLimits(): void
    {
        self::assertArrayHasKey('db_port', FormValidator::database(self::database(['db_port' => '0']))['errors']);
        self::assertArrayHasKey('db_port', FormValidator::database(self::database(['db_port' => '65536']))['errors']);
        self::assertArrayHasKey('db_port', FormValidator::database(self::database(['db_port' => '33a']))['errors']);
        self::assertSame(65535, FormValidator::database(self::database(['db_port' => '65535']))['values']['port']);
        self::assertSame(3306, FormValidator::database(self::database(['db_port' => '']))['values']['port']);
    }

    #[Test]
    public function databaseNameAndUserRules(): void
    {
        self::assertArrayHasKey('db_name', FormValidator::database(self::database(['db_name' => 'mein db']))['errors']);
        self::assertArrayHasKey('db_name', FormValidator::database(self::database(['db_name' => str_repeat('a', 65)]))['errors']);
        self::assertArrayNotHasKey('db_name', FormValidator::database(self::database(['db_name' => 'web123_db-1$']))['errors']);
        self::assertArrayHasKey('db_user', FormValidator::database(self::database(['db_user' => "a\nb"]))['errors']);
        self::assertArrayHasKey('db_user', FormValidator::database(self::database(['db_user' => '']))['errors']);
        self::assertArrayHasKey('db_password', FormValidator::database(self::database(['db_password' => "a\0b"]))['errors']);
    }

    #[Test]
    public function validAdminIsAccepted(): void
    {
        $result = FormValidator::admin(self::admin(['admin_nick' => 'Jürgen_Müller-2']));

        self::assertSame([], $result['errors']);
        self::assertSame('Jürgen_Müller-2', $result['values']['nick']);
    }

    #[Test]
    public function nickRules(): void
    {
        self::assertArrayHasKey('admin_nick', FormValidator::admin(self::admin(['admin_nick' => 'ab']))['errors']);
        self::assertArrayHasKey('admin_nick', FormValidator::admin(self::admin(['admin_nick' => str_repeat('a', 31)]))['errors']);
        self::assertArrayHasKey('admin_nick', FormValidator::admin(self::admin(['admin_nick' => 'Sabine K.!']))['errors']);
        self::assertArrayHasKey('admin_nick', FormValidator::admin(self::admin(['admin_nick' => '']))['errors']);
        self::assertTrue(FormValidator::isNickname('Größe.Ölfaß'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rejectedPasswords(): iterable
    {
        yield 'admin123' => ['admin123', 'Sabine'];
        yield 'ADMIN123 in Großbuchstaben' => ['ADMIN123', 'Sabine'];
        yield 'passwort1' => ['Passwort1', 'Sabine'];
        yield 'password1' => ['password1', 'Sabine'];
        yield 'gleich Nickname' => ['sabine2026', 'Sabine2026'];
        yield 'gleich Nickname mit Umlaut' => ['jürgen2026', 'JÜRGEN2026'];
        yield 'zu kurz' => ['abc1234', 'Sabine'];
        yield 'ohne Ziffer' => ['Lichtblick', 'Sabine'];
        yield 'ohne Buchstaben' => ['12345678', 'Sabine'];
        yield '73 Byte' => [str_repeat('a', 72) . '1', 'Sabine'];
        yield 'leer' => ['', 'Sabine'];
        yield 'ungültiges UTF-8' => ["abcdefg1\xff", 'Sabine'];
    }

    #[Test]
    #[DataProvider('rejectedPasswords')]
    public function weakPasswordsAreRejected(string $password, string $nick): void
    {
        self::assertNotNull(FormValidator::passwordError($password, $nick));
    }

    #[Test]
    public function passwordAtTheLimitsIsAccepted(): void
    {
        self::assertNull(FormValidator::passwordError(str_repeat('a', 71) . '1', 'Sabine'));
        self::assertNotNull(FormValidator::passwordError('Ölfaß12', 'Sabine'), 'Sieben Zeichen sind zu kurz.');
        self::assertNull(FormValidator::passwordError('Ölfässer1', 'Sabine'));
    }

    #[Test]
    public function umlautsCountAsOneCharacter(): void
    {
        self::assertSame(8, FormValidator::charCount('ÄÖÜäöüß1'));
        self::assertNull(FormValidator::charCount("\xff"));
        self::assertNull(FormValidator::passwordError('ÄÖÜäöüß1', 'Sabine'));
    }

    #[Test]
    public function confirmationMustMatch(): void
    {
        $result = FormValidator::admin(self::admin(['admin_password_confirm' => 'Lichtblick2027']));

        self::assertSame(['admin_password_confirm'], array_keys($result['errors']));
    }

    #[Test]
    public function adminEmailIsValidated(): void
    {
        self::assertArrayHasKey('admin_email', FormValidator::admin(self::admin(['admin_email' => 'keine-adresse']))['errors']);
        self::assertArrayHasKey('admin_email', FormValidator::admin(self::admin(['admin_email' => str_repeat('a', 120) . '@example.org']))['errors']);
    }

    #[Test]
    public function websiteInput(): void
    {
        $ok = FormValidator::website([
            'site_name' => 'Downloads Fotoclub Lichtblick',
            'site_url' => 'https://www.example.org/downloads/',
            'site_email' => 'downloads@example.org',
            'site_description' => '',
        ]);

        self::assertSame([], $ok['errors']);
        self::assertSame('https://www.example.org/downloads', $ok['values']['url'], 'Der Schrägstrich am Ende wird entfernt.');
        self::assertSame('', $ok['values']['description']);

        $bad = FormValidator::website([
            'site_name' => "Name\r\nBcc: x@example.org",
            'site_url' => 'ftp://example.org',
            'site_email' => 'x',
            'site_description' => str_repeat('a', 301),
        ]);

        self::assertSame(['site_name', 'site_url', 'site_email', 'site_description'], array_keys($bad['errors']));
    }

    #[Test]
    public function urlWithQuotesIsRejected(): void
    {
        self::assertFalse(FormValidator::isWebUrl('https://example.org/"onmouseover=x'));
        self::assertTrue(FormValidator::isWebUrl('http://localhost:8249'));
    }
}
