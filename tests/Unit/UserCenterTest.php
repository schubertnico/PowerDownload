<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerDownload\Tests\Support\MockDbHandler;

/**
 * Benutzerbereich 3.6.0: Mail-Helfer, Passwortregeln, Herkunftsprüfung,
 * Bot-Fallen sowie die Module Registrierung, Anmelden, Passwort vergessen
 * und Profil.
 */
class UserCenterTest extends TestCase
{
    private string $incDir;

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_mail.inc.php';
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_captcha.inc.php';
    }

    protected function setUp(): void
    {
        $this->incDir = dirname(__DIR__, 2) . '/pdl-inc/';

        global $settings, $template, $db_handler, $sql_table, $user_details, $user_rights, $csrf_token,
               $submit, $nick, $email, $pw_new, $pw_new2, $pw_old, $homepage, $get_letter, $remind_code;

        $settings = [
            'script_file' => 'downloads.php?',
            'site_url' => 'https://downloads.example.org/pdl',
            'mail_fromname' => 'PowerDownload Test',
            'mail_fromaddr' => 'noreply@example.org',
            'captcha_enabled' => 'N',
        ];
        $template = [];
        $db_handler = new MockDbHandler();
        $sql_table = ['iplock' => 'pdl3_iplock', 'user' => 'pdl3_user', 'admin_log' => 'pdl3_admin_log'];
        $user_details = null;
        $user_rights = ['download' => 'Y'];
        $submit = 0;
        $nick = $email = $pw_new = $pw_new2 = $pw_old = $homepage = $get_letter = $remind_code = '';

        $_POST = [];
        $_GET = [];
        $_SESSION = ['csrf_token' => bin2hex(random_bytes(32))];
        $csrf_token = $_SESSION['csrf_token'];

        $GLOBALS['pdl_test_mails'] = [];
        pdl_mail_set_transport(static function (string $to, string $subject, string $body, array $headers): bool {
            $GLOBALS['pdl_test_mails'][] = ['to' => $to, 'subject' => $subject, 'body' => $body, 'headers' => $headers];
            return true;
        });
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
    }

    private function includeModule(string $file): string
    {
        global $settings, $template, $db_handler, $sql_table, $user_details, $user_rights, $csrf_token,
               $submit, $nick, $email, $pw_new, $pw_new2, $pw_old, $homepage, $get_letter, $remind_code;

        ob_start();
        include $this->incDir . $file;
        return (string) ob_get_clean();
    }

    /** @return array<int, array{to: string, subject: string, body: string, headers: array<string, string>}> */
    private function mails(): array
    {
        return $GLOBALS['pdl_test_mails'];
    }

    // ==================== pdl_mail.inc.php ====================

    #[Test]
    public function encodeHeaderLeavesAsciiAlone(): void
    {
        $this->assertSame('Ihre Registrierung bei PowerDownload', pdl_mail_encode_header('Ihre Registrierung bei PowerDownload'));
    }

    #[Test]
    public function encodeHeaderUsesRfc2047ForUmlauts(): void
    {
        $subject = 'Ihr Passwort bei PowerDownload wurde geändert – Größe, Übung, Straße';
        $encoded = pdl_mail_encode_header($subject);

        $this->assertMatchesRegularExpression('/^(=\?UTF-8\?B\?[A-Za-z0-9+\/=]+\?=)(\r\n =\?UTF-8\?B\?[A-Za-z0-9+\/=]+\?=)*$/', $encoded);
        foreach (explode("\r\n ", $encoded) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }
        $decoded = '';
        foreach (explode("\r\n ", $encoded) as $word) {
            $decoded .= base64_decode(substr($word, 10, -2));
        }
        $this->assertSame($subject, $decoded);
    }

    #[Test]
    public function encodeHeaderRemovesLineBreaks(): void
    {
        $this->assertSame('Betreff Bcc: x@example.org', pdl_mail_encode_header("Betreff\r\nBcc: x@example.org"));
    }

    #[Test]
    public function formatAddressQuotesAndEncodesNames(): void
    {
        $this->assertSame('PDL Automailer <a@example.org>', pdl_mail_format_address('a@example.org', 'PDL Automailer'));
        $this->assertSame('"Smith, John" <a@example.org>', pdl_mail_format_address('a@example.org', 'Smith, John'));
        $this->assertStringStartsWith('=?UTF-8?B?', pdl_mail_format_address('a@example.org', 'Müller'));
    }

    #[Test]
    public function siteBaseUrlUsesSettingFirst(): void
    {
        $this->assertSame('https://ex.org/dl/', pdl_site_base_url(['site_url' => 'https://ex.org/dl'], []));
        $this->assertSame('https://ex.org/dl/', pdl_site_base_url(['site_url' => 'https://ex.org/dl/downloads.php'], []));
    }

    #[Test]
    public function siteBaseUrlFallsBackToRequest(): void
    {
        $server = ['HTTP_HOST' => 'localhost:8246', 'SCRIPT_NAME' => '/downloads.php'];
        $this->assertSame('http://localhost:8246/', pdl_site_base_url([], $server));

        $admin = ['HTTP_HOST' => 'ex.org', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/sub/pdl-admin/makeletter.php'];
        $this->assertSame('https://ex.org/sub/', pdl_site_base_url(['site_url' => ''], $admin));

        $evil = ['HTTP_HOST' => 'evil.example/<x>', 'SERVER_NAME' => 'good.example', 'SCRIPT_NAME' => '/downloads.php'];
        $this->assertSame('http://good.example/', pdl_site_base_url([], $evil));
    }

    #[Test]
    public function scriptUrlBuildsAbsoluteLinks(): void
    {
        $server = ['HTTP_HOST' => 'localhost:8246', 'SCRIPT_NAME' => '/downloads.php'];
        $this->assertSame(
            'http://localhost:8246/downloads.php?usercenter=lost2&remind_code=abc',
            pdl_script_url(['script_file' => 'downloads.php?'], 'usercenter=lost2&remind_code=abc', $server)
        );
        $this->assertSame('http://localhost:8246/downloads.php', pdl_script_url(['script_file' => 'downloads.php?'], '', $server));
        $this->assertSame('https://x.org/a.php?b=1&usercenter=login', pdl_script_url(['script_file' => 'https://x.org/a.php?b=1&'], 'usercenter=login'));
    }

    #[Test]
    public function mailTemplateFallsBackToBuiltInText(): void
    {
        $this->assertStringContainsString('{link}', pdl_mail_template('mail_lost1', []));
        $this->assertStringContainsString('{link}', pdl_mail_template('mail_lost1', ['mail_lost1' => "  \n"]));
        $this->assertSame('Eigener Text', pdl_mail_template('mail_lost1', ['mail_lost1' => 'Eigener Text']));
    }

    #[Test]
    public function placeholdersNeverContainPasswords(): void
    {
        $vars = pdl_mail_placeholders(['script_file' => 'downloads.php?', 'site_url' => 'https://ex.org'], ['nick' => 'Anna']);
        $this->assertSame('', $vars['pw']);
        $this->assertSame('', $vars['new_pw']);
        $this->assertSame('Anna', $vars['user']);
        $this->assertSame('https://ex.org/downloads.php?usercenter=login', $vars['login_url']);
        $this->assertSame('60 Minuten', $vars['ttl']);
    }

    #[Test]
    public function sendMailSetsStandardHeaders(): void
    {
        $ok = pdl_send_mail('anna@example.org', 'Passwort zurücksetzen', "Zeile 1\r\nZeile 2", [
            'mail_fromname' => 'Downloads Müller',
            'mail_fromaddr' => 'downloads@example.org',
        ]);
        $this->assertTrue($ok);
        $mail = $this->mails()[0];
        $this->assertSame('1.0', $mail['headers']['MIME-Version']);
        $this->assertSame('text/plain; charset=UTF-8', $mail['headers']['Content-Type']);
        $this->assertSame('8bit', $mail['headers']['Content-Transfer-Encoding']);
        $this->assertStringEndsWith('<downloads@example.org>', $mail['headers']['From']);
        $this->assertStringStartsWith('=?UTF-8?B?', $mail['headers']['From']);
        $this->assertStringStartsWith('=?UTF-8?B?', $mail['subject']);
        $this->assertSame('Zeile 1' . PHP_EOL . 'Zeile 2', $mail['body']);
    }

    #[Test]
    public function sendMailRejectsInvalidRecipient(): void
    {
        $this->assertFalse(@pdl_send_mail("anna@example.org\r\nBcc: x@example.org", 'Test', 'Text', []));
        $this->assertSame([], $this->mails());
    }

    // ==================== pdl_csrf.inc.php ====================

    /** @return array<string, array{array<string, string>, bool}> */
    public static function crossSiteProvider(): array
    {
        return [
            'gleiche Seite' => [['HTTP_SEC_FETCH_SITE' => 'same-origin'], false],
            'Adresszeile' => [['HTTP_SEC_FETCH_SITE' => 'none'], false],
            'fremde Seite' => [['HTTP_SEC_FETCH_SITE' => 'cross-site'], true],
            'Subdomain' => [['HTTP_SEC_FETCH_SITE' => 'same-site'], true],
            'Origin passt' => [['HTTP_HOST' => 'localhost:8246', 'HTTP_ORIGIN' => 'http://localhost:8246'], false],
            'Origin fremd' => [['HTTP_HOST' => 'localhost:8246', 'HTTP_ORIGIN' => 'https://evil.example'], true],
            'Origin null' => [['HTTP_HOST' => 'localhost:8246', 'HTTP_ORIGIN' => 'null'], true],
            'Referer passt' => [['HTTP_HOST' => 'ex.org', 'HTTP_REFERER' => 'https://ex.org/downloads.php'], false],
            'Referer fremd' => [['HTTP_HOST' => 'ex.org', 'HTTP_REFERER' => 'https://evil.example/x'], true],
            'keine Angaben (curl)' => [['HTTP_HOST' => 'ex.org'], false],
        ];
    }

    /** @param array<string, string> $server */
    #[Test]
    #[DataProvider('crossSiteProvider')]
    public function requestIsCrossSite(array $server, bool $expected): void
    {
        $this->assertSame($expected, pdl_request_is_cross_site($server));
    }

    #[Test]
    public function logoutUrlContainsToken(): void
    {
        $this->assertSame('downloads.php?logout=1&csrf_token=' . $_SESSION['csrf_token'], pdl_logout_url('downloads.php?'));
    }

    /** @return array<string, array{string, string, string|null}> */
    public static function passwordProvider(): array
    {
        return [
            'leer' => ['', '', 'Bitte geben Sie ein Passwort ein.'],
            'ohne Wiederholung' => ['abcd1234', '', 'Bitte wiederholen Sie das Passwort im zweiten Feld.'],
            'ungleich' => ['abcd1234', 'abcd1235', 'Passwort und Wiederholung stimmen nicht überein. Bitte geben Sie beide erneut ein.'],
            'zu kurz' => ['abc123', 'abc123', 'Das Passwort muss mindestens 8 Zeichen lang sein.'],
            'ohne Ziffer' => ['abcdefgh', 'abcdefgh', 'Das Passwort muss mindestens einen Buchstaben und eine Ziffer enthalten.'],
            'ohne Buchstabe' => ['12345678', '12345678', 'Das Passwort muss mindestens einen Buchstaben und eine Ziffer enthalten.'],
            'Umlaut zählt als Buchstabe' => ['1234567ä', '1234567ä', null],
            'gültig' => ['Audit2026pw', 'Audit2026pw', null],
        ];
    }

    #[Test]
    #[DataProvider('passwordProvider')]
    public function validatePassword(string $pw, string $repeat, ?string $error): void
    {
        $this->assertSame($error === null ? [] : [$error], pdl_validate_password($pw, $repeat));
    }

    #[Test]
    public function passwordVerifyStoredSupportsBcryptAndMd5(): void
    {
        $this->assertTrue(pdl_password_verify_stored('geheim123', password_hash('geheim123', PASSWORD_DEFAULT)));
        $this->assertTrue(pdl_password_verify_stored('geheim123', md5('geheim123')));
        $this->assertFalse(pdl_password_verify_stored('falsch', md5('geheim123')));
        $this->assertFalse(pdl_password_verify_stored('', ''));
    }

    // ==================== pdl_captcha.inc.php ====================

    #[Test]
    public function spamCheckDetectsHoneypotAndSpeed(): void
    {
        $_POST = ['pdl_website' => 'http://spam.example', 'pdl_ts' => (string) (time() - 10)];
        $this->assertSame('honeypot', pdl_spam_check());
        $_POST = ['pdl_website' => '', 'pdl_ts' => (string) time()];
        $this->assertSame('too_fast', pdl_spam_check());
        $_POST = ['pdl_website' => '', 'pdl_ts' => (string) (time() - pdl_spam_min_seconds())];
        $this->assertSame('ok', pdl_spam_check());
    }

    // ==================== Registrierung ====================

    private function prepareRegistration(int $tsOffset = 10): void
    {
        global $submit, $email, $pw_new, $pw_new2, $get_letter;
        $submit = 1;
        $email = 'anna@example.org';
        $pw_new = 'Audit2026pw';
        $pw_new2 = 'Audit2026pw';
        $get_letter = '';
        $_POST = ['nick' => 'Anna', 'pdl_website' => '', 'pdl_ts' => (string) (time() - $tsOffset)];
    }

    #[Test]
    public function registerTooFastShowsHintAndKeepsValues(): void
    {
        global $db_handler;
        $this->prepareRegistration(0);
        $db_handler->addResult([]); // DELETE iplock
        $db_handler->addResult([['c' => 0]]);

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('Bitte warten Sie einen Moment und senden Sie das Formular erneut.', $output);
        $this->assertStringContainsString('value="Anna"', $output);
        $this->assertStringNotContainsString('erfolgreich', $output);
        $this->assertSame(2, $db_handler->querys, 'kein INSERT ohne bestandene Prüfung');
    }

    #[Test]
    public function registerHoneypotFakesSuccessSilently(): void
    {
        global $db_handler;
        $this->prepareRegistration();
        $_POST['pdl_website'] = 'http://spam.example';

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('Registrierung erfolgreich', $output);
        $this->assertSame(0, $db_handler->querys);
        $this->assertSame([], $this->mails());
    }

    #[Test]
    public function registerRateLimitShowsWarning(): void
    {
        global $db_handler;
        $this->prepareRegistration();
        $db_handler->addResult([]);
        $db_handler->addResult([['c' => 5]]);

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('Zu viele Registrierungen.', $output);
        $this->assertStringNotContainsString('<form', $output);
    }

    #[Test]
    public function registerWrongCaptchaShowsError(): void
    {
        global $db_handler, $settings;
        $settings['captcha_enabled'] = 'Y';
        $this->prepareRegistration();
        $_SESSION['pdl_captcha_answer'] = 7;
        $_POST['pdl_captcha'] = '8';
        $db_handler->addResult([]);
        $db_handler->addResult([['c' => 0]]);

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('Das Ergebnis der Rechenaufgabe stimmt nicht.', $output);
        $this->assertStringContainsString('id="pdlCaptcha"', $output);
    }

    #[Test]
    public function registerFormHasNewsletterUnchecked(): void
    {
        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertMatchesRegularExpression('/<input type="checkbox" id="pdlRegGetLetter"[^>]*class="form-check-input" aria-describedby/', $output);
        $this->assertDoesNotMatchRegularExpression('/id="pdlRegGetLetter"[^>]*checked/', $output);
        $this->assertStringContainsString('action="downloads.php?usercenter=register"', $output);
    }

    #[Test]
    public function registerSuccessStoresMemberGroupAndSendsMail(): void
    {
        global $db_handler;
        $this->prepareRegistration();
        $db_handler->addResult([]); // DELETE iplock
        $db_handler->addResult([['c' => 0]]); // Limit
        $db_handler->addResult([]); // nick
        $db_handler->addResult([]); // email
        $db_handler->addResult([]); // INSERT user
        $db_handler->addResult([]); // INSERT iplock

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('Registrierung erfolgreich', $output);
        $this->assertStringContainsString('id="pdlRegLogin"', $output);

        $mail = $this->mails()[0];
        $this->assertSame('Ihre Registrierung bei PowerDownload', $mail['subject']);
        $this->assertStringContainsString('vielen Dank für Ihre Registrierung bei PowerDownload', $mail['body']);
        $this->assertStringContainsString('https://downloads.example.org/pdl/downloads.php?usercenter=login', $mail['body']);
        $this->assertStringNotContainsString('Audit2026pw', $mail['body']);
    }

    // ==================== Passwort vergessen ====================

    #[Test]
    public function lostTooFastShowsHint(): void
    {
        global $submit, $email, $db_handler;
        $submit = 1;
        $email = 'anna@example.org';
        $_POST = ['pdl_website' => '', 'pdl_ts' => (string) time()];
        $db_handler->addResult([]);
        $db_handler->addResult([['c' => 0]]);

        $output = $this->includeModule('pdl_ulost.modul.php');
        $this->assertStringContainsString('Bitte warten Sie einen Moment', $output);
        $this->assertStringContainsString('value="anna@example.org"', $output);
        $this->assertSame([], $this->mails());
    }

    #[Test]
    public function lostRateLimitShowsWarning(): void
    {
        global $submit, $email, $db_handler;
        $submit = 1;
        $email = 'anna@example.org';
        $db_handler->addResult([]);
        $db_handler->addResult([['c' => 3]]);

        $output = $this->includeModule('pdl_ulost.modul.php');
        $this->assertStringContainsString('Zu viele Anforderungen.', $output);
    }

    #[Test]
    public function lostInvalidEmailShowsError(): void
    {
        global $submit, $email, $db_handler;
        $submit = 1;
        $email = 'keine-adresse';
        $db_handler->addResult([]);
        $db_handler->addResult([['c' => 0]]);

        $output = $this->includeModule('pdl_ulost.modul.php');
        $this->assertStringContainsString('Die E-Mail-Adresse ist ungültig.', $output);
    }

    #[Test]
    public function lost2EnforcesPasswordRules(): void
    {
        global $submit, $remind_code, $pw_new, $pw_new2, $db_handler;
        $submit = 1;
        $remind_code = str_repeat('ab', 16);
        $pw_new = 'abcdefgh';
        $pw_new2 = 'abcdefgh';
        $db_handler->addResult([['user_id' => 4, 'nick' => 'Anna', 'email' => 'anna@example.org']]);

        $output = $this->includeModule('pdl_ulost2.modul.php');
        $this->assertStringContainsString('mindestens einen Buchstaben und eine Ziffer', $output);
        $this->assertStringContainsString(pdl_password_hint(), $output);
    }

    #[Test]
    public function lost2SuccessSendsChangedMail(): void
    {
        global $submit, $remind_code, $pw_new, $pw_new2, $db_handler;
        $submit = 1;
        $remind_code = str_repeat('ab', 16);
        $pw_new = 'Neu2026pw';
        $pw_new2 = 'Neu2026pw';
        $db_handler->addResult([['user_id' => 4, 'nick' => 'Anna', 'email' => 'anna@example.org']]);
        $db_handler->addResult([]); // UPDATE

        $output = $this->includeModule('pdl_ulost2.modul.php');
        $this->assertStringContainsString('Ihr neues Passwort wurde gespeichert.', $output);
        $this->assertStringContainsString('id="pdlLost2Login"', $output);
        $mail = $this->mails()[0];
        $this->assertStringStartsWith('=?UTF-8?B?', $mail['subject']);
        $this->assertStringContainsString('wurde soeben geändert', $mail['body']);
    }

    // ==================== Anmelden ====================

    #[Test]
    public function loginShowsSuccessAfterRedirect(): void
    {
        global $user_details;
        $user_details = ['user_id' => 4, 'nick' => 'Anna'];
        $_GET = ['login_ok' => '1'];

        $output = $this->includeModule('pdl_ulogin.modul.php');
        $this->assertStringContainsString('Sie sind jetzt angemeldet.', $output);
        $this->assertStringContainsString('id="pdlLogoutForm"', $output);
    }

    #[Test]
    public function loginShowsLogoutFeedback(): void
    {
        $_GET = ['logout_ok' => '1'];
        $output = $this->includeModule('pdl_ulogin.modul.php');
        $this->assertStringContainsString('Sie wurden abgemeldet.', $output);
        $this->assertStringContainsString('id="pdlLoginForm"', $output);
    }

    #[Test]
    public function loginAsksBeforeForeignLogout(): void
    {
        global $user_details;
        $user_details = ['user_id' => 4, 'nick' => 'Anna'];
        $_GET = ['logout_confirm' => '1'];

        $output = $this->includeModule('pdl_ulogin.modul.php');
        $this->assertStringContainsString('Möchten Sie sich als <strong>Anna</strong> abmelden?', $output);
        $this->assertStringContainsString('name="logout" value="1"', $output);
    }

    // ==================== Profil ====================

    /** @return array<string, mixed> */
    private function profileUser(): array
    {
        return [
            'user_id' => 4, 'nick' => 'Anna', 'email' => 'anna@example.org', 'homepage' => '',
            'get_letter' => 'N', 'passwort' => password_hash('Audit2026pw', PASSWORD_DEFAULT),
        ];
    }

    #[Test]
    public function profileKeepsInputOnError(): void
    {
        global $user_details, $submit, $pw_old, $email, $homepage;
        $user_details = $this->profileUser();
        $submit = 1;
        $pw_old = 'falsch';
        $email = 'anna@example.org';
        $homepage = 'https://anna.example.org';

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('Das aktuelle Passwort ist falsch.', $output);
        $this->assertStringContainsString('value="https://anna.example.org"', $output);
        $this->assertStringContainsString('id="pdlProfilForm"', $output);
    }

    #[Test]
    public function profileShowsSavedNoticeAfterRedirect(): void
    {
        global $user_details;
        $user_details = $this->profileUser();
        $_GET = ['saved' => '2'];

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('Ihr Profil wurde gespeichert.', $output);
        $this->assertStringContainsString('auf anderen Geräten wurden Sie abgemeldet', $output);
        $this->assertStringContainsString('id="pdlProfilForm"', $output);
    }

    #[Test]
    public function profileDeleteWithWrongPasswordShowsErrorAtDeleteCard(): void
    {
        global $user_details, $db_handler;
        $user_details = $this->profileUser();
        $_POST = ['delete_account' => '1', 'delete_pw' => 'falsch', 'delete_confirm' => '1', 'csrf_token' => $_SESSION['csrf_token']];

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('Das eingegebene Passwort ist nicht korrekt.', $output);
        $this->assertSame(0, $db_handler->querys);
        $this->assertStringContainsString('#pdlProfileDelete"', $output);
    }

    #[Test]
    public function profileDeleteRemovesAccountWithoutPersonalDataInLog(): void
    {
        global $user_details, $db_handler;
        $user_details = $this->profileUser();
        $_POST = ['delete_account' => '1', 'delete_pw' => 'Audit2026pw', 'delete_confirm' => '1', 'csrf_token' => $_SESSION['csrf_token']];
        $db_handler->addResult([]); // DELETE

        $log = tempnam(sys_get_temp_dir(), 'pdl_log_');
        $previous = ini_set('error_log', (string) $log);
        try {
            $output = $this->includeModule('pdl_uprofil.modul.php');
        } finally {
            ini_set('error_log', (string) $previous);
        }
        $logged = (string) file_get_contents((string) $log);
        @unlink((string) $log);

        $this->assertStringContainsString('Ihr Konto wurde gelöscht.', $output);
        $this->assertStringContainsString('pdl self_delete user_id=4', $logged);
        $this->assertStringNotContainsString('anna@example.org', $logged);
        $this->assertStringNotContainsString('Anna', $logged);
    }
}
