<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Attrappe der Datenbankklasse: liefert vorbereitete Ergebnisse der Reihe
 * nach, protokolliert alle Anweisungen und lässt Anweisungen mit einem
 * Muster scheitern (Rückgabe false wie mysqli).
 */
final class SystemDbFake
{
    /** @var list<string> */
    public array $queries = [];
    /** @var list<list<array<int|string, mixed>>> */
    private array $results = [];
    public ?object $handler = null;

    public function __construct(private string $failPattern = '')
    {
    }

    /** @param list<array<int|string, mixed>> $rows */
    public function addResult(array $rows): self
    {
        $this->results[] = $rows;
        return $this;
    }

    /** @return \ArrayIterator<int, array<int|string, mixed>>|bool */
    public function sql_query(string $query): \ArrayIterator|bool
    {
        $this->queries[] = $query;
        if ($this->failPattern !== '' && preg_match($this->failPattern, $query) === 1) {
            return false;
        }
        if (preg_match('/^\s*(SELECT|SHOW)/i', $query) === 1) {
            return new \ArrayIterator(array_shift($this->results) ?? []);
        }
        return true;
    }

    /** @return array<int|string, mixed>|null */
    public function sql_fetch_array(mixed $result): ?array
    {
        if (!$result instanceof \ArrayIterator || !$result->valid()) {
            return null;
        }
        $row = $result->current();
        $result->next();
        return $row;
    }

    public function sql_escape_string(string $string): string
    {
        return addslashes($string);
    }

    public function sql_escape_int(mixed $value): int
    {
        return (int) $value;
    }

    public function sql_insert_id(): int
    {
        return 7;
    }
}

class SystemHelpersTest extends TestCase
{
    /** @var array<string, string> */
    private array $sqlTable = [
        'comments' => 'pdl3_comments', 'files' => 'pdl3_files', 'iplock' => 'pdl3_iplock', 'release' => 'pdl3_release',
        'rights' => 'pdl3_rights', 'screens' => 'pdl3_screens', 'settings' => 'pdl3_settings', 'user' => 'pdl3_user',
        'usergroup' => 'pdl3_usergroup', 'admin_log' => 'pdl3_admin_log',
    ];

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/pdl-admin/system_helpers.inc.php';
        ini_set('error_log', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdl-systemhelpers-test.log');
    }

    // ----- Rechte ---------------------------------------------------------

    #[Test]
    public function rightNamesOnlyAllowLowercaseAndUnderscore(): void
    {
        $this->assertTrue(pdl_sys_valid_right_name('adminaccess'));
        $this->assertTrue(pdl_sys_valid_right_name('upload_gross'));
        $this->assertFalse(pdl_sys_valid_right_name('adminaccess) VALUES (1); --'));
        $this->assertFalse(pdl_sys_valid_right_name('Admin'));
        $this->assertFalse(pdl_sys_valid_right_name('recht1'));
        $this->assertFalse(pdl_sys_valid_right_name('name'));
        $this->assertFalse(pdl_sys_valid_right_name('ugroup_id'));
        $this->assertFalse(pdl_sys_valid_right_name(str_repeat('a', 33)));
    }

    #[Test]
    public function identRejectsInjection(): void
    {
        $this->assertSame('`pdl3_user`', pdl_sys_ident('pdl3_user'));
        $this->expectException(\InvalidArgumentException::class);
        pdl_sys_ident('x`; DROP TABLE y; --');
    }

