<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerDownload\Tests\Support\IplockMemoryDb;
use PowerDownload\Tests\Support\RecordingDbHandler;

/**
 * Sperren in pdl3_iplock (pdl-inc/pdl_locks.inc.php):
 * - R3: Anmelde-Fehlversuche zählen je IP, eine erfolgreiche Anmeldung löscht
 *   nur die Fehlversuche des eigenen Kontos.
 * - D1: Bewertung, Download-Zähler und Kommentar-Pause je Konto, Gäste je IP.
 * - D3: Rücksprungziel back_release.
 */
class LocksTest extends TestCase
{
    /** @var array<string, string> */
    private array $sqlTable = ['iplock' => 'pdl3_iplock'];

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_locks.inc.php';
    }

    // ==================== R3: Anmelde-Sperre ====================

    #[Test]
    public function loginFailureStoresAttemptedAccount(): void
    {
        $db = new IplockMemoryDb();
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 7);
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 0);

        $this->assertSame([7, 0], array_column($db->rows, 'user_id'));
        $this->assertSame(['login', 'login'], array_column($db->rows, 'art'));
        $this->assertSame(['10.0.0.1', '10.0.0.1'], array_column($db->rows, 'ip'));
    }

    #[Test]
    public function loginFailuresCountPerIpAcrossAccounts(): void
    {
        $db = new IplockMemoryDb();
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 1);
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 2);
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 0);
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.2', 1);

        $this->assertSame(3, pdl_login_failures($db, $this->sqlTable, '10.0.0.1'));
        $this->assertSame(1, pdl_login_failures($db, $this->sqlTable, '10.0.0.2'));
        $this->assertSame(0, pdl_login_failures($db, $this->sqlTable, '10.0.0.3'));
    }

    #[Test]
    public function loginOnOwnAccountDoesNotResetLockAgainstOtherAccounts(): void
    {
        // Umgehung (R3): vier Versuche gegen das Konto 7, dann Anmeldung am
        // eigenen Konto 9 – bisher löschte das alle Fehlversuche der IP.
        $db = new IplockMemoryDb();
        for ($i = 0; $i < 4; $i++) {
            pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 7);
        }
        pdl_login_failures_clear($db, $this->sqlTable, '10.0.0.1', 9);
        $this->assertSame(4, pdl_login_failures($db, $this->sqlTable, '10.0.0.1'));

        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 7);
        $this->assertGreaterThanOrEqual(5, pdl_login_failures($db, $this->sqlTable, '10.0.0.1'), 'Der fünfte Fehlversuch sperrt die IP.');
    }

    #[Test]
    public function successfulLoginClearsOnlyOwnFailuresFromThisIp(): void
    {
        $db = new IplockMemoryDb();
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 9); // vertippt
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 9);
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 0); // unbekannter Name
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.2', 9); // fremde IP, gleiches Konto

        pdl_login_failures_clear($db, $this->sqlTable, '10.0.0.1', 9);

        $this->assertSame(1, pdl_login_failures($db, $this->sqlTable, '10.0.0.1'));
        $this->assertSame(1, pdl_login_failures($db, $this->sqlTable, '10.0.0.2'), 'Versuche von anderen Adressen bleiben.');
    }

    #[Test]
    public function clearWithoutAccountDeletesNothing(): void
    {
        $db = new IplockMemoryDb();
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 0);
        $queries = count($db->queries);

        pdl_login_failures_clear($db, $this->sqlTable, '10.0.0.1', 0);

        $this->assertCount($queries, $db->queries);
        $this->assertSame(1, $db->count('login'));
    }

    #[Test]
    public function oldLoginFailuresExpireAndAreRemoved(): void
    {
        $db = new IplockMemoryDb();
        $db->rows[] = ['ip' => '10.0.0.1', 'time' => time() - 1000, 'file_id' => 0, 'user_id' => 7, 'art' => 'login'];
        $db->rows[] = ['ip' => '10.0.0.1', 'time' => time() - 1000, 'file_id' => 4, 'user_id' => 7, 'art' => 'vote'];
        pdl_login_failure_add($db, $this->sqlTable, '10.0.0.1', 7);

        $this->assertSame(1, pdl_login_failures($db, $this->sqlTable, '10.0.0.1'));
        $this->assertSame(1, $db->count('login'), 'Abgelaufene Fehlversuche werden gelöscht.');
        $this->assertSame(1, $db->count('vote'), 'Andere Arten bleiben unberührt.');
    }

    #[Test]
    public function loginQueriesEscapeIp(): void
    {
        $db = new RecordingDbHandler();
        pdl_login_failures_clear($db, $this->sqlTable, "1.2.3.4' OR '1'='1", 3);

        $this->assertSame(["DELETE FROM pdl3_iplock WHERE art='login' AND ip='1.2.3.4\\' OR \\'1\\'=\\'1' AND user_id='3'"], $db->queries);
    }

    // ==================== D1: Sperren je Konto, Gäste je IP ====================

    #[Test]
    public function membersSharingAnIpVoteIndependently(): void
    {
        $db = new IplockMemoryDb();
        pdl_lock_add($db, $this->sqlTable, 'vote', 4, 2, '192.168.1.10'); // Jonas im Vereins-WLAN

        $this->assertTrue(pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 2, '192.168.1.10'));
        $this->assertFalse(pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 3, '192.168.1.10'), 'Frieda darf trotz gleicher IP bewerten.');
        $this->assertFalse(pdl_lock_exists($db, $this->sqlTable, 'vote', 5, 2, '192.168.1.10'), 'Anderes Release ist frei.');
        $this->assertTrue(pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 2, '10.9.9.9'), 'Das Konto bleibt auch von einer anderen IP aus gesperrt.');
    }

    #[Test]
    public function guestsAreLockedPerIp(): void
    {
        $db = new IplockMemoryDb();
        pdl_lock_add($db, $this->sqlTable, 'vote', 4, 0, '192.168.1.10');

        $this->assertTrue(pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 0, '192.168.1.10'));
        $this->assertFalse(pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 0, '192.168.1.11'));
        $this->assertFalse(pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 2, '192.168.1.10'), 'Ein Gast sperrt kein Mitglied.');
    }

    #[Test]
    public function guestSeesLockOfMemberFromSameIp(): void
    {
        // Bewerten, abmelden, als Gast noch einmal bewerten zählt nicht doppelt.
        $db = new IplockMemoryDb();
        pdl_lock_add($db, $this->sqlTable, 'vote', 4, 2, '192.168.1.10');

        $this->assertTrue(pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 0, '192.168.1.10'));
    }

    #[Test]
    public function downloadCounterLockIsPerAccountAndFile(): void
    {
        $db = new IplockMemoryDb();
        pdl_lock_add($db, $this->sqlTable, 'download', 4, 2, '192.168.1.10');

        $this->assertTrue(pdl_lock_exists($db, $this->sqlTable, 'download', 4, 2, '192.168.1.10'));
        $this->assertFalse(pdl_lock_exists($db, $this->sqlTable, 'download', 4, 3, '192.168.1.10'));
        $this->assertFalse(pdl_lock_exists($db, $this->sqlTable, 'download', 5, 2, '192.168.1.10'));
        $this->assertFalse(pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 2, '192.168.1.10'), 'Arten sind getrennt.');
    }

    #[Test]
    public function commentPauseRespectsSinceAndIgnoresRelease(): void
    {
        $db = new IplockMemoryDb();
        $db->rows[] = ['ip' => '192.168.1.10', 'time' => time() - 120, 'file_id' => 1, 'user_id' => 2, 'art' => 'comment'];
        $this->assertFalse(pdl_lock_exists($db, $this->sqlTable, 'comment', 0, 2, '192.168.1.10', time() - 60));

        pdl_lock_add($db, $this->sqlTable, 'comment', 1, 2, '192.168.1.10');
        $this->assertTrue(pdl_lock_exists($db, $this->sqlTable, 'comment', 0, 2, '192.168.1.10', time() - 60), 'Pause gilt für alle Releases.');
        $this->assertFalse(pdl_lock_exists($db, $this->sqlTable, 'comment', 0, 3, '192.168.1.10', time() - 60), 'Andere Mitglieder im selben WLAN warten nicht.');
    }

    #[Test]
    public function lockQueriesUseAccountOrEscapedIp(): void
    {
        $db = new RecordingDbHandler();
        pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 2, '10.0.0.1');
        pdl_lock_exists($db, $this->sqlTable, 'vote', 4, 0, "x' OR '1");
        pdl_lock_add($db, $this->sqlTable, 'vote', 4, 2, '10.0.0.1');

        $this->assertSame("SELECT file_id FROM pdl3_iplock WHERE art='vote' AND file_id='4' AND user_id='2' LIMIT 1", $db->queries[0]);
        $this->assertSame("SELECT file_id FROM pdl3_iplock WHERE art='vote' AND file_id='4' AND ip='x\\' OR \\'1' LIMIT 1", $db->queries[1]);
        $this->assertMatchesRegularExpression("/^INSERT INTO pdl3_iplock \\(ip,time,file_id,user_id,art\\) VALUES \\('10\\.0\\.0\\.1','\\d+','4','2','vote'\\)$/", $db->queries[2]);
    }

    // ==================== D3: Rücksprungziel ====================

    /**
     * @return array<string, array{0: mixed, 1: int}>
     */
    public static function backReleaseProvider(): array
    {
        return [
            'Zahl' => ['4', 4],
            'int' => [12, 12],
            'leer' => ['', 0],
            'null' => [null, 0],
            'null als Text' => ['0', 0],
            'negativ' => ['-3', 0],
            'Text' => ['abc', 0],
            'Zahl mit Rest' => ['5x', 0],
            'Array' => [['4'], 0],
            'zu groß' => ['99999999999', 0],
        ];
    }

    #[Test]
    #[DataProvider('backReleaseProvider')]
    public function backReleaseAcceptsOnlyPositiveIds(mixed $input, int $expected): void
    {
        $this->assertSame($expected, pdl_back_release($input));
    }

    #[Test]
    public function backReleaseQuery(): void
    {
        $this->assertSame('&back_release=4', pdl_back_release_query(4));
        $this->assertSame('', pdl_back_release_query(0));
    }
}
