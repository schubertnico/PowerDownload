<?php

/**
 * PowerDownload - Autoloader für Web-Installer und update.php
 *
 * Lädt die Klassen im Namensraum PowerDownload\Installer sowie
 * PowerDownload\LocalConfig und die CSRF-Hilfsfunktionen. Gibt nichts aus und
 * startet nichts. Composer gibt es auf einem Webspace nicht, deshalb ein
 * eigener Autoloader.
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/pdl_localconfig.inc.php';
require_once dirname(__DIR__) . '/pdl_csrf.inc.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'PowerDownload\\Installer\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $name = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . $name . '.php';

    if (preg_match('/^[A-Za-z]+$/', $name) === 1 && is_file($file)) {
        require $file;
    }
});