    #[Test]
    public function rightsListKeepsOnlyValidExistingColumns(): void
    {
        $db = (new SystemDbFake())
            ->addResult([['Field' => 'ugroup_id'], ['Field' => 'name'], ['Field' => 'download'], ['Field' => 'adminaccess']])
            ->addResult([
                ['right_id' => 1, 'name' => 'Downloads', 'bez' => '', 'variablenname' => 'download', 'reihenfolge' => 1],
                ['right_id' => 5, 'name' => 'Admin', 'bez' => '', 'variablenname' => 'adminaccess', 'reihenfolge' => 5],
                ['right_id' => 30, 'name' => 'Böse', 'bez' => '', 'variablenname' => 'x`=1', 'reihenfolge' => 6],
                ['right_id' => 31, 'name' => 'Ohne Spalte', 'bez' => '', 'variablenname' => 'fehlt', 'reihenfolge' => 7],
            ]);
        $rights = pdl_sys_rights_list($db, $this->sqlTable);
        $this->assertSame(['download', 'adminaccess'], array_column($rights, 'variablenname'));
    }

    #[Test]
    public function rightsFromPostIgnoresUnknownAndEnforcesAdminaccess(): void
    {
        $rights = [];
        foreach (['download', 'adminaccess', 'settings', 'editfiles'] as $i => $var) {
            $rights[] = ['right_id' => $i + 1, 'name' => $var, 'bez' => '', 'variablenname' => $var, 'reihenfolge' => $i];
        }
        $posted = ['download' => 'Y', 'settings' => 'Y', 'name`) VALUES (1)#' => 'Y', 'editfiles' => 'Y'];
        $values = pdl_sys_rights_from_post($rights, $posted);
        $this->assertSame(['download' => 'Y', 'adminaccess' => 'N', 'settings' => 'N', 'editfiles' => 'N'], $values);

        $posted['adminaccess'] = 'Y';
        $values = pdl_sys_rights_from_post($rights, $posted);
        $this->assertSame('Y', $values['settings']);

        // Gastgruppe: niemals Admin-Zugang
        $guest = pdl_sys_rights_from_post($rights, $posted, true);
        $this->assertSame('N', $guest['adminaccess']);
        $this->assertSame('N', $guest['settings']);
        $this->assertSame('Y', $guest['download']);
    }

    #[Test]
    public function canRequiresAdminaccessAndAllRights(): void
    {
        $this->assertFalse(pdl_sys_can(['settings' => 'Y'], 'settings'));
        $this->assertTrue(pdl_sys_can(['adminaccess' => 'Y', 'settings' => 'Y'], 'settings'));
        $this->assertFalse(pdl_sys_can(['adminaccess' => 'Y', 'edituser' => 'Y'], 'edituser', 'deluser'));
        $GLOBALS['user_details'] = ['user_id' => 3];
        $this->assertStringContainsString('„Einstellungen verwalten“', pdl_sys_denied_text('settings'));
        $GLOBALS['user_details'] = null;
        $this->assertStringContainsString('melden Sie sich zuerst an', pdl_sys_denied_text('settings'));
    }

    #[Test]
    public function protectedGroupsIncludeGuestGroup(): void
    {
        $this->assertSame([1, 2, 3, 7], pdl_sys_protected_group_ids(['guest_group_id' => '7']));
        $this->assertSame('guest', pdl_sys_group_role(7, ['guest_group_id' => '7']));
        $this->assertSame('admin', pdl_sys_group_role(2, []));
        $this->assertSame('member', pdl_sys_group_role(1, []));
        $this->assertSame('', pdl_sys_group_role(8, ['guest_group_id' => '3']));
    }

    #[Test]
    public function shippedKeysComeFromSchema(): void
    {
        $this->assertContains('site_url', pdl_sys_shipped_keys('settings'));
        $this->assertContains('orderby', pdl_sys_shipped_keys('settings'));
        $this->assertContains('backup', pdl_sys_shipped_keys('rights'));
        $this->assertContains('stats', pdl_sys_shipped_keys('template'));
        $this->assertNotContains('10', pdl_sys_shipped_keys('templategroup'));
    }

    // ----- Benutzer und Einstellungen --------------------------------------

