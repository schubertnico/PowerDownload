<?php

/**
 * PowerDownload - HTML-Bausteine für Installer und update.php
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

/**
 * Bootstrap-5-Formularbausteine.
 *
 * Feldnamen tragen kein Präfix (db_host), die id das Präfix „pdl_install_“
 * (pdl_install_db_host). Fehlermeldung: „<id>_error“, Hilfetext: „<id>_help“.
 * Die ids sind die Selektoren des Installationsvideos und der Tests. Alle
 * Werte werden maskiert.
 */
final class Html
{
    public const string ID_PREFIX = 'pdl_install_';

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function id(string $name): string
    {
        return self::ID_PREFIX . $name;
    }

    /**
     * Eingabefeld mit Label, Hilfetext und Fehlermeldung.
     *
     * @param array<string, string> $errors Fehlermeldungen je Feldname
     * @param array<string, string|int|bool> $attributes zusätzliche Attribute (true = ohne Wert, false = weglassen)
     */
    public static function input(
        string $name,
        string $label,
        string $value,
        array $errors,
        array $attributes = [],
        string $help = '',
    ): string {
        $error = $errors[$name] ?? '';
        $attributes += ['type' => 'text'];
        $id = self::id($name);

        return '<div class="mb-3">'
            . self::label($id, $label, ($attributes['required'] ?? false) === true)
            . '<input id="' . self::escape($id) . '" name="' . self::escape($name) . '"'
            . ' class="form-control' . ($error !== '' ? ' is-invalid' : '') . '"'
            . ' value="' . self::escape($value) . '"'
            . self::attributes($attributes)
            . self::describedBy($id, $help, $error)
            . ($error !== '' ? ' aria-invalid="true"' : '')
            . '>'
            . self::feedback($id, $error)
            . self::help($id, $help)
            . '</div>';
    }

    /**
     * Mehrzeiliges Eingabefeld.
     *
     * @param array<string, string> $errors
     * @param array<string, string|int|bool> $attributes
     */
    public static function textarea(
        string $name,
        string $label,
        string $value,
        array $errors,
        array $attributes = [],
        string $help = '',
    ): string {
        $error = $errors[$name] ?? '';
        $id = self::id($name);

        return '<div class="mb-3">'
            . self::label($id, $label, ($attributes['required'] ?? false) === true)
            . '<textarea id="' . self::escape($id) . '" name="' . self::escape($name) . '"'
            . ' class="form-control' . ($error !== '' ? ' is-invalid' : '') . '"'
            . self::attributes($attributes)
            . self::describedBy($id, $help, $error)
            . ($error !== '' ? ' aria-invalid="true"' : '')
            . '>' . self::escape($value) . '</textarea>'
            . self::feedback($id, $error)
            . self::help($id, $help)
            . '</div>';
    }

    /**
     * Meldung als Bootstrap-Alert (leer, wenn keine Meldung).
     */
    public static function alert(string $message, string $type, string $id): string
    {
        if ($message === '') {
            return '';
        }

        $type = in_array($type, ['success', 'danger', 'warning', 'info'], true) ? $type : 'info';
        $role = $type === 'danger' || $type === 'warning' ? 'alert' : 'status';

        return '<div id="' . self::escape($id) . '" class="alert alert-' . $type . '" role="' . $role . '">'
            . self::escape($message) . '</div>';
    }

    /**
     * Verstecktes Feld mit dem CSRF-Token (Name wie im übrigen PowerDownload).
     */
    public static function csrfField(string $token): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::escape($token) . '">';
    }

    private static function label(string $id, string $label, bool $required): string
    {
        return '<label for="' . self::escape($id) . '" class="form-label fw-semibold">'
            . self::escape($label)
            . ($required ? ' <span class="text-danger" aria-hidden="true">*</span>' : '')
            . '</label>';
    }

    /**
     * @param array<string, string|int|bool> $attributes
     */
    private static function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            if ($value === false) {
                continue;
            }
            $html .= ' ' . self::escape($key);

            if ($value !== true) {
                $html .= '="' . self::escape((string) $value) . '"';
            }
        }

        return $html;
    }

    private static function describedBy(string $id, string $help, string $error): string
    {
        $ids = [];

        if ($error !== '') {
            $ids[] = $id . '_error';
        }

        if ($help !== '') {
            $ids[] = $id . '_help';
        }

        return $ids === [] ? '' : ' aria-describedby="' . self::escape(implode(' ', $ids)) . '"';
    }

    private static function help(string $id, string $help): string
    {
        if ($help === '') {
            return '';
        }

        return '<div id="' . self::escape($id . '_help') . '" class="form-text">' . self::escape($help) . '</div>';
    }

    private static function feedback(string $id, string $message): string
    {
        if ($message === '') {
            return '';
        }

        return '<div id="' . self::escape($id . '_error') . '" class="invalid-feedback">' . self::escape($message) . '</div>';
    }
}
