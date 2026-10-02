<?php

/**
 * PowerDownload - Ausgabe der Installer-Seiten
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

/**
 * Gibt eine Seite aus Layout und Inhaltsvorlage aus (templates/*.php).
 *
 * Jede Vorlage liefert per `return` eine Funktion; direkt aufgerufen gibt sie
 * nichts aus.
 */
final class View
{
    /**
     * Vorlagen, die gerendert werden dürfen.
     */
    public const array TEMPLATES = ['requirements', 'database', 'website', 'admin', 'finish', 'done', 'locked', 'update', 'update_denied'];

    /**
     * @param array<string, mixed> $args benannte Argumente für die Vorlage
     * @param string $badge Kennzeichnung in der Kopfzeile, z. B. „Installation“
     * @param int $step aktueller Schritt (0 = ohne Schrittanzeige)
     * @param int $completed höchster erledigter Schritt
     */
    public static function render(string $template, string $title, string $badge, int $step, int $completed, array $args): void
    {
        if (!in_array($template, self::TEMPLATES, true)) {
            throw new \RuntimeException('Unbekannte Vorlage.');
        }

        $layout = self::load('layout');
        $body = self::load($template);

        $layout($title, $badge, $step, $completed, static function () use ($body, $args): void {
            $body(...$args);
        });
    }

    /**
     * Gibt die Seite als Zeichenkette zurück (für Tests).
     *
     * @param array<string, mixed> $args
     */
    public static function capture(string $template, string $title, string $badge, int $step, int $completed, array $args): string
    {
        ob_start();

        try {
            self::render($template, $title, $badge, $step, $completed, $args);
        } finally {
            $html = (string) ob_get_clean();
        }

        return $html;
    }

    private static function load(string $name): callable
    {
        $file = __DIR__ . '/templates/' . $name . '.php';
        $callable = is_file($file) ? require $file : null;

        if (!is_callable($callable)) {
            throw new \RuntimeException('Die Vorlage ' . $name . '.php fehlt oder ist beschädigt. Bitte laden Sie das Verzeichnis pdl-inc/installer/ erneut hoch.');
        }

        return $callable;
    }
}