    #[Test]
    public function homepageKeepsHttpsAndRepairsOldValues(): void
    {
        $this->assertSame('https://example.org/neu', pdl_sys_normalize_homepage('https://example.org/neu'));
        $this->assertSame('http://example.org', pdl_sys_normalize_homepage('http://example.org'));
        $this->assertSame('https://example.org', pdl_sys_normalize_homepage('example.org'));
        $this->assertSame('', pdl_sys_normalize_homepage('http://'));
        $this->assertSame('', pdl_sys_normalize_homepage('  '));
        $this->assertSame('https://example.org/alt', pdl_sys_normalize_homepage('http://https://example.org/alt'));
    }

    #[Test]
    public function eingabeParsingBuildingAndValidation(): void
    {
        $sort = pdl_sys_parse_eingabe('auswahl:name|time|views|votes|voted/votes');
        $this->assertSame('auswahl', $sort['type']);
        $this->assertSame('Datum', $sort['options']['time']);
        $this->assertSame('Durchschnittliche Bewertung', $sort['options']['voted/votes']);
        $this->assertNull(pdl_sys_validate_setting($sort, 'time'));
        $this->assertNotNull(pdl_sys_validate_setting($sort, 'date'));
        $this->assertNotNull(pdl_sys_validate_setting($sort, 'downloads'));

        $this->assertSame('auswahl:a=Erste|b=Zweite', pdl_sys_build_eingabe('auswahl', "a=Erste\nb=Zweite"));
        $this->assertNull(pdl_sys_build_eingabe('auswahl', ''));
        $this->assertNull(pdl_sys_build_eingabe('<select>', ''));
        $this->assertSame('name|time', pdl_sys_options_text(pdl_sys_parse_eingabe('auswahl:name|time')['options']));

        $legacy = pdl_sys_parse_eingabe('<select name="x"><option>1</option></select>');
        $this->assertSame('input', $legacy['type']);
        $this->assertTrue($legacy['legacy']);

        $zahl = pdl_sys_parse_eingabe('zahl');
        $this->assertNull(pdl_sys_validate_setting($zahl, '10'));
        $this->assertNotNull(pdl_sys_validate_setting($zahl, 'abc'));
        $this->assertNotNull(pdl_sys_validate_setting($zahl, '-1'));
        $this->assertNotNull(pdl_sys_validate_setting(pdl_sys_parse_eingabe('email'), 'kein-mail'));
        $this->assertNotNull(pdl_sys_validate_setting(pdl_sys_parse_eingabe('url'), 'javascript:alert(1)'));
        $this->assertNotNull(pdl_sys_validate_setting(pdl_sys_parse_eingabe('input'), ' ', true));
    }

    #[Test]
    public function settingFieldsUseMatchingInputTypes(): void
    {
        $this->assertStringContainsString('role="switch"', pdl_sys_setting_field_html('x', pdl_sys_parse_eingabe('anaus'), 'Y', 'h'));
        $this->assertStringContainsString('type="number"', pdl_sys_setting_field_html('x', pdl_sys_parse_eingabe('zahl'), '5', 'h'));
        $select = pdl_sys_setting_field_html('orderby', pdl_sys_parse_eingabe('auswahl:name|time'), 'date', 'h');
        $this->assertStringContainsString('id="setting_orderby"', $select);
        $this->assertStringContainsString('ungültig', $select);
        $password = pdl_sys_setting_field_html('ftp_passwort', pdl_sys_parse_eingabe('passwort'), 'geheim', 'h', false, true);
        $this->assertStringNotContainsString('geheim', $password);
        $this->assertStringContainsString('type="password"', $password);
    }

    // ----- Newsletter -----------------------------------------------------

