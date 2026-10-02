<?php

/**
 * PowerDownload - E-Mail-Versand und absolute Links
 *
 * Gemeinsamer Helfer für alle Mails (Registrierung, Passwort vergessen,
 * Passwort geändert, Newsletter):
 *
 *   pdl_send_mail(string $to, string $subject, string $body,
 *                 ?array $settings = null, array $extra_headers = []): bool
 *
 * - $to       Empfängeradresse (eine Adresse, ohne Anzeigenamen)
 * - $subject  Betreff als normaler UTF-8-Text; die Kodierung nach RFC 2047
 *             übernimmt der Helfer (ohne mbstring-Pflicht)
 * - $body     Text der Mail als UTF-8 (text/plain), Zeilenumbrüche beliebig
 * - $settings Einstellungen (mail_fromname, mail_fromaddr, sitename); null
 *             verwendet das globale $settings aus pdl_header.inc.php
 * - $extra_headers zusätzliche Kopfzeilen, z. B. ['List-Unsubscribe' => '<…>']
 * - Rückgabe  true, wenn die Mail an das Mailsystem übergeben wurde
 *
 * Gesetzt werden immer From, MIME-Version, Content-Type (text/plain;
 * charset=UTF-8) und Content-Transfer-Encoding (8bit). Zeilenumbrüche in
 * Kopfzeilenwerten werden entfernt (Schutz vor Header-Injection).
 *
 * Für Tests lässt sich der Versand mit pdl_mail_set_transport() umleiten.
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

if (!function_exists('pdl_password_reset_ttl')) {
    /**
     * Gültigkeit eines Links aus „Passwort vergessen“ in Sekunden.
     * Einzige Quelle für Speicherung, Meldung und Mail-Text.
     */
    function pdl_password_reset_ttl(): int
    {
        return 3600;
    }
}

if (!function_exists('pdl_password_reset_ttl_text')) {
    /**
     * Gültigkeit als lesbarer Text, z. B. „60 Minuten“.
     */
    function pdl_password_reset_ttl_text(): string
    {
        $minutes = intdiv(pdl_password_reset_ttl(), 60);
        if ($minutes % 60 === 0 && $minutes >= 120) {
            return ($minutes / 60) . ' Stunden';
        }
        return $minutes === 1 ? '1 Minute' : $minutes . ' Minuten';
    }
}

if (!function_exists('pdl_mail_set_transport')) {
    /**
     * Ersetzt den Versand über mail() durch eine eigene Funktion (Tests,
     * eigene Mailer). Signatur des Transports:
     * function (string $to, string $encodedSubject, string $body, array<string, string> $headers): bool
     * null stellt den Versand über mail() wieder her.
     */
    function pdl_mail_set_transport(?callable $transport): void
    {
        $GLOBALS['pdl_mail_transport'] = $transport;
    }
}

if (!function_exists('pdl_mail_header_value')) {
    /**
     * Entfernt Zeilenumbrüche und Steuerzeichen aus einem Kopfzeilenwert.
     */
    function pdl_mail_header_value(string $value): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value));
    }
}

if (!function_exists('pdl_mail_encode_header')) {
    /**
     * Kodiert einen Kopfzeilentext nach RFC 2047 (UTF-8, Base64), sobald er
     * Nicht-ASCII-Zeichen enthält. Lange Texte werden in mehrere „encoded
     * words“ zerlegt, ohne ein UTF-8-Zeichen zu teilen. Benötigt kein mbstring.
     */
    function pdl_mail_encode_header(string $text): string
    {
        $text = pdl_mail_header_value($text);
        if ($text === '' || preg_match('/[^\x20-\x7E]/', $text) !== 1) {
            return $text;
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            // Kein gültiges UTF-8: byteweise kodieren, damit nichts verloren geht
            $chars = str_split($text);
        }

        // 39 Byte ergeben 52 Base64-Zeichen; mit „=?UTF-8?B?…?=“ bleibt jede
        // Zeile unter 78 Zeichen, auch die erste mit „Subject: “.
        $words = [];
        $chunk = '';
        foreach ($chars as $char) {
            if ($chunk !== '' && strlen($chunk) + strlen($char) > 39) {
                $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
                $chunk = '';
            }
            $chunk .= $char;
        }
        if ($chunk !== '') {
            $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
        }

        return implode("\r\n ", $words);
    }
}

