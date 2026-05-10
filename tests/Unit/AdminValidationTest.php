<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PowerDownload\Tests\Support\MockDbHandler;

/**
 * Tests für pdl-inc/pdl_admin_validation.inc.php
 */
class AdminValidationTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_admin_validation.inc.php';
    }

    // ===== pdl_validate_required =====

    #[Test]
    public function validateRequiredReturnsEmptyArrayWhenAllFieldsPresent(): void
    {
        $errors = pdl_validate_required(['name' => 'foo', 'email' => 'bar@x.de'], ['name', 'email']);
        $this->assertSame([], $errors);
    }

    #[Test]
    public function validateRequiredReportsEmptyString(): void
    {
        $errors = pdl_validate_required(['name' => '', 'foo' => 'bar'], ['name', 'foo']);
        $this->assertArrayHasKey('name', $errors);
        $this->assertSame('Pflichtfeld', $errors['name']);
        $this->assertArrayNotHasKey('foo', $errors);
    }

    #[Test]
    public function validateRequiredReportsMissingKey(): void
    {
        $errors = pdl_validate_required([], ['name']);
        $this->assertArrayHasKey('name', $errors);
    }

    #[Test]
    public function validateRequiredTreatsWhitespaceAsEmpty(): void
    {
        $errors = pdl_validate_required(['name' => "   \t  "], ['name']);
        $this->assertArrayHasKey('name', $errors);
    }

    // ===== pdl_validate_email_optional =====

    #[Test]
    public function emailOptionalAllowsEmpty(): void
    {
        $this->assertNull(pdl_validate_email_optional(''));
        $this->assertNull(pdl_validate_email_optional('   '));
    }

    #[Test]
    public function emailOptionalAcceptsValid(): void
    {
        $this->assertNull(pdl_validate_email_optional('foo@example.com'));
        $this->assertNull(pdl_validate_email_optional('a.b+c@sub.example.de'));
    }

    #[Test]
    #[DataProvider('invalidEmailProvider')]
    public function emailOptionalRejectsInvalid(string $value): void
    {
        $this->assertNotNull(pdl_validate_email_optional($value));
    }

    public static function invalidEmailProvider(): array
    {
        return [
            ['NOT_AN_EMAIL'],
            ['foo@'],
            ['@example.com'],
            ['plain text'],
        ];
    }

    // ===== pdl_validate_url_optional =====

    #[Test]
    public function urlOptionalAllowsEmpty(): void
    {
        $this->assertNull(pdl_validate_url_optional(''));
    }

    #[Test]
    public function urlOptionalAcceptsHttpAndHttps(): void
    {
        $this->assertNull(pdl_validate_url_optional('http://example.com'));
        $this->assertNull(pdl_validate_url_optional('https://example.com/path?a=b'));
    }

    #[Test]
    public function urlOptionalRejectsJavascriptScheme(): void
    {
        $msg = pdl_validate_url_optional('javascript:alert(1)');
        $this->assertNotNull($msg);
    }

    #[Test]
    public function urlOptionalRejectsFtpScheme(): void
    {
        $msg = pdl_validate_url_optional('ftp://example.com');
        $this->assertNotNull($msg);
    }

    #[Test]
    public function urlOptionalRejectsMalformed(): void
    {
        $this->assertNotNull(pdl_validate_url_optional('not a url'));
    }

    // ===== pdl_validate_int_min =====

    #[Test]
    public function intMinAcceptsValueAtBoundary(): void
    {
        $this->assertNull(pdl_validate_int_min(0, 0));
        $this->assertNull(pdl_validate_int_min(5, 5));
    }

    #[Test]
    public function intMinRejectsBelowBoundary(): void
    {
        $this->assertNotNull(pdl_validate_int_min(-1, 0));
        $this->assertNotNull(pdl_validate_int_min(4, 5));
    }

    // ===== pdl_ordner_exists =====

    #[Test]
    public function ordnerExistsAlwaysTrueForZero(): void
    {
        $db = new MockDbHandler();
        $this->assertTrue(pdl_ordner_exists($db, ['ordner' => 'pdl3_ordner'], 0));
        // 0 queries should be needed for root
        $this->assertSame(0, $db->querys);
    }

    #[Test]
    public function ordnerExistsFalseForNegative(): void
    {
        $db = new MockDbHandler();
        $this->assertFalse(pdl_ordner_exists($db, ['ordner' => 'pdl3_ordner'], -5));
    }

    #[Test]
    public function ordnerExistsTrueWhenRowFound(): void
    {
        $db = new MockDbHandler();
        $db->addResult([['ordner_id' => 7]]);
        $this->assertTrue(pdl_ordner_exists($db, ['ordner' => 'pdl3_ordner'], 7));
    }

    #[Test]
    public function ordnerExistsFalseWhenNoRow(): void
    {
        $db = new MockDbHandler();
        $db->addResult([]); // empty result
        $this->assertFalse(pdl_ordner_exists($db, ['ordner' => 'pdl3_ordner'], 99999));
    }

    // ===== pdl_release_exists =====

    #[Test]
    public function releaseExistsFalseForZero(): void
    {
        $db = new MockDbHandler();
        $this->assertFalse(pdl_release_exists($db, ['release' => 'pdl3_release'], 0));
    }

    #[Test]
    public function releaseExistsTrueWhenRowFound(): void
    {
        $db = new MockDbHandler();
        $db->addResult([['release_id' => 1]]);
        $this->assertTrue(pdl_release_exists($db, ['release' => 'pdl3_release'], 1));
    }

    #[Test]
    public function releaseExistsFalseWhenNoRow(): void
    {
        $db = new MockDbHandler();
        $db->addResult([]);
        $this->assertFalse(pdl_release_exists($db, ['release' => 'pdl3_release'], 5));
    }

    // ===== pdl_validate_ordner_parent =====

    #[Test]
    public function ordnerParentRejectsSelfReference(): void
    {
        $this->assertNotNull(pdl_validate_ordner_parent(5, 5));
    }

    #[Test]
    public function ordnerParentAllowsDifferent(): void
    {
        $this->assertNull(pdl_validate_ordner_parent(5, 7));
    }

    #[Test]
    public function ordnerParentAllowsRoot(): void
    {
        $this->assertNull(pdl_validate_ordner_parent(5, 0));
    }

    // ===== pdl_validate_screen_upload =====

    #[Test]
    public function screenUploadFailsWithoutError(): void
    {
        $this->assertNotNull(pdl_validate_screen_upload([]));
    }

    #[Test]
    public function screenUploadFailsWithIniError(): void
    {
        $this->assertNotNull(pdl_validate_screen_upload(['error' => UPLOAD_ERR_INI_SIZE, 'tmp_name' => '/tmp/foo']));
    }

    #[Test]
    public function screenUploadFailsWithMissingTmp(): void
    {
        $this->assertNotNull(pdl_validate_screen_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => '']));
    }

    #[Test]
    public function screenUploadDetectsNonJpegMime(): void
    {
        // create a PNG-temp file
        $tmp = tempnam(sys_get_temp_dir(), 'pdltest');
        // Minimal PNG header
        file_put_contents($tmp, hex2bin('89504E470D0A1A0A0000000D49484452000000010000000108020000') . str_repeat("\0", 100));
        $err = pdl_validate_screen_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp]);
        $this->assertNotNull($err);
        unlink($tmp);
    }

    #[Test]
    public function screenUploadAcceptsJpeg(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD not available');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pdltest');
        $im = imagecreatetruecolor(10, 10);
        imagejpeg($im, $tmp);
        imagedestroy($im);
        $err = pdl_validate_screen_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp]);
        $this->assertNull($err, 'Erwartet null, bekommen: ' . (string) $err);
        unlink($tmp);
    }

    // ===== pdl_validate_autor_type =====

    #[Test]
    public function autorTypeAcceptsKnownValues(): void
    {
        $this->assertTrue(pdl_validate_autor_type(-1));
        $this->assertTrue(pdl_validate_autor_type(0));
        $this->assertTrue(pdl_validate_autor_type(1));
    }

    #[Test]
    public function autorTypeRejectsUnknownValues(): void
    {
        $this->assertFalse(pdl_validate_autor_type(2));
        $this->assertFalse(pdl_validate_autor_type(99));
        $this->assertFalse(pdl_validate_autor_type(-99));
    }

    // ===== pdl_validate_release_input =====

    #[Test]
    public function releaseInputErrorsOnEmptyName(): void
    {
        $db = new MockDbHandler();
        $res = pdl_validate_release_input(
            ['name' => '', 'ordner_id' => 0, 'autor_type' => -1],
            $db,
            ['ordner' => 'pdl3_ordner']
        );
        $this->assertArrayHasKey('name', $res['errors']);
    }

    #[Test]
    public function releaseInputErrorsOnInvalidOrdner(): void
    {
        $db = new MockDbHandler();
        $db->addResult([]); // ordner_exists check liefert nichts
        $res = pdl_validate_release_input(
            ['name' => 'Foo', 'ordner_id' => 99999, 'autor_type' => -1],
            $db,
            ['ordner' => 'pdl3_ordner']
        );
        $this->assertArrayHasKey('ordner_id', $res['errors']);
    }

    #[Test]
    public function releaseInputErrorsOnInvalidEmailWhenAutorIsManual(): void
    {
        $db = new MockDbHandler();
        $res = pdl_validate_release_input(
            ['name' => 'Foo', 'ordner_id' => 0, 'autor_type' => 0, 'autor_email' => 'NOT_EMAIL', 'autor_homepage' => ''],
            $db,
            ['ordner' => 'pdl3_ordner']
        );
        $this->assertArrayHasKey('autor_email', $res['errors']);
    }

    #[Test]
    public function releaseInputErrorsOnDangerousUrlWhenAutorIsManual(): void
    {
        $db = new MockDbHandler();
        $res = pdl_validate_release_input(
            ['name' => 'Foo', 'ordner_id' => 0, 'autor_type' => 0, 'autor_email' => '', 'autor_homepage' => 'javascript:alert(1)'],
            $db,
            ['ordner' => 'pdl3_ordner']
        );
        $this->assertArrayHasKey('autor_homepage', $res['errors']);
    }

    #[Test]
    public function releaseInputPassesValidInput(): void
    {
        $db = new MockDbHandler();
        $res = pdl_validate_release_input(
            ['name' => 'Foo', 'ordner_id' => 0, 'autor_type' => -1],
            $db,
            ['ordner' => 'pdl3_ordner']
        );
        $this->assertSame([], $res['errors']);
        $this->assertSame(-1, $res['autor_type']);
    }

    // ===== pdl_admin_field_label =====

    #[Test]
    public function fieldLabelReturnsGermanForKnownFields(): void
    {
        $this->assertSame('Name', pdl_admin_field_label('name'));
        $this->assertSame('Ordner', pdl_admin_field_label('ordner_id'));
        $this->assertSame('URL zur Datei', pdl_admin_field_label('url'));
        $this->assertSame('Dateigröße', pdl_admin_field_label('size'));
        $this->assertSame('Spiegel-Server', pdl_admin_field_label('mirror'));
        $this->assertSame('E-Mail des Autors', pdl_admin_field_label('autor_email'));
        $this->assertSame('Sicherheits-Token', pdl_admin_field_label('_csrf'));
    }

    #[Test]
    public function fieldLabelFallsBackForUnknownFields(): void
    {
        // Unbekannte Felder werden so dargestellt, dass sie für Nutzer
        // möglichst lesbar sind (Underscore → Leerzeichen, erste Buchstabe
        // groß).
        $this->assertSame('Foo bar', pdl_admin_field_label('foo_bar'));
        $this->assertSame('Etwas', pdl_admin_field_label('etwas'));
    }

    // ===== pdl_admin_render_errors =====

    #[Test]
    public function renderErrorsReturnsEmptyStringForNoErrors(): void
    {
        $this->assertSame('', pdl_admin_render_errors([]));
    }

    #[Test]
    public function renderErrorsContainsGermanLabelsAndEscapesValues(): void
    {
        $html = pdl_admin_render_errors([
            'name' => 'Pflichtfeld',
            'autor_email' => 'Keine gültige E-Mail-Adresse.',
        ]);
        $this->assertStringContainsString('<strong>Bitte korrigieren Sie folgende Eingaben:</strong>', $html);
        $this->assertStringContainsString('<li>Name: Pflichtfeld</li>', $html);
        $this->assertStringContainsString('<li>E-Mail des Autors: Keine gültige E-Mail-Adresse.</li>', $html);
    }

    #[Test]
    public function renderErrorsEscapesHtmlInUserSuppliedValues(): void
    {
        $html = pdl_admin_render_errors([
            'name' => '<script>alert(1)</script>',
        ]);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // ===== pdl_validate_file_upload =====

    #[Test]
    public function fileUploadRejectsWhenNoFileGiven(): void
    {
        $err = pdl_validate_file_upload(['error' => UPLOAD_ERR_NO_FILE]);
        $this->assertNotNull($err);
        $this->assertStringContainsString('auswählen', $err);
    }

    #[Test]
    public function fileUploadRejectsIniSizeError(): void
    {
        $err = pdl_validate_file_upload(['error' => UPLOAD_ERR_INI_SIZE, 'tmp_name' => '/tmp/foo', 'size' => 1, 'name' => 'x.zip']);
        $this->assertNotNull($err);
        $this->assertStringContainsString('zu groß', $err);
    }

    #[Test]
    public function fileUploadRejectsEmptyFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdlupl');
        file_put_contents($tmp, '');
        $err = pdl_validate_file_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp, 'size' => 0, 'name' => 'x.zip']);
        $this->assertNotNull($err);
        $this->assertStringContainsString('leer', $err);
        unlink($tmp);
    }

    #[Test]
    public function fileUploadRejectsTooLargeFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdlupl');
        file_put_contents($tmp, 'X');
        $err = pdl_validate_file_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp, 'size' => 999999, 'name' => 'x.zip'], 1024);
        $this->assertNotNull($err);
        $this->assertStringContainsString('Maximum', $err);
        unlink($tmp);
    }

    #[Test]
    public function fileUploadRejectsDangerousExtensions(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdlupl');
        file_put_contents($tmp, 'X');
        foreach (['shell.php', 'web.phtml', 'sneaky.phar', 'evil.PhP', 'cgi.cgi', 'r.sh', 'a.bat'] as $bad) {
            $err = pdl_validate_file_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp, 'size' => 10, 'name' => $bad]);
            $this->assertNotNull($err, 'Erwartet Ablehnung für: ' . $bad);
        }
        unlink($tmp);
    }

    #[Test]
    public function fileUploadRejectsPathTraversalAttempts(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdlupl');
        file_put_contents($tmp, 'X');
        foreach (['../etc/passwd', '..\\windows\\system.ini', "x\0.zip", 'a/b.zip'] as $bad) {
            $err = pdl_validate_file_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp, 'size' => 10, 'name' => $bad]);
            $this->assertNotNull($err, 'Erwartet Ablehnung für: ' . $bad);
        }
        unlink($tmp);
    }

    #[Test]
    public function fileUploadRejectsMissingExtension(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdlupl');
        file_put_contents($tmp, 'X');
        $err = pdl_validate_file_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp, 'size' => 10, 'name' => 'README']);
        $this->assertNotNull($err);
        unlink($tmp);
    }

    #[Test]
    public function fileUploadAcceptsRegularArchive(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdlupl');
        file_put_contents($tmp, 'PK' . str_repeat('X', 40));
        $err = pdl_validate_file_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp, 'size' => 42, 'name' => 'release-1.0.zip']);
        $this->assertNull($err, 'Sollte gültig sein, war: ' . (string) $err);
        unlink($tmp);
    }

    // ===== pdl_sanitize_upload_filename =====

    #[Test]
    public function sanitizeFilenameKeepsSafeAsciiAndReplacesRest(): void
    {
        $this->assertSame('release-1.0.zip', pdl_sanitize_upload_filename('release-1.0.zip'));
        $this->assertSame('Setup_Datei.exe', pdl_sanitize_upload_filename('Setup Datei.exe'));
        // Umlaute werden zu Unterstrichen, doppelte Unterstriche werden zusammengezogen
        $this->assertSame('Gr_e.txt', pdl_sanitize_upload_filename('Grüße.txt'));
        // Pfad-Bestandteile werden via basename entfernt
        $this->assertSame('cleanname.zip', pdl_sanitize_upload_filename('/var/etc/cleanname.zip'));
    }

    // ===== pdl_format_bytes / pdl_ini_size_in_bytes =====

    #[Test]
    public function formatBytesProducesGermanFriendlyOutput(): void
    {
        $this->assertSame('500 B', pdl_format_bytes(500));
        $this->assertSame('10 KB', pdl_format_bytes(10240));
        $this->assertSame('1 MB', pdl_format_bytes(1024 * 1024));
        $this->assertSame('2 GB', pdl_format_bytes(2 * 1024 * 1024 * 1024));
    }

    #[Test]
    public function iniSizeInBytesParsesPhpIniNotation(): void
    {
        $this->assertSame(8 * 1024 * 1024, pdl_ini_size_in_bytes('8M'));
        $this->assertSame(2 * 1024 * 1024 * 1024, pdl_ini_size_in_bytes('2G'));
        $this->assertSame(512 * 1024, pdl_ini_size_in_bytes('512K'));
        $this->assertSame(123, pdl_ini_size_in_bytes('123'));
        $this->assertSame(0, pdl_ini_size_in_bytes(''));
        $this->assertSame(0, pdl_ini_size_in_bytes('-1M'));
    }
}