    #[Test]
    public function letterPeriodUsesLastLetterOrThirtyDays(): void
    {
        $now = mktime(12, 0, 0, 10, 2, 2026);
        $p = pdl_sys_letter_period('', '', 0, $now);
        $this->assertSame('30tage', $p['mode']);
        $this->assertSame($now - 30 * 86400, $p['since']);
        $this->assertSame('in den letzten 30 Tagen', $p['phrase']);

        $last = mktime(9, 0, 0, 9, 1, 2026);
        $p = pdl_sys_letter_period('', '', $last, $now);
        $this->assertSame('seit_letztem', $p['mode']);
        $this->assertSame($last, $p['since']);
        $this->assertSame('seit dem letzten Newsletter am 01.09.2026', $p['phrase']);

        $p = pdl_sys_letter_period('datum', '2026-08-15', $last, $now);
        $this->assertSame('seit dem 15.08.2026', $p['phrase']);
        $this->assertSame('30tage', pdl_sys_letter_period('datum', 'kaputt', 0, $now)['mode']);
    }

    #[Test]
    public function letterTeaserRemovesMarkupAndCutsCleanly(): void
    {
        $this->assertSame('Fett und Link', pdl_sys_letter_teaser('[b]Fett[/b] und [url=https://x.de]Link[/url]'));
        $this->assertSame('Kurz', pdl_sys_letter_teaser("Kurz{trenn}Rest", '{trenn}'));
        // Trennung nach Zeichen: Marke verschwindet, der Text bleibt ganz
        $this->assertSame('Kurz. Rest', pdl_sys_letter_teaser('Kurz.{trenn} Rest', '{trenn}', 250, false));
        $this->assertSame('A & B', pdl_sys_letter_teaser('<p>A &amp; B</p>'));
        $long = str_repeat('Äpfel ', 80);
        $teaser = pdl_sys_letter_teaser($long, '', 50);
        $this->assertStringEndsWith(' …', $teaser);
        $this->assertTrue(mb_check_encoding($teaser, 'UTF-8'));
        $this->assertLessThanOrEqual(53, mb_strlen($teaser));
    }

    #[Test]
    public function letterTextHasAbsoluteLinksAndCorrectGrammar(): void
    {
        $now = mktime(12, 0, 0, 10, 2, 2026);
        $period = ['mode' => '30tage', 'since' => 0, 'phrase' => 'in den letzten 30 Tagen'];
        $url = static fn (string $q): string => 'https://example.org/dl/downloads.php?' . $q;
        $one = pdl_sys_letter_text([['release_id' => 5, 'name' => 'Satzung', 'text' => 'Neue Fassung', 'time' => $now]], $period, 'Verein', $now, $url);
        $this->assertStringContainsString('In den letzten 30 Tagen ist 1 neues Release erschienen:', $one);
        $this->assertStringContainsString('https://example.org/dl/downloads.php?release_id=5', $one);
        $this->assertStringContainsString('https://example.org/dl/downloads.php?usercenter=profil', $one);
        $this->assertStringNotContainsString(' sie ', $one);
        $none = pdl_sys_letter_text([], $period, 'Verein', $now, $url);
        $this->assertStringContainsString('In den letzten 30 Tagen sind keine neuen Releases erschienen.', $none);
    }

    #[Test]
    public function recipientsOnlyFromSelectedGroupsWithoutGuests(): void
    {
        $db = (new SystemDbFake())->addResult([['email' => 'a@example.org'], ['email' => 'A@example.org'], ['email' => 'kaputt'], ['email' => 'b@example.org']]);
        $list = pdl_sys_letter_recipients($db, $this->sqlTable, [1, 3, 9], 3);
        $this->assertSame(['a@example.org', 'b@example.org'], $list);
        $this->assertStringContainsString("get_letter = 'Y' AND ugroup_id IN (1,9)", $db->queries[0]);
        $this->assertSame([], pdl_sys_letter_recipients(new SystemDbFake(), $this->sqlTable, [3], 3));
    }

    #[Test]
    public function addressParsingDeduplicates(): void
    {
        $r = pdl_sys_parse_addresses('a@example.org; b@example.org, A@example.org  falsch');
        $this->assertSame(['a@example.org', 'b@example.org'], $r['valid']);
        $this->assertSame(['falsch'], $r['invalid']);
    }

