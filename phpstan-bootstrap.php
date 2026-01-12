<?php
/**
 * PHPStan Bootstrap - Global Variable Declarations
 *
 * This file defines global variables for PHPStan static analysis.
 * These variables are normally defined in header.inc.php at runtime.
 */

declare(strict_types=1);

// Database handler
/** @var pdl_db_class $db_handler */
$db_handler = new pdl_db_class();

// SQL table names
/** @var array<string, string> $sql_table */
$sql_table = [];

// User session data
/** @var array<string, mixed> $user */
$user = [];

// User rights/permissions
/** @var array<string, string> $user_rights */
$user_rights = [];

// Template configuration
/** @var array<string, string> $template */
$template = [];

// Site settings
/** @var array<string, mixed> $settings */
$settings = [];

// Smilies array
/** @var array<int, array{old: string, neu: string}> $smilies */
$smilies = [];

// Glossary array
/** @var array<int, array{old: string, neu: string}> $glossary */
$glossary = [];

// Bad words array
/** @var array<int, string> $badwords */
$badwords = [];

// Current page number
/** @var int $page */
$page = 1;

// List type
/** @var string|null $list */
$list = null;

// Install mode flag
/** @var int $install */
$install = 0;

// Total count
/** @var int $total */
$total = 0;
