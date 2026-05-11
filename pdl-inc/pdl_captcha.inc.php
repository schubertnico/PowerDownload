<?php
declare(strict_types=1);

if (!function_exists('pdl_captcha_active')) {
    function pdl_captcha_active(array $settings): bool
    {
        return (($settings['captcha_enabled'] ?? 'N') === 'Y');
    }
}

if (!function_exists('pdl_captcha_render')) {
    /**
     * Erzeugt eine Bootstrap-Card mit einer einfachen Addition (zwei Zahlen
     * im Bereich 1–9) und speichert die erwartete Lösung in der Session.
     * Gibt den fertigen HTML-String zurück.
     */
    function pdl_captcha_render(array $settings): string
    {
        if (!pdl_captcha_active($settings)) {
            return '';
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $_SESSION['pdl_captcha_answer'] = $a + $b;
        return '<div class="mb-3">'
            . '<label for="pdlCaptcha" class="form-label">Bitte lösen Sie folgende einfache Aufgabe</label>'
            . '<div class="input-group">'
            . '<span class="input-group-text">' . (int)$a . ' + ' . (int)$b . ' =</span>'
            . '<input type="number" inputmode="numeric" min="0" max="100" id="pdlCaptcha" name="pdl_captcha" class="form-control" required autocomplete="off">'
            . '</div>'
            . '<div class="form-text">Diese kleine Rechen-Aufgabe verhindert automatisierte Eintragungen.</div>'
            . '</div>';
    }
}

if (!function_exists('pdl_captcha_verify')) {
    /**
     * Vergleicht $_POST['pdl_captcha'] mit der zuvor in der Session
     * gespeicherten Lösung. Bei deaktiviertem Captcha immer true.
     * Nach jedem Verify wird die Session-Lösung gelöscht (One-Shot).
     */
    function pdl_captcha_verify(array $settings): bool
    {
        if (!pdl_captcha_active($settings)) {
            return true;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $expected = $_SESSION['pdl_captcha_answer'] ?? null;
        unset($_SESSION['pdl_captcha_answer']);
        if ($expected === null) {
            return false;
        }
        $given = trim((string)($_POST['pdl_captcha'] ?? ''));
        if ($given === '' || !ctype_digit($given)) {
            return false;
        }
        return ((int)$given === (int)$expected);
    }
}