    // ----- Sicherung ------------------------------------------------------

    #[Test]
    public function backupKeepsNullAndRemovesSecrets(): void
    {
        $db = new SystemDbFake();
        $row = pdl_sys_backup_clean_row('pdl3_user', [0 => '1', 'user_id' => '1', 'nick' => "O'Neil", 'signatur' => null, 'session_token' => 'abc', 'remind_code' => 'xyz', 'remind_expires' => '99'], $this->sqlTable);
        $this->assertSame('', $row['session_token']);
        $this->assertSame('', $row['remind_code']);
        $this->assertSame(0, $row['remind_expires']);
        $this->assertArrayNotHasKey(0, $row);
        $sql = pdl_sys_backup_insert($db, 'pdl3_user', $row);
        $this->assertSame("INSERT INTO `pdl3_user` (`user_id`, `nick`, `signatur`, `session_token`, `remind_code`, `remind_expires`) VALUES ('1', 'O\\'Neil', NULL, '', '', 0);\n", $sql);
        $this->assertTrue(pdl_sys_backup_is_powerdownload(pdl_sys_backup_header('v3.6.0', time())));
        $this->assertTrue(pdl_sys_backup_is_powerdownload("# PowerDownload v3.5.0 MySQL-Dump\n"));
        $this->assertFalse(pdl_sys_backup_is_powerdownload("DROP DATABASE x;\n"));
        $this->assertSame(['pdl3_comments', 'pdl3_files'], pdl_sys_backup_tables(['a' => 'pdl3_comments', 'b' => 'pdl3_files', 'c' => 'bad name']));
    }

    #[Test]
    public function splitSqlRespectsStringsAndComments(): void
    {
        $sql = "-- PowerDownload Sicherung\n# alter Kommentar\nSET NAMES utf8mb4;\n"
            . "INSERT INTO `t` VALUES ('a;b', 'C:\\\\', 'O\\'Neil', \"x;y\");\n"
            . "/* Block; Kommentar */ DELETE FROM t WHERE c = 'it''s';\n"
            . "-- Ende\n";
        $parts = pdl_sys_split_sql($sql);
        $this->assertCount(3, $parts);
        $this->assertSame('SET NAMES utf8mb4', $parts[0]);
        $this->assertSame("INSERT INTO `t` VALUES ('a;b', 'C:\\\\', 'O\\'Neil', \"x;y\")", $parts[1]);
        $this->assertStringEndsWith("DELETE FROM t WHERE c = 'it''s'", $parts[2]);
    }

    #[Test]
    public function importCountsFailures(): void
    {
        $db = new SystemDbFake('/FAIL/');
        $result = pdl_sys_backup_import($db, "INSERT INTO a VALUES (1);\nINSERT INTO FAIL VALUES (2);\nINSERT INTO b VALUES (3);");
        $this->assertSame(['total' => 3, 'ok' => 2, 'failed' => 1], array_intersect_key($result, ['total' => 0, 'ok' => 0, 'failed' => 0]));
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('INSERT INTO FAIL', $result['errors'][0]);
    }

    // ----- Zähler und Kommentare zurücksetzen ------------------------------

    #[Test]
    public function resetRunsInTransactionAndCommits(): void
    {
        $db = new SystemDbFake();
        $result = pdl_sys_reset_run($db, $this->sqlTable, true);
        $this->assertTrue($result['ok']);
        $this->assertSame('START TRANSACTION', $db->queries[0]);
        $this->assertSame('COMMIT', end($db->queries));
        $joined = implode("\n", $db->queries);
        $this->assertStringContainsString('UPDATE `pdl3_release` SET views = 0, votes = 0, voted = 0', $joined);
        $this->assertStringContainsString('UPDATE `pdl3_files` SET downloads = 0', $joined);
        $this->assertStringContainsString('DELETE FROM `pdl3_comments`', $joined);
        $this->assertStringContainsString('DELETE FROM `pdl3_iplock`', $joined);
        $this->assertStringContainsString('UPDATE `pdl3_screens` SET views = 0', $joined);
        $this->assertStringNotContainsString('DROP', $joined);
        $this->assertStringNotContainsString('pdl3_user', $joined);
    }

