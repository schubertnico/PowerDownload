<?php

/**
 * PowerDownload - CSRF-Schutz und weitere Sicherheits-Helfer
 *
 * - CSRF-Token in der Sitzung (csrf_token, csrf_verify, csrf_input)
 * - Herkunftsprüfung über Fetch-Metadata, Origin und Referer
 *   (pdl_request_is_cross_site), z. B. für Anmelden und Abmelden
 * - Abmelde-Link mit Token (pdl_logout_url)
 * - Einheitliche Passwortregeln (pdl_validate_password, pdl_password_hint)
 *   und Passwortprüfung inkl. alter MD5-Hashes (pdl_password_verify_stored)
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

if (!function_exists('pdl_session_ensure')) {
    /**
     * Startet die Sitzung, falls nötig und noch möglich. Sind bereits
     * Kopfzeilen gesendet (CLI, Tests), wird nur $_SESSION bereitgestellt.
     */
    function pdl_session_ensure(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (!is_array($_SESSION ?? null)) {
            $_SESSION = [];
        }
    }
}

/**
 * Returns current CSRF token; creates one if missing.
 */
function csrf_token(): string
{
    pdl_session_ensure();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a submitted token against the session token.
 */
function csrf_verify(?string $token): bool
{
    pdl_session_ensure();
    $session_token = $_SESSION['csrf_token'] ?? '';
    if (!is_string($session_token) || $session_token === '' || $token === null || $token === '') {
        return false;
    }
    return hash_equals($session_token, $token);
}

/**
 * Returns HTML hidden input with current token.
 */
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

if (!function_exists('pdl_request_is_cross_site')) {
    /**
     * Liefert true, wenn die Anfrage nachweislich von einer anderen Website
     * ausgelöst wurde. Ausgewertet werden „Sec-Fetch-Site“ (alle aktuellen
     * Browser), ersatzweise „Origin“ und danach „Referer“. Fehlen alle drei
     * (z. B. curl, Skripte), gibt es keinen Hinweis auf eine fremde Seite
     * und das Ergebnis ist false.
     *
     * @param array<string, mixed>|null $server Standard: $_SERVER
     */
    function pdl_request_is_cross_site(?array $server = null): bool
    {
        $server = $server ?? $_SERVER;

        $fetch_site = strtolower(trim((string) ($server['HTTP_SEC_FETCH_SITE'] ?? '')));
        if ($fetch_site !== '') {
            // „none“ = vom Benutzer selbst geöffnet (Adresszeile, Lesezeichen)
            return !in_array($fetch_site, ['same-origin', 'none'], true);
        }

        $host = strtolower(trim((string) ($server['HTTP_HOST'] ?? '')));
        foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $key) {
            $value = trim((string) ($server[$key] ?? ''));
            if ($value === '') {
                continue;
            }
            if (strtolower($value) === 'null') {
                return true;
            }
            $parts = parse_url($value);
            if (!is_array($parts) || empty($parts['host'])) {
                return true;
            }
            $authority = strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
            return $host !== '' && $authority !== $host;
        }

        return false;
    }
}

if (!function_exists('pdl_logout_url')) {
    /**
     * Abmelde-Link mit CSRF-Token, z. B. für die Navigation:
     * htmlspecialchars(pdl_logout_url($settings['script_file'])).
     * Ein Abmelde-Link ohne Token wirkt nur, wenn er von dieser Seite aus
     * aufgerufen wird; sonst fragt PowerDownload nach.
     */
    function pdl_logout_url(string $script_file): string
    {
        return $script_file . 'logout=1&csrf_token=' . rawurlencode(csrf_token());
    }
}

if (!function_exists('pdl_password_hint')) {
    /**
     * Hilfetext zu den Passwortregeln (Registrierung, Profil, neues Passwort).
     */
    function pdl_password_hint(): string
    {
        return 'Mindestens 8 Zeichen, darunter mindestens ein Buchstabe und eine Ziffer.';
    }
}

if (!function_exists('pdl_validate_password')) {
    /**
     * Prüft ein neues Passwort samt Wiederholung nach den gemeinsamen Regeln:
     * mindestens 8 Zeichen, mindestens ein Buchstabe und eine Ziffer.
     *
     * @return list<string> Fehlermeldungen (leer = in Ordnung)
     */
    function pdl_validate_password(string $password, string $repeat): array
    {
        if ($password === '') {
            return ['Bitte geben Sie ein Passwort ein.'];
        }
        if ($repeat === '') {
            return ['Bitte wiederholen Sie das Passwort im zweiten Feld.'];
        }
        if ($password !== $repeat) {
            return ['Passwort und Wiederholung stimmen nicht überein. Bitte geben Sie beide erneut ein.'];
        }
        $length = preg_match_all('/./su', $password);
        if ($length === false) {
            $length = strlen($password);
        }
        if ($length < 8) {
            return ['Das Passwort muss mindestens 8 Zeichen lang sein.'];
        }
        if (preg_match('/\pL/u', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
            return ['Das Passwort muss mindestens einen Buchstaben und eine Ziffer enthalten.'];
        }
        return [];
    }
}

if (!function_exists('pdl_password_verify_stored')) {
    /**
     * Vergleicht ein eingegebenes Passwort mit dem gespeicherten Hash
     * (password_hash oder alter MD5-Hash aus PowerDownload 2.x).
     */
    function pdl_password_verify_stored(string $password, string $stored): bool
    {
        if ($stored === '') {
            return false;
        }
        $info = password_get_info($stored);
        if (($info['algo'] ?? null) !== null && ($info['algo'] ?? 0) !== 0) {
            return password_verify($password, $stored);
        }
        if (strlen($stored) === 32 && ctype_xdigit($stored)) {
            return hash_equals(strtolower($stored), md5($password));
        }
        return false;
    }
}
