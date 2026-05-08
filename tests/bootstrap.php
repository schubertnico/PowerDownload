<?php

/**
 * PHPUnit Bootstrap
 */

declare(strict_types=1);

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Mock global variables that functions depend on
$settings = [
    'dlspeed' => 56,
    'date_format' => 'd.m.Y',
    'script_file' => 'downloads.php?',
    'spages' => 10,
];

$template = [
    'alt_1' => '#FFFFFF',
    'alt_2' => '#F0F0F0',
    'footer_bg' => '#CCCCCC',
    'header_bg' => '#333333',
    'table_border' => '#000000',
];

$smilies = [];
$glossary = [];
$badwords = [];
$users = [];
$alt_switch = 0;
$page = 1;
$list = '';
$total = 0;
$install = 0;
$inadmin = 0;

// Include the functions file
require_once dirname(__DIR__) . '/pdl-inc/pdl_functions.inc.php';
require_once dirname(__DIR__) . '/pdl-inc/pdl_csrf.inc.php';
