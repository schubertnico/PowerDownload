<?php

/**
 * PowerDownload - CSRF Protection Helpers
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

/**
 * Returns current CSRF token; creates one if missing.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a submitted token against the session token.
 */
function csrf_verify(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $session_token = $_SESSION['csrf_token'] ?? '';
    if ($session_token === '' || $token === null || $token === '') {
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