    #[Test]
    public function resetRollsBackOnError(): void
    {
        $db = new SystemDbFake('/DELETE FROM `pdl3_comments`/');
        $result = pdl_sys_reset_run($db, $this->sqlTable, false);
        $this->assertFalse($result['ok']);
        $this->assertSame('ROLLBACK', end($db->queries));
        $this->assertStringNotContainsString('pdl3_screens', implode("\n", $db->queries));
    }

    #[Test]
    public function resetCountsReadsAllNumbers(): void
    {
        $db = (new SystemDbFake())
            ->addResult([['Field' => 'screen_id'], ['Field' => 'views']])
            ->addResult([[0 => 12]])
            ->addResult([[0 => 345]])
            ->addResult([[0 => 4]])
            ->addResult([[0 => 678]])
            ->addResult([[0 => 9]])
            ->addResult([[0 => 3]])
            ->addResult([[0 => 2]])
            ->addResult([[0 => 50]]);
        $counts = pdl_sys_reset_counts($db, $this->sqlTable);
        $this->assertSame(12, $counts['comments']);
        $this->assertSame(345, $counts['downloads']);
        $this->assertSame(678, $counts['views']);
        $this->assertTrue($counts['has_screen_views']);
        $this->assertSame(50, $counts['screen_views']);
    }

    // ----- Sicherheitskorrekturen 3.6.0 (R7, R8, R11) -----------------------

    #[Test]
    public function replacementsAndAddfilesCountAsAdminRights(): void
    {
        $admin_like = pdl_sys_admin_like_rights();
        $this->assertArrayHasKey('replacements', $admin_like, 'Glossar-HTML wirkt roh auf allen Seiten.');
        $this->assertArrayHasKey('addfiles', $admin_like, 'Uploads, per FTP auch in beliebige Verzeichnisse.');

        $rights = [];
        foreach (['adminaccess' => 'Admin-Zugang', 'addfiles' => 'Releases und Dateien hinzufügen', 'replacements' => 'Ersetzungen verwalten', 'comment' => 'Kommentare moderieren'] as $var => $name) {
            $rights[] = ['right_id' => count($rights) + 1, 'name' => $name, 'bez' => 'Benötigt Admin-Zugang.', 'variablenname' => $var, 'reihenfolge' => count($rights) + 1];
        }
        $html = pdl_sys_rights_switches_html($rights, []);
        $hint = (string) strstr((string) strstr($html, 'id="pdlRightsAdminHint"'), '</div>', true);
        $this->assertStringContainsString('„Releases und Dateien hinzufügen“', $hint);
        $this->assertStringContainsString('„Ersetzungen verwalten“', $hint);
        $this->assertStringNotContainsString('Kommentare moderieren', $hint);
        $this->assertSame(2, substr_count($html, '>Admin-Recht</span>'));
    }

    #[Test]
    public function adminAccountsNeedSettingsRightToBeDeleted(): void
    {
        $mod = ['adminaccess' => 'Y', 'deluser' => 'Y', 'settings' => 'N'];
        $admin = ['adminaccess' => 'Y', 'deluser' => 'Y', 'settings' => 'Y'];
        $this->assertTrue(pdl_sys_may_delete_user('N', $mod), 'Normale Konten darf „Benutzer löschen“ löschen.');
        $this->assertFalse(pdl_sys_may_delete_user('Y', $mod), 'Konten mit Admin-Zugang nur mit „Einstellungen verwalten“.');
        $this->assertTrue(pdl_sys_may_delete_user('Y', $admin));
        $this->assertFalse(pdl_sys_may_delete_user('N', ['adminaccess' => 'Y', 'deluser' => 'N', 'settings' => 'Y']));
    }