if (!function_exists('pdl_mail_format_address')) {
    /**
     * Baut „Name <adresse>“ mit kodiertem bzw. maskiertem Anzeigenamen.
     */
    function pdl_mail_format_address(string $address, string $name = ''): string
    {
        $address = pdl_mail_header_value($address);
        $name = pdl_mail_header_value($name);
        if ($name === '') {
            return $address;
        }
        if (preg_match('/[^\x20-\x7E]/', $name) === 1) {
            $name = pdl_mail_encode_header($name);
        } elseif (preg_match('/[()<>\[\]:;@\\\\,."]/', $name) === 1) {
            $name = '"' . addcslashes($name, '"\\') . '"';
        }
        return $name . ' <' . $address . '>';
    }
}

if (!function_exists('pdl_mail_sitename')) {
    /**
     * Name der Seite für Mails: Einstellung „sitename“ bzw. „site_name“,
     * sonst „PowerDownload“.
     *
     * @param array<string, mixed> $settings
     */
    function pdl_mail_sitename(array $settings): string
    {
        foreach (['sitename', 'site_name'] as $key) {
            $value = pdl_mail_header_value((string) ($settings[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
        return 'PowerDownload';
    }
}

if (!function_exists('pdl_site_base_url')) {
    /**
     * Absolute Adresse des PowerDownload-Verzeichnisses mit „/“ am Ende,
     * z. B. „https://example.com/downloads/“.
     *
     * Quelle ist die Einstellung „site_url“. Ist sie leer, wird die Adresse
     * aus der aktuellen Anfrage gebildet (Schema, Host, Pfad bis zur
     * downloads.php; aus pdl-admin/ heraus das übergeordnete Verzeichnis).
     * Für den Produktivbetrieb sollte site_url gesetzt sein, damit Links in
     * Mails nicht vom Host-Header der Anfrage abhängen.
     *
     * @param array<string, mixed>      $settings
     * @param array<string, mixed>|null $server   Standard: $_SERVER
     */
    function pdl_site_base_url(array $settings, ?array $server = null): string
    {
        $server = $server ?? $_SERVER;

        $configured = pdl_mail_header_value((string) ($settings['site_url'] ?? ''));
        if ($configured !== '' && preg_match('#^https?://[^/?\#\s]+#i', $configured) === 1) {
            $configured = (string) preg_replace('/[?#].*$/', '', $configured);
            if (preg_match('#^https?://[^/]+/.*\.php$#i', $configured) === 1) {
                // „…/downloads.php“ eingetragen: nur das Verzeichnis verwenden
                $configured = substr($configured, 0, (int) strrpos($configured, '/') + 1);
            }
            return rtrim($configured, '/') . '/';
        }

        $https = (!empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off')
            || (string) ($server['SERVER_PORT'] ?? '') === '443'
            || strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

        $host = (string) ($server['HTTP_HOST'] ?? '');
        $host_pattern = '/^(?:[A-Za-z0-9](?:[A-Za-z0-9.-]{0,251}[A-Za-z0-9])?|\[[0-9A-Fa-f:.]+\])(?::\d{1,5})?$/';
        if (preg_match($host_pattern, $host) !== 1) {
            $host = (string) ($server['SERVER_NAME'] ?? '');
            if (preg_match($host_pattern, $host) !== 1) {
                $host = 'localhost';
            }
        }

        $dir = str_replace('\\', '/', dirname((string) ($server['SCRIPT_NAME'] ?? '/downloads.php')));
        if (basename($dir) === 'pdl-admin') {
            $dir = str_replace('\\', '/', dirname($dir));
        }
        $dir = trim($dir, '/.');
        $path = '/';
        if ($dir !== '') {
            $path .= implode('/', array_map('rawurlencode', explode('/', $dir))) . '/';
        }

        return ($https ? 'https' : 'http') . '://' . $host . $path;
    }
}

if (!function_exists('pdl_script_url')) {
    /**
     * Absolute Adresse der downloads.php (Einstellung „script_file“) mit
     * optionaler Abfrage, z. B. pdl_script_url($settings, 'usercenter=login')
     * → „https://example.com/downloads/downloads.php?usercenter=login“.
     *
     * @param array<string, mixed>      $settings
     * @param array<string, mixed>|null $server
     */
    function pdl_script_url(array $settings, string $query = '', ?array $server = null): string
    {
        $script = trim((string) ($settings['script_file'] ?? ''));
        if ($script === '') {
            $script = 'downloads.php?';
        }
        if (!str_ends_with($script, '?') && !str_ends_with($script, '&')) {
            $script .= str_contains($script, '?') ? '&' : '?';
        }
        $url = $query === '' ? rtrim($script, '?&') : $script . ltrim($query, '?&');

        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        $base = pdl_site_base_url($settings, $server);
        if (str_starts_with($url, '/')) {
            // Pfad ab Wurzel: nur Schema und Host der Basisadresse verwenden
            $origin = (string) preg_replace('#^(https?://[^/]+).*$#i', '$1', $base);
            return $origin . $url;
        }
        return $base . $url;
    }
}

if (!function_exists('pdl_mail_default_templates')) {
    /**
     * Eingebaute Standardtexte. Sie gelten, wenn die gleichnamige Vorlage
     * (pdl3_template) fehlt oder leer ist, und entsprechen den Vorlagen in
     * pdl-inc/pdl3_schema.sql.
     *
     * @return array<string, string>
     */
    function pdl_mail_default_templates(): array
    {
        return [
            'mail_register' => "Hallo {nick},\n\n"
                . "vielen Dank für Ihre Registrierung bei {sitename}. Ihr Benutzerkonto ist eingerichtet.\n\n"
                . "Benutzername: {nick}\n"
                . "E-Mail-Adresse: {email}\n\n"
                . "Hier können Sie sich anmelden:\n"
                . "{login_url}\n\n"
                . "Ihr Passwort steht aus Sicherheitsgründen nicht in dieser E-Mail. Falls Sie es vergessen, legen Sie über „Passwort vergessen“ jederzeit ein neues fest.\n\n"
                . "Viele Grüße\n"
                . "{sitename}\n"
                . "{site_url}\n",
            'mail_lost1' => "Hallo {nick},\n\n"
                . "für Ihr Benutzerkonto bei {sitename} wurde ein neues Passwort angefordert.\n\n"
                . "Über diesen Link legen Sie ein neues Passwort fest:\n"
                . "{link}\n\n"
                . "Der Link ist {ttl} gültig und funktioniert nur einmal.\n\n"
                . "Falls Sie kein neues Passwort angefordert haben, ignorieren Sie diese E-Mail bitte. Ihr bisheriges Passwort bleibt dann gültig.\n\n"
                . "Viele Grüße\n"
                . "{sitename}\n"
                . "{site_url}\n",
            'mail_lost2' => "Hallo {nick},\n\n"
                . "das Passwort für Ihr Benutzerkonto bei {sitename} wurde soeben geändert.\n\n"
                . "Hier können Sie sich mit dem neuen Passwort anmelden:\n"
                . "{login_url}\n\n"
                . "Falls Sie Ihr Passwort nicht selbst geändert haben, legen Sie bitte sofort über „Passwort vergessen“ ein neues fest:\n"
                . "{lost_url}\n\n"
                . "Viele Grüße\n"
                . "{sitename}\n"
                . "{site_url}\n",
        ];
    }
}

if (!function_exists('pdl_mail_subjects')) {
    /**
     * Betreffzeilen der Benutzer-Mails (Platzhalter wie im Text).
     *
     * @return array<string, string>
     */
    function pdl_mail_subjects(): array
    {
        return [
            'mail_register' => 'Ihre Registrierung bei {sitename}',
            'mail_lost1' => 'Passwort zurücksetzen bei {sitename}',
            'mail_lost2' => 'Ihr Passwort bei {sitename} wurde geändert',
        ];
    }
}

if (!function_exists('pdl_mail_template')) {
    /**
     * Liefert die Vorlage $name aus $template oder den eingebauten Standardtext.
     *
     * @param array<string, mixed> $template
     */
    function pdl_mail_template(string $name, array $template): string
    {
        $text = (string) ($template[$name] ?? '');
        if (trim($text) === '') {
            $text = pdl_mail_default_templates()[$name] ?? '';
        }
        return $text;
    }
}

if (!function_exists('pdl_mail_placeholders')) {
    /**
     * Standard-Platzhalter für Benutzer-Mails. Alte Namen aus 3.5.0
     * ({user}, {url}, {script_file}, {pw}, {new_pw}) werden mit ersetzt,
     * damit angepasste Vorlagen weiter funktionieren; Passwörter werden nie
     * verschickt.
     *
     * @param array<string, mixed> $settings
     * @param array<string, string> $vars z. B. ['nick' => …, 'email' => …, 'link' => …]
     * @return array<string, string>
     */
    function pdl_mail_placeholders(array $settings, array $vars = []): array
    {
        $values = [
            'sitename' => pdl_mail_sitename($settings),
            'site_url' => pdl_script_url($settings),
            'login_url' => pdl_script_url($settings, 'usercenter=login'),
            'lost_url' => pdl_script_url($settings, 'usercenter=lost'),
            'ttl' => pdl_password_reset_ttl_text(),
            'nick' => '',
            'email' => '',
            'link' => '',
        ];
        foreach ($vars as $key => $value) {
            $values[$key] = $value;
        }
        $values['user'] = $values['nick'];
        $values['url'] = $values['link'];
        $values['script_file'] = $values['site_url'];
        $values['pw'] = '';
        $values['new_pw'] = '';
        return $values;
    }
}

if (!function_exists('pdl_mail_render')) {
    /**
     * Ersetzt {platzhalter} in $text.
     *
     * @param array<string, string> $vars
     */
    function pdl_mail_render(string $text, array $vars): string
    {
        $pairs = [];
        foreach ($vars as $key => $value) {
            $pairs['{' . $key . '}'] = $value;
        }
        return strtr($text, $pairs);
    }
}

if (!function_exists('pdl_send_template_mail')) {
    /**
     * Rendert Vorlage und Betreff ($name = mail_register, mail_lost1,
     * mail_lost2) und verschickt die Mail an $to.
     *
     * @param array<string, mixed>  $settings
     * @param array<string, mixed>  $template
     * @param array<string, string> $vars
     */
    function pdl_send_template_mail(string $name, string $to, array $settings, array $template, array $vars = []): bool
    {
        $placeholders = pdl_mail_placeholders($settings, $vars);
        $subject = pdl_mail_render(pdl_mail_subjects()[$name] ?? 'Nachricht von {sitename}', $placeholders);
        $body = pdl_mail_render(pdl_mail_template($name, $template), $placeholders);
        return pdl_send_mail($to, $subject, $body, $settings);
    }
}

if (!function_exists('pdl_send_mail')) {
    /**
     * Verschickt eine Text-Mail (UTF-8) mit standardkonformen Kopfzeilen.
     *
     * @param array<string, mixed>|null $settings      null: globales $settings
     * @param array<string, string>     $extra_headers zusätzliche Kopfzeilen
     */
    function pdl_send_mail(string $to, string $subject, string $body, ?array $settings = null, array $extra_headers = []): bool
    {
        if ($settings === null) {
            $settings = (isset($GLOBALS['settings']) && is_array($GLOBALS['settings'])) ? $GLOBALS['settings'] : [];
        }

        $to = pdl_mail_header_value($to);
        if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            error_log('PowerDownload: Mail nicht verschickt, Empfängeradresse ungültig.');
            return false;
        }

        $from_addr = pdl_mail_header_value((string) ($settings['mail_fromaddr'] ?? ''));
        $from_name = pdl_mail_header_value((string) ($settings['mail_fromname'] ?? ''));
        if ($from_name === '') {
            $from_name = pdl_mail_sitename($settings);
        }

        $headers = [];
        if ($from_addr !== '' && filter_var($from_addr, FILTER_VALIDATE_EMAIL) !== false) {
            $headers['From'] = pdl_mail_format_address($from_addr, $from_name);
        }
        $headers['MIME-Version'] = '1.0';
        $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        $headers['Content-Transfer-Encoding'] = '8bit';
        $headers['X-Mailer'] = 'PowerDownload';
        foreach ($extra_headers as $header_name => $header_value) {
            $header_name = (string) preg_replace('/[^A-Za-z0-9-]/', '', (string) $header_name);
            if ($header_name === '' || isset($headers[$header_name])) {
                continue;
            }
            $headers[$header_name] = pdl_mail_header_value((string) $header_value);
        }

        $encoded_subject = pdl_mail_encode_header($subject);
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        if (PHP_EOL !== "\n") {
            $body = str_replace("\n", PHP_EOL, $body);
        }

        $transport = $GLOBALS['pdl_mail_transport'] ?? null;
        if (is_callable($transport)) {
            return (bool) $transport($to, $encoded_subject, $body, $headers);
        }

        $sent = @mail($to, $encoded_subject, $body, $headers);
        if (!$sent) {
            $error = error_get_last();
            error_log('PowerDownload: Mailversand fehlgeschlagen (' . pdl_mail_header_value($subject) . ')'
                . (is_array($error) ? ': ' . $error['message'] : '.'));
        }
        return $sent;
    }
}
