<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerDownload\Tests\Support\MockDbHandler;

/**
 * Sicherheitskorrekturen vor dem Release 3.6.0 (Code-Review R1, R4, R6, R8,
 * R13 und Zensur nur außerhalb von HTML-Tags).
 */
class SecurityFixes36Test extends TestCase
{
    private string $tmpBase = '';

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_admin_validation.inc.php';
        require_once dirname(__DIR__, 2) . '/pdl-admin/functions.inc.php';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_HOST']);
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

    private function makeBase(): string
    {
        $this->tmpBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdl_sec36_' . bin2hex(random_bytes(4));
        mkdir($this->tmpBase . '/pdl-files/3', 0777, true);
        return $this->tmpBase;
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function upload(string $name, array $extra = []): array
    {
        return $extra + ['error' => UPLOAD_ERR_OK, 'tmp_name' => __FILE__, 'size' => 10, 'name' => $name];
    }

    // ===== R1: doppelte Endungen =====

    /**
     * @return iterable<string, array{string}>
     */
    public static function rejectedUploads(): iterable
    {
        yield 'phtml' => ['x.phtml'];
        yield 'Großbuchstaben' => ['x.pHp'];
        yield 'htaccess' => ['.htaccess'];
        yield 'php5' => ['paket.PHP5'];
        yield 'pht' => ['x.pht'];
        yield 'phar' => ['x.zip.phar'];
        yield 'shtml' => ['seite.shtml'];
    }

    #[Test]
    #[DataProvider('rejectedUploads')]
    public function uploadWithExecutableLastExtensionIsRejected(string $name): void
    {
        $this->assertNotNull(pdl_validate_file_upload($this->upload($name)), 'Erwartet Ablehnung für: ' . $name);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function renamedUploads(): iterable
    {
        yield 'php.zip' => ['x.php.zip', 'x_php.zip'];
        yield 'php.png (Smiley)' => ['x.php.png', 'x_php.png'];
        yield 'Großschreibung innen' => ['Tool.PhP.tar.gz', 'Tool_PhP.tar.gz'];
        yield 'mehrere innen' => ['a.cgi.pl.7z', 'a_cgi_pl.7z'];
        yield 'phtml allein' => ['x.phtml', 'x_phtml'];
        yield 'htaccess' => ['.htaccess', 'htaccess'];
        yield 'Punkt am Ende' => ['x.php.', 'x_php'];
        yield 'Doppelpunkte' => ['x..php..zip', 'x_php.zip'];
        yield 'unverändert' => ['release-1.0.zip', 'release-1.0.zip'];
        yield 'tar.gz bleibt' => ['archiv.tar.gz', 'archiv.tar.gz'];
    }

    #[Test]
    #[DataProvider('renamedUploads')]
    public function storedNameNeverEndsInAnExecutableExtension(string $name, string $expected): void
    {
        $this->assertSame($expected, pdl_sanitize_upload_filename($name));
    }

    #[Test]
    public function doubleExtensionUploadIsAcceptedButStoredSafely(): void
    {
        $this->assertNull(pdl_validate_file_upload($this->upload('x.php.zip')));
        $this->assertSame('x_php.zip', pdl_sanitize_upload_filename('x.php.zip'));
    }

    #[Test]
    public function shorteningTheNameCannotExposeAnExtension(): void
    {
        // 116 Zeichen + „.phpxyz“: Ohne Vorsicht bliebe nach dem Kürzen auf 120 Zeichen „….php“.
        $name = str_repeat('a', 116) . '.phpxyz';
        $safe = pdl_sanitize_upload_filename($name);
        $this->assertLessThanOrEqual(120, strlen($safe));
        $this->assertDoesNotMatchRegularExpression('/\.(ph(p\d?|t|tml|ar|ps)|cgi|pl|py|sh)(\.|$)/i', $safe);
    }

    #[Test]
    public function executableExtensionListIgnoresCase(): void
    {
        foreach (['php', 'PHP', 'php7', 'pht', 'phtml', 'phar', 'phps', 'cgi', 'pl', 'py', 'sh', 'shtml', 'htaccess'] as $ext) {
            $this->assertTrue(pdl_upload_extension_is_executable($ext), $ext);
        }
        foreach (['zip', 'png', 'pdf', 'exe', 'gz', 'phpx', 'tar'] as $ext) {
            $this->assertFalse(pdl_upload_extension_is_executable($ext), $ext);
        }
    }

    // ===== R1/R4: Schutzdateien =====

    private static function normalized(string $file): string
    {
        return str_replace("\r\n", "\n", (string) file_get_contents(dirname(__DIR__, 2) . '/' . $file));
    }

    #[Test]
    public function uploadDirectoryProtectionFilesMatchTheGenerators(): void
    {
        $this->assertSame(self::normalized('pdl-files/.htaccess'), pdl_admin_files_htaccess());
        $this->assertSame(self::normalized('pdl-gfx/screens/.htaccess'), pdl_admin_screens_htaccess());
        $this->assertSame(self::normalized('pdl-gfx/smilies/.htaccess'), pdl_admin_smilies_htaccess());
    }

    #[Test]
    public function pdlFilesDeniesEveryDirectRequest(): void
    {
        $htaccess = self::normalized('pdl-files/.htaccess');
        $this->assertMatchesRegularExpression('/<IfModule mod_authz_core\.c>\s*Require all denied\s*<\/IfModule>/', $htaccess);
        $this->assertMatchesRegularExpression('/<IfModule !mod_authz_core\.c>\s*Order allow,deny\s*Deny from all\s*<\/IfModule>/', $htaccess);
        $this->assertStringContainsString('RemoveHandler .php', $htaccess);
        $this->assertStringNotContainsString('Require all granted', $htaccess);
        // Zusätzlich in der Webroot-.htaccess, falls pdl-files/.htaccess beim FTP-Upload fehlt.
        $this->assertStringContainsString('RewriteRule ^pdl-files(/|$) - [F,L]', self::normalized('.htaccess'));
    }

    #[Test]
    public function imageDirectoriesBlockScriptExtensionsAnywhereInTheName(): void
    {
        foreach (['pdl-gfx/screens/.htaccess', 'pdl-gfx/smilies/.htaccess'] as $file) {
            $htaccess = self::normalized($file);
            $this->assertStringContainsString('<FilesMatch "\.(ph(p\d?|t|tml|ar|ps)|cgi|pl|py|sh)(\.|$)">', $htaccess, $file);
            $this->assertStringContainsString('RemoveType .php', $htaccess, $file);
            // Die Sperre steht nach der Freigabe für Bilder, damit sie gewinnt.
            $this->assertGreaterThan(strpos($htaccess, 'Require all granted'), strrpos($htaccess, 'Require all denied'), $file);
            $pattern = '/\.(ph(p\d?|t|tml|ar|ps)|cgi|pl|py|sh)(\.|$)/';
            $this->assertSame(1, preg_match($pattern, 'smiley.php.png'));
            $this->assertSame(1, preg_match($pattern, 'x.phtml'));
            $this->assertSame(0, preg_match($pattern, 'release4screen1g.jpg'));
        }
    }

    #[Test]
    public function missingPdlFilesProtectionIsRecreated(): void
    {
        $base = $this->makeBase();
        pdl_admin_ensure_files_htaccess($base . '/pdl-files');
        $this->assertSame(pdl_admin_files_htaccess(), file_get_contents($base . '/pdl-files/.htaccess'));
        file_put_contents($base . '/pdl-files/.htaccess', 'eigene Regeln');
        pdl_admin_ensure_files_htaccess($base . '/pdl-files');
        $this->assertSame('eigene Regeln', file_get_contents($base . '/pdl-files/.htaccess'), 'Vorhandene Datei bleibt.');
    }

    // ===== R4: Auslieferung =====

    #[Test]
    public function absoluteUrlToOwnPdlFilesIsDeliveredLocally(): void
    {
        $base = $this->makeBase();
        file_put_contents($base . '/pdl-files/3/a b.zip', 'PK');
        $expected = realpath($base . '/pdl-files/3/a b.zip');

        $this->assertSame(['type' => 'local', 'path' => $expected], pdl_download_target('http://localhost:8246/pdl-files/3/a%20b.zip', $base, 'localhost:8246'));
        $this->assertSame(['type' => 'local', 'path' => $expected], pdl_download_target('pdl-files/3/a%20b.zip', $base, 'localhost:8246'));
        // Fremder Server: weiterleiten
        $this->assertSame('redirect', pdl_download_target('https://cloud.example.org/pdl-files/3/a%20b.zip', $base, 'localhost:8246')['type'] ?? null);
        // Ohne bekannten Host bleibt es bei der Weiterleitung.
        $this->assertSame('redirect', pdl_download_target('http://localhost:8246/pdl-files/3/a%20b.zip', $base, '')['type'] ?? null);
        $_SERVER['HTTP_HOST'] = 'localhost:8246';
        $this->assertSame('local', pdl_download_target('http://localhost:8246/pdl-files/3/a%20b.zip', $base)['type'] ?? null);
    }

    // ===== R6: Datei noch in Gebrauch =====

    #[Test]
    public function sharedFileIsRecognisedRegardlessOfUrlSpelling(): void
    {
        global $db_handler, $sql_table;
        $base = $this->makeBase();
        file_put_contents($base . '/pdl-files/3/a b.zip', 'PK');
        $path = (string) realpath($base . '/pdl-files/3/a b.zip');
        $sql_table = ['files' => 'pdl3_files'];
        $_SERVER['HTTP_HOST'] = 'localhost:8246';

        foreach (['pdl-files/3/a b.zip', './pdl-files/3/a%20b.zip', '/pdl-files/3/a%20b.zip', 'http://localhost:8246/pdl-files/3/a%20b.zip', 'pdl%2Dfiles/3/a%20b.zip'] as $other) {
            $db_handler = (new MockDbHandler())->addResult([['url' => 'https://example.org/x.zip'], ['url' => $other]]);
            $this->assertTrue(pdl_admin_local_file_in_use($path, $base), 'Gleiche Datei: ' . $other);
        }

        $db_handler = (new MockDbHandler())->addResult([['url' => 'pdl-files/3/andere.zip'], ['url' => 'https://example.org/pdl-files/3/a%20b.zip']]);
        $this->assertFalse(pdl_admin_local_file_in_use($path, $base));
    }

    // ===== R13: Ordnerbaum mit Kreisen =====

    #[Test]
    public function folderTreeStopsAtMaximumDepth(): void
    {
        global $db_handler, $sordner_id, $settings, $sql_table, $release, $screen_id;
        $db_handler = new MockDbHandler();
        for ($i = 0; $i < 100; $i++) {
            // Jede Ebene liefert wieder denselben Ordner (Kreis in der Datenbank).
            $db_handler->addResult([['ordner_id' => 7, 'name' => 'Kreis']]);
        }
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $sordner_id = 0;
        $settings['script_file'] = 'dl.php?';
        $release = null;
        $screen_id = 0;

        ob_start();
        treeview_ordner(0, '');
        $output = (string) ob_get_clean();

        $this->assertSame(pdl_tree_max_depth(), substr_count($output, 'Kreis'));
        $this->assertSame(pdl_tree_max_depth(), $db_handler->querys);
    }

    #[Test]
    public function breadcrumbStopsAtFolderCircle(): void
    {
        global $db_handler, $settings, $sql_table, $release_id, $screen_id, $ordner_id;
        $db_handler = new MockDbHandler();
        // 1 liegt in 2, 2 liegt in 1.
        $db_handler->addResult([['ordner_id' => 1, 'sordner_id' => 2, 'name' => 'Eins']]);
        $db_handler->addResult([['ordner_id' => 2, 'sordner_id' => 1, 'name' => 'Zwei']]);
        for ($i = 0; $i < 50; $i++) {
            $db_handler->addResult([['ordner_id' => 1, 'sordner_id' => 2, 'name' => 'Eins']]);
        }
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $settings['script_file'] = 'dl.php?';
        $release_id = 0;
        $screen_id = 0;
        $ordner_id = 1;

        ob_start();
        treeview_pfeil(1);
        $output = (string) ob_get_clean();

        $this->assertSame(2, $db_handler->querys);
        $this->assertStringContainsString('Index', $output);
        $this->assertSame(1, substr_count($output, 'Eins'));
    }

    #[Test]
    public function folderSelectStopsAtMaximumDepth(): void
    {
        global $db_handler, $sql_table, $pdl_treeview_cache;
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $db_handler = new MockDbHandler();
        treeview_select_reset_cache();
        // Ordner 0 → 5 → 5 → …: 5 ist sein eigener Unterordner.
        $pdl_treeview_cache = [0 => [['ordner_id' => 5, 'name' => 'Selbst']], 5 => [['ordner_id' => 5, 'name' => 'Selbst']]];

        $output = treeview_select(0, '');
        $this->assertSame(pdl_tree_max_depth(), substr_count($output, '<option'));
        treeview_select_reset_cache();
    }

    // ===== Zensur nur im Text =====

    #[Test]
    public function badwordsLeaveLinkAddressesAndAttributesAlone(): void
    {
        $html = 'Siehe <a href="https://www.example.org/example">example.org</a> <img src="example.png" alt="example"> – ein Example.';
        $out = bbcode_apply_badwords($html, ['example']);
        $this->assertStringContainsString('href="https://www.example.org/example"', $out);
        $this->assertStringContainsString('src="example.png" alt="example"', $out);
        $this->assertStringContainsString('>e******.org</a>', $out);
        $this->assertStringContainsString('ein E******.', $out);
    }

    #[Test]
    public function badwordsKeepEntitiesAndHandleUmlauts(): void
    {
        $this->assertSame('&quot;S*****&quot; &amp; mehr', bbcode_apply_badwords('&quot;Schund&quot; &amp; mehr', ['schund', 'quot', 'amp']));
        $this->assertSame('Ü***** und ü*****', bbcode_apply_badwords('Übelst und übelst', ['übelst']));
        $this->assertSame('S********** im Text', bbcode_apply_badwords('Schweinkram im Text', ['schweinkram']));
    }
}
