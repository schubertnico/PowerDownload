<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Tests\Support\MockDbHandler;
use pdl_db_class;

/**
 * Tests für die Admin-Helfer aus 3.6.0: Ordner-Kreise, Größeneingabe,
 * lokale Dateien, Screenshot-Formate, Kommentar-Vermerk und SQL-Fehlerprotokoll.
 */
class AdminInhalteTest extends TestCase
{
    private string $tmpBase = '';

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_admin_validation.inc.php';
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_db_class_mysql.inc.php';
    }

    protected function tearDown(): void
    {
        if ($this->tmpBase !== '' && is_dir($this->tmpBase)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tmpBase, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
            }
            rmdir($this->tmpBase);
        }
    }

    // ===== Ordner-Kreise (A08) =====

    #[Test]
    public function ordnerNoCycleRejectsSelf(): void
    {
        $db = new MockDbHandler();
        $this->assertNotNull(pdl_validate_ordner_no_cycle($db, ['ordner' => 'pdl3_ordner'], 4, 4));
    }

    #[Test]
    public function ordnerNoCycleRejectsOwnChild(): void
    {
        // Ordner 4 soll unter 5 – und 5 liegt unter 4.
        $db = new MockDbHandler();
        $db->addResult([['sordner_id' => 4]]);
        $this->assertNotNull(pdl_validate_ordner_no_cycle($db, ['ordner' => 'pdl3_ordner'], 4, 5));
    }

    #[Test]
    public function ordnerNoCycleRejectsGrandchild(): void
    {
        // 4 → unter 6; 6 liegt unter 5, 5 unter 4.
        $db = new MockDbHandler();
        $db->addResult([['sordner_id' => 5]])->addResult([['sordner_id' => 4]]);
        $this->assertNotNull(pdl_validate_ordner_no_cycle($db, ['ordner' => 'pdl3_ordner'], 4, 6));
    }

    #[Test]
    public function ordnerNoCycleAllowsOtherBranch(): void
    {
        $db = new MockDbHandler();
        $db->addResult([['sordner_id' => 1]])->addResult([['sordner_id' => 0]]);
        $this->assertNull(pdl_validate_ordner_no_cycle($db, ['ordner' => 'pdl3_ordner'], 4, 2));
    }

    #[Test]
    public function ordnerNoCycleAllowsIndex(): void
    {
        $db = new MockDbHandler();
        $this->assertNull(pdl_validate_ordner_no_cycle($db, ['ordner' => 'pdl3_ordner'], 4, 0));
    }

    #[Test]
    public function ordnerNoCycleStopsAtExistingCircle(): void
    {
        // Ziel 7 hängt in einem vorhandenen Kreis 7 → 8 → 7: Fehlermeldung statt Endlosschleife.
        $db = new MockDbHandler();
        $db->addResult([['sordner_id' => 8]])->addResult([['sordner_id' => 7]]);
        $this->assertNotNull(pdl_validate_ordner_no_cycle($db, ['ordner' => 'pdl3_ordner'], 4, 7));
    }

    // ===== Größeneingabe (A33) =====

    #[Test]
    public function parseSizeInputHandlesUnitsAndComma(): void
    {
        $this->assertSame(2621440, pdl_parse_size_input('2,5', 'MB'));
        $this->assertSame(2621440, pdl_parse_size_input('2.5', 'mb'));
        $this->assertSame(1264128, pdl_parse_size_input('1.234,5', 'KB'));
        $this->assertSame(3 * 1024 ** 3, pdl_parse_size_input('3', 'GB'));
        $this->assertSame(500, pdl_parse_size_input('500', 'B'));
        $this->assertSame(0, pdl_parse_size_input('', 'MB'));
    }

    #[Test]
    public function parseSizeInputRejectsInvalidValues(): void
    {
        $this->assertNull(pdl_parse_size_input('abc', 'MB'));
        $this->assertNull(pdl_parse_size_input('-1', 'MB'));
        $this->assertNull(pdl_parse_size_input('10', 'XB'));
    }

    #[Test]
    public function formatSizeInputPicksLargestUnit(): void
    {
        $this->assertSame(['value' => '1,5', 'unit' => 'MB'], pdl_format_size_input(1572864));
        $this->assertSame(['value' => '500', 'unit' => 'B'], pdl_format_size_input(500));
        $this->assertSame(['value' => '', 'unit' => 'MB'], pdl_format_size_input(0));
        $this->assertSame(['value' => '1,18', 'unit' => 'MB'], pdl_format_size_input(1234567));
    }

    // ===== Datei-Adressen und lokale Dateien (A23, A25) =====

    #[Test]
    public function fileUrlAcceptsHttpAndLocalPaths(): void
    {
        $this->assertNull(pdl_validate_file_url('https://example.org/datei.pdf'));
        $this->assertNull(pdl_validate_file_url('pdl-files/3/satzung.pdf'));
        $this->assertNotNull(pdl_validate_file_url(''));
        $this->assertNotNull(pdl_validate_file_url('pdl-files/../setup.php'));
        $this->assertNotNull(pdl_validate_file_url('javascript:alert(1)'));
        $this->assertNotNull(pdl_validate_file_url('https://example.org/' . str_repeat('a', 300)));
    }

    #[Test]
    public function localFilePathResolvesOnlyFilesUnderPdlFiles(): void
    {
        $this->tmpBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdl_inhalte_' . bin2hex(random_bytes(4));
        mkdir($this->tmpBase . '/pdl-files/3', 0777, true);
        file_put_contents($this->tmpBase . '/pdl-files/3/satzung 2026.pdf', 'x');
        file_put_contents($this->tmpBase . '/setup.php', 'x');

        $expected = realpath($this->tmpBase . '/pdl-files/3/satzung 2026.pdf');
        $this->assertSame($expected, pdl_local_file_path('pdl-files/3/satzung%202026.pdf', $this->tmpBase));
        $this->assertSame($expected, pdl_local_file_path('http://localhost:8246/pdl-files/3/satzung%202026.pdf', $this->tmpBase, 'localhost:8246'));
        $this->assertSame($expected, pdl_local_file_path('http://localhost:8246/unterordner/pdl-files/3/satzung%202026.pdf', $this->tmpBase, 'localhost:8246'));
        $this->assertNull(pdl_local_file_path('https://fremd.example/pdl-files/3/satzung%202026.pdf', $this->tmpBase, 'localhost:8246'));
        $this->assertNull(pdl_local_file_path('pdl-files/../setup.php', $this->tmpBase));
        $this->assertNull(pdl_local_file_path('setup.php', $this->tmpBase));
        $this->assertNull(pdl_local_file_path('pdl-files/3/fehlt.pdf', $this->tmpBase));
    }

    // ===== Screenshots (A02) =====

    #[Test]
    public function screenUploadAcceptsPng(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD not available');
        }
        $tmp = (string) tempnam(sys_get_temp_dir(), 'pdltest');
        $im = imagecreatetruecolor(12, 8);
        imagepng($im, $tmp);
        imagedestroy($im);
        $this->assertNull(pdl_validate_screen_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp]));
        unlink($tmp);
    }

    #[Test]
    public function screenUploadRejectsUnreadableImage(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'pdltest');
        file_put_contents($tmp, "\xFF\xD8\xFF" . str_repeat("\0", 64));
        $this->assertNotNull(pdl_validate_screen_upload(['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp]));
        unlink($tmp);
    }

    #[Test]
    public function screenUploadReportsMissingFile(): void
    {
        $err = pdl_validate_screen_upload(['error' => UPLOAD_ERR_NO_FILE, 'tmp_name' => '']);
        $this->assertNotNull($err);
        $this->assertStringContainsString('Bilddatei', (string) $err);
    }

    // ===== Kommentar-Vermerk (A13) =====

    #[Test]
    public function commentEditNoteIsReplacedNotStacked(): void
    {
        $text = "Pfad C:\\Programme\\PowerDownload – äöü ß.\n\nEditiert von admin am 01.10.2026\n\nEditiert von admin am 02.10.2026";
        $this->assertSame("Pfad C:\\Programme\\PowerDownload – äöü ß.", pdl_comment_strip_edit_note($text));
        $this->assertSame(
            "Pfad C:\\Programme\\PowerDownload – äöü ß.\n\nBearbeitet von Redaktion am 03.10.2026",
            pdl_comment_with_edit_note($text, 'Redaktion', '03.10.2026')
        );
    }

    #[Test]
    public function commentWithoutNoteStaysUnchanged(): void
    {
        $this->assertSame('Bearbeitet von mir, danke!', pdl_comment_strip_edit_note('Bearbeitet von mir, danke!'));
    }

    #[Test]
    public function maxLengthCountsCharactersNotBytes(): void
    {
        $this->assertNull(pdl_validate_max_length(str_repeat('ä', 128), 128));
        $this->assertNotNull(pdl_validate_max_length(str_repeat('ä', 129), 128));
    }

    // ===== SQL-Fehler (A36) =====

    #[Test]
    public function dbClassReportsMissingConnection(): void
    {
        $db = new pdl_db_class();
        $this->assertSame('', $db->sql_error());
        $this->assertFalse($db->sql_query('SELECT 1'));
        $this->assertSame(-1, $db->sql_errno());
        $this->assertNotSame('', $db->sql_error());
    }

    #[Test]
    public function logExcerptMasksSecretsAndTruncates(): void
    {
        $masked = pdl_db_class::sql_log_excerpt("UPDATE pdl3_user SET passwort='geheim123', nick='x' WHERE user_id='1'");
        $this->assertStringNotContainsString('geheim123', $masked);
        $this->assertStringContainsString("passwort='***'", $masked);

        $plain = pdl_db_class::sql_log_excerpt("SELECT * FROM pdl3_release WHERE name='Satzung'");
        $this->assertStringContainsString("'Satzung'", $plain);

        $long = pdl_db_class::sql_log_excerpt("SELECT '" . str_repeat('ü', 500) . "'", 100);
        $this->assertLessThanOrEqual(110, strlen($long));
        $this->assertTrue(mb_check_encoding($long, 'UTF-8'));
    }
}
