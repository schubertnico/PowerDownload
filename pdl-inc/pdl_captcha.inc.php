<?php
declare(strict_types=1);

/**
 * PowerDownload - Rechenaufgabe gegen automatisierte Eintragungen
 * (Registrierung, Passwort vergessen). Einstellung „captcha_enabled“.
 */

if (!function_exists('pdl_captcha_active')) {
    function pdl_captcha_active(array $settings): bool
    {
        return (($settings['captcha_enabled'] ?? 'N') === 'Y');
    }
}

if (!function_exists('pdl_captcha_session')) {
    /**
     * Stellt die Sitzung bereit (ohne Warnung, wenn bereits Ausgabe erfolgt ist).
     */
    function pdl_captcha_session(): void
    {
        if (function_exists('pdl_session_ensure')) {
            pdl_session_ensure();
            return;
        }
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (!is_array($_SESSION ?? null)) {
            $_SESSION = [];
        }
    }
}

if (!function_exists('pdl_captcha_render')) {
    /**
     * Erzeugt das Eingabefeld mit einer einfachen Addition (zwei Zahlen
     * im Bereich 1–9) und speichert die erwartete Lösung in der Sitzung.
     * Gibt den fertigen HTML-String zurück.
     */
    function pdl_captcha_render(array $settings): string
    {
        if (!pdl_captcha_active($settings)) {
            return '';
        }
        pdl_captcha_session();
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $_SESSION['pdl_captcha_answer'] = $a + $b;
        return '<div class="mb-3">'
            . '<label for="pdlCaptcha" class="form-label">Bitte lösen Sie folgende Rechenaufgabe</label>'
            . '<div class="input-group">'
            . '<span class="input-group-text">' . $a . ' + ' . $b . ' =</span>'
            . '<input type="number" inputmode="numeric" min="0" max="100" id="pdlCaptcha" name="pdl_captcha" class="form-control" required autocomplete="off" aria-describedby="pdlCaptchaHelp">'
            . '</div>'
            . '<div id="pdlCaptchaHelp" class="form-text">Die kleine Rechenaufgabe verhindert automatisierte Eintragungen.</div>'
            . '</div>';
    }
}

if (!function_exists('pdl_captcha_verify')) {
    /**
     * Vergleicht $_POST['pdl_captcha'] mit der zuvor in der Sitzung
     * gespeicherten Lösung. Bei deaktivierter Rechenaufgabe immer true.
     * Nach jeder Prüfung wird die Lösung gelöscht (nur ein Versuch).
     */
    function pdl_captcha_verify(array $settings): bool
    {
        if (!pdl_captcha_active($settings)) {
            return true;
        }
        pdl_captcha_session();
        $expected = $_SESSION['pdl_captcha_answer'] ?? null;
        unset($_SESSION['pdl_captcha_answer']);
        if ($expected === null) {
            return false;
        }
        $given_raw = $_POST['pdl_captcha'] ?? '';
        $given = is_string($given_raw) ? trim($given_raw) : '';
        if ($given === '' || !ctype_digit($given)) {
            return false;
        }
        return ((int)$given === (int)$expected);
    }
}

if (!function_exists('pdl_spam_fields')) {
    /**
     * Unsichtbare Fallen gegen Bots: Zeitstempel (pdl_ts) und ein Feld, das
     * Menschen nicht sehen und deshalb leer lassen (pdl_website).
     */
    function pdl_spam_fields(): string
    {
        return '<input type="hidden" name="pdl_ts" value="' . time() . '">'
            . '<div class="visually-hidden" aria-hidden="true">'
            . '<label for="pdl_website">Website (bitte freilassen)</label>'
            . '<input type="text" id="pdl_website" name="pdl_website" tabindex="-1" autocomplete="off" value="">'
            . '</div>';
    }
}

if (!function_exists('pdl_spam_min_seconds')) {
    /**
     * Mindestzeit zwischen Anzeige und Absenden eines Formulars in Sekunden.
     */
    function pdl_spam_min_seconds(): int
    {
        return 2;
    }
}

if (!function_exists('pdl_spam_check')) {
    /**
     * Prüft die Fallen aus pdl_spam_fields().
     *
     * @return string 'honeypot' (verstecktes Feld ausgefüllt: still abweisen),
     *                'too_fast' (schneller als pdl_spam_min_seconds(): Hinweis zeigen)
     *                oder 'ok'
     */
    function pdl_spam_check(): string
    {
        $honeypot = $_POST['pdl_website'] ?? '';
        if (!is_string($honeypot) || $honeypot !== '') {
            return 'honeypot';
        }
        $ts_raw = $_POST['pdl_ts'] ?? 0;
        $ts = is_scalar($ts_raw) ? (int) $ts_raw : 0;
        if ($ts > 0 && (time() - $ts) < pdl_spam_min_seconds()) {
            return 'too_fast';
        }
        return 'ok';
    }
}

if (!function_exists('pdl_spam_too_fast_error')) {
    /**
     * Hinweis, wenn ein Formular zu schnell abgeschickt wurde.
     */
    function pdl_spam_too_fast_error(): string
    {
        return 'Bitte warten Sie einen Moment und senden Sie das Formular erneut.';
    }
}

if (!function_exists('pdl_rate_limit_ip')) {
    /**
     * IP-Adresse in der Form, in der sie in pdl3_iplock.ip (varchar 32) passt.
     */
    function pdl_rate_limit_ip(string $ip): string
    {
        return substr($ip, 0, 32);
    }
}

if (!function_exists('pdl_rate_limit_count')) {
    /**
     * Entfernt abgelaufene Einträge der Art $art aus pdl3_iplock und zählt
     * die verbliebenen Einträge der IP im Zeitfenster.
     *
     * @param array<string, string> $sql_table
     */
    function pdl_rate_limit_count($db_handler, array $sql_table, string $art, string $ip, int $window = 3600): int
    {
        $art_safe = $db_handler->sql_escape_string($art);
        $db_handler->sql_query("DELETE FROM " . $sql_table['iplock'] . " WHERE art='" . $art_safe . "' AND time<" . (time() - $window));
        $res = $db_handler->sql_query("SELECT COUNT(*) AS c FROM " . $sql_table['iplock'] . " WHERE ip='" . $db_handler->sql_escape_string(pdl_rate_limit_ip($ip)) . "' AND art='" . $art_safe . "'");
        $row = $db_handler->sql_fetch_array($res);
        return (int) ($row['c'] ?? 0);
    }
}

if (!function_exists('pdl_rate_limit_record')) {
    /**
     * Zählt einen Vorgang der Art $art für die IP (pdl3_iplock).
     *
     * @param array<string, string> $sql_table
     */
    function pdl_rate_limit_record($db_handler, array $sql_table, string $art, string $ip): void
    {
        $db_handler->sql_query("INSERT INTO " . $sql_table['iplock'] . " (ip,time,file_id,user_id,art) VALUES ('"
            . $db_handler->sql_escape_string(pdl_rate_limit_ip($ip)) . "','" . time() . "',0,0,'"
            . $db_handler->sql_escape_string($art) . "')");
    }
}

if (!function_exists('pdl_captcha_error')) {
    /**
     * Fehlermeldung bei falsch gelöster Rechenaufgabe.
     */
    function pdl_captcha_error(): string
    {
        return 'Das Ergebnis der Rechenaufgabe stimmt nicht. Bitte lösen Sie die neue Aufgabe und senden Sie das Formular erneut.';
    }
}
