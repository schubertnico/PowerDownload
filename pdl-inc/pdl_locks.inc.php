<?php

/**
 * PowerDownload - Sperren (pdl3_iplock) und Rücksprung nach der Anmeldung
 *
 * - Bewertung (eine je Release in 24 Stunden), Download-Zähler (einmal je
 *   Datei und Stunde) und Kommentar-Pause (eine Minute) gelten für
 *   Angemeldete je Konto, für Gäste je IP-Adresse. So kann im Vereins-WLAN,
 *   wo sich viele Mitglieder eine Adresse teilen, jedes Mitglied selbst
 *   bewerten (pdl_lock_exists, pdl_lock_add).
 * - Anmelde-Fehlversuche zählen je IP-Adresse, gespeichert wird zusätzlich
 *   das versuchte Konto (0 = unbekannter Benutzername). Eine erfolgreiche
 *   Anmeldung löscht nur die Fehlversuche dieses Kontos von dieser Adresse.
 *   Wer erst fremde Konten durchprobiert und sich dann am eigenen Konto
 *   anmeldet, setzt die Sperre damit nicht zurück (pdl_login_failures,
 *   pdl_login_failure_add, pdl_login_failures_clear).
 * - Rücksprungziel nach der Anmeldung: Release, von dem aus sich ein Gast
 *   anmelden möchte (Parameter back_release, pdl_back_release).
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

if (!function_exists('pdl_lock_owner_sql')) {
    /**
     * Bedingung für den Besitzer einer Sperre: angemeldet (user_id > 0) je
     * Konto, sonst je IP-Adresse. Gäste sehen dabei auch Einträge, die
     * Angemeldete von derselben Adresse aus gesetzt haben. Wer bewertet, sich
     * abmeldet und als Gast noch einmal bewertet, zählt also nicht doppelt.
     *
     * @param mixed $db_handler Datenbank-Klasse (pdl_db_class)
     */
    function pdl_lock_owner_sql($db_handler, int $user_id, string $ip): string
    {
        if ($user_id > 0) {
            return "user_id='" . $db_handler->sql_escape_int($user_id) . "'";
        }
        return "ip='" . $db_handler->sql_escape_string($ip) . "'";
    }
}

if (!function_exists('pdl_lock_exists')) {
    /**
     * Gibt es einen Eintrag der Art $art (vote, download, comment) für den
     * Besucher? $file_id ist Release bzw. Datei; 0 prüft alle Einträge der
     * Art. $since > 0 berücksichtigt nur Einträge nach diesem Zeitpunkt.
     *
     * @param mixed $db_handler Datenbank-Klasse (pdl_db_class)
     * @param array<string, string> $sql_table
     */
    function pdl_lock_exists($db_handler, array $sql_table, string $art, int $file_id, int $user_id, string $ip, int $since = 0): bool
    {
        $sql = "SELECT file_id FROM " . $sql_table['iplock']
            . " WHERE art='" . $db_handler->sql_escape_string($art) . "'"
            . ($file_id > 0 ? " AND file_id='" . $db_handler->sql_escape_int($file_id) . "'" : '')
            . " AND " . pdl_lock_owner_sql($db_handler, $user_id, $ip)
            . ($since > 0 ? " AND time>" . $since : '')
            . " LIMIT 1";
        return $db_handler->sql_num_rows($db_handler->sql_query($sql)) > 0;
    }
}

if (!function_exists('pdl_lock_add')) {
    /**
     * Speichert einen Eintrag mit IP-Adresse und Konto (0 = Gast).
     *
     * @param mixed $db_handler Datenbank-Klasse (pdl_db_class)
     * @param array<string, string> $sql_table
     */
    function pdl_lock_add($db_handler, array $sql_table, string $art, int $file_id, int $user_id, string $ip): bool
    {
        return $db_handler->sql_query(
            "INSERT INTO " . $sql_table['iplock'] . " (ip,time,file_id,user_id,art) VALUES ('"
            . $db_handler->sql_escape_string($ip) . "','" . time() . "','"
            . $db_handler->sql_escape_int(max(0, $file_id)) . "','"
            . $db_handler->sql_escape_int(max(0, $user_id)) . "','"
            . $db_handler->sql_escape_string($art) . "')"
        ) !== false;
    }
}

if (!function_exists('pdl_login_failures')) {
    /**
     * Anzahl der Anmelde-Fehlversuche dieser IP-Adresse in den letzten
     * $window Sekunden, über alle Konten. Ältere Einträge werden gelöscht.
     *
     * @param mixed $db_handler Datenbank-Klasse (pdl_db_class)
     * @param array<string, string> $sql_table
     */
    function pdl_login_failures($db_handler, array $sql_table, string $ip, int $window = 900): int
    {
        $since = time() - $window;
        $db_handler->sql_query("DELETE FROM " . $sql_table['iplock'] . " WHERE art='login' AND time<" . $since);
        $row = $db_handler->sql_fetch_array($db_handler->sql_query(
            "SELECT COUNT(*) AS c FROM " . $sql_table['iplock']
            . " WHERE art='login' AND ip='" . $db_handler->sql_escape_string($ip) . "' AND time>" . $since
        ));
        return (int) (is_array($row) ? ($row['c'] ?? 0) : 0);
    }
}

if (!function_exists('pdl_login_failure_add')) {
    /**
     * Speichert einen Fehlversuch mit dem versuchten Konto (0 = unbekannt).
     *
     * @param mixed $db_handler Datenbank-Klasse (pdl_db_class)
     * @param array<string, string> $sql_table
     */
    function pdl_login_failure_add($db_handler, array $sql_table, string $ip, int $user_id): void
    {
        pdl_lock_add($db_handler, $sql_table, 'login', 0, $user_id, $ip);
    }
}

if (!function_exists('pdl_login_failures_clear')) {
    /**
     * Nach erfolgreicher Anmeldung: nur die Fehlversuche dieses Kontos von
     * dieser IP-Adresse löschen. Fehlversuche gegen andere Konten zählen weiter.
     *
     * @param mixed $db_handler Datenbank-Klasse (pdl_db_class)
     * @param array<string, string> $sql_table
     */
    function pdl_login_failures_clear($db_handler, array $sql_table, string $ip, int $user_id): void
    {
        if ($user_id < 1) {
            return;
        }
        $db_handler->sql_query(
            "DELETE FROM " . $sql_table['iplock'] . " WHERE art='login' AND ip='" . $db_handler->sql_escape_string($ip)
            . "' AND user_id='" . $db_handler->sql_escape_int($user_id) . "'"
        );
    }
}

if (!function_exists('pdl_back_release')) {
    /**
     * Release-ID aus dem Parameter back_release (Rücksprung nach der
     * Anmeldung); 0, wenn keiner angegeben oder ungültig ist.
     */
    function pdl_back_release(mixed $value): int
    {
        if (!is_scalar($value) || preg_match('/^\d{1,10}$/', (string) $value) !== 1) {
            return 0;
        }
        $id = (int) $value;
        return $id > 0 && $id <= 2147483647 ? $id : 0;
    }
}

if (!function_exists('pdl_back_release_query')) {
    /**
     * Anhang „&back_release=N“ für Adressen zur Anmeldung (leer bei 0).
     * In HTML-Attributen mit htmlspecialchars() ausgeben.
     */
    function pdl_back_release_query(int $release_id): string
    {
        return $release_id > 0 ? '&back_release=' . $release_id : '';
    }
}