    #[Test]
    public function letterJobStateBlocksSecondSendAndResumesAfterAbort(): void
    {
        $now = 1_800_000_000;
        $job = ['fingerprint' => 'abc', 'started' => $now - 600, 'heartbeat' => $now - 600, 'last_user_id' => 12, 'sent' => 5, 'failed' => 0, 'finished' => 0];
        $this->assertSame('new', pdl_sys_letter_job_state(null, 'abc', $now));
        $this->assertSame('new', pdl_sys_letter_job_state($job, 'anderer Newsletter', $now));
        $this->assertSame('resume', pdl_sys_letter_job_state($job, 'abc', $now), 'Abgebrochen: fortsetzen statt neu beginnen.');
        $this->assertSame('running', pdl_sys_letter_job_state(['heartbeat' => $now - 30] + $job, 'abc', $now), 'Läuft noch: nicht parallel senden.');
        $this->assertSame('done', pdl_sys_letter_job_state(['finished' => $now - 3600] + $job, 'abc', $now), 'Zweites „Senden“: bereits verschickt.');
        $this->assertSame('new', pdl_sys_letter_job_state(['finished' => $now - 90000] + $job, 'abc', $now));
    }

    #[Test]
    public function letterJobIsStoredAsHiddenSettingWithoutAddresses(): void
    {
        $job = ['fingerprint' => 'abc', 'started' => 1, 'heartbeat' => 2, 'last_user_id' => 12, 'sent' => 5, 'failed' => 1, 'finished' => 0];
        $db = (new SystemDbFake())->addResult([['c' => 0]]);
        $this->assertTrue(pdl_sys_letter_job_save($db, $this->sqlTable, $job));
        $this->assertStringContainsString("INSERT INTO `pdl3_settings`", $db->queries[1]);
        $this->assertStringContainsString("VALUES ('letter_job', '', '', '", $db->queries[1]);
        $this->assertStringContainsString("', '', 0, 0)", $db->queries[1]);
        $this->assertStringNotContainsString('@', $db->queries[1]);

        $db = (new SystemDbFake())->addResult([['c' => 1]]);
        $this->assertTrue(pdl_sys_letter_job_save($db, $this->sqlTable, $job));
        $this->assertStringStartsWith('UPDATE `pdl3_settings` SET wert = ', $db->queries[1]);

        $db = (new SystemDbFake())->addResult([['wert' => (string) json_encode($job)]]);
        $this->assertSame($job, pdl_sys_letter_job_load($db, $this->sqlTable));
        $this->assertNull(pdl_sys_letter_job_load((new SystemDbFake())->addResult([['wert' => '']]), $this->sqlTable));
        $this->assertNull(pdl_sys_letter_job_load(new SystemDbFake(), $this->sqlTable));
    }

    #[Test]
    public function recipientRowsKeepUserIdsForResuming(): void
    {
        $db = (new SystemDbFake())->addResult([
            ['user_id' => 3, 'email' => 'a@example.org'],
            ['user_id' => 5, 'email' => 'A@example.org'],
            ['user_id' => 8, 'email' => 'kaputt'],
            ['user_id' => 9, 'email' => 'b@example.org'],
        ]);
        $rows = pdl_sys_letter_recipient_rows($db, $this->sqlTable, [1], 3);
        $this->assertSame([['user_id' => 3, 'email' => 'a@example.org'], ['user_id' => 9, 'email' => 'b@example.org']], $rows);
        $this->assertStringContainsString('SELECT user_id, email FROM `pdl3_user`', $db->queries[0]);
        $this->assertStringContainsString('ORDER BY user_id ASC', $db->queries[0]);
    }
}
